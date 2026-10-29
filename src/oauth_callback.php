<?php

session_start();
require_once __DIR__ . '/oauth_common.php';
require_once __DIR__ . '/../config.php';

$oauthState = $_SESSION['opark_oauth'] ?? [];
unset($_SESSION['opark_oauth']);
$provider = strtolower($_GET['provider'] ?? '');
$flow = $oauthState['flow'] ?? 'login';

if (!in_array($provider, ['google', 'facebook', 'sti'], true)
    || ($oauthState['provider'] ?? '') !== $provider
    || !isset($_GET['state'], $oauthState['state'])
    || !hash_equals($oauthState['state'], (string) $_GET['state'])) {
    opark_oauth_fail($flow, 'Sign-in could not be verified. Please try again.');
}

if (isset($_GET['error']) || empty($_GET['code'])) {
    opark_oauth_fail($flow, 'Sign-in was cancelled or denied.');
}

$providerConfig = opark_oauth_config()[$provider] ?? [];
try {
    $tokenParameters = [
        'client_id' => $providerConfig['client_id'] ?? '',
        'client_secret' => $providerConfig['client_secret'] ?? '',
        'code' => $_GET['code'],
        'redirect_uri' => $providerConfig['redirect_uri'] ?? '',
        'grant_type' => 'authorization_code',
    ];

    if ($provider === 'google') {
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $profileUrl = 'https://openidconnect.googleapis.com/v1/userinfo';
    } elseif ($provider === 'facebook') {
        $tokenUrl = 'https://graph.facebook.com/oauth/access_token';
        $profileUrl = 'https://graph.facebook.com/me?fields=id,name,email';
    } else {
        $tenantId = $providerConfig['tenant_id'] ?? '';
        if ($tenantId === '' || in_array(strtolower($tenantId), ['common', 'organizations', 'consumers'], true)) {
            throw new RuntimeException('The STI Microsoft Entra tenant ID is not configured.');
        }
        $tokenUrl = 'https://login.microsoftonline.com/' . rawurlencode($tenantId) . '/oauth2/v2.0/token';
        $profileUrl = 'https://graph.microsoft.com/v1.0/me?$select=id,displayName,mail,userPrincipalName';
        $tokenParameters['scope'] = 'openid profile email User.Read';
    }

    if ($provider === 'facebook') {
        unset($tokenParameters['grant_type']);
        $token = opark_oauth_http($tokenUrl . '?' . http_build_query($tokenParameters));
    } else {
        $token = opark_oauth_http($tokenUrl, null, $tokenParameters);
    }
    if (empty($token['access_token'])) {
        throw new RuntimeException('The identity provider did not return an access token.');
    }

    $profile = opark_oauth_http($profileUrl, $token['access_token']);
    $email = strtolower(trim($profile['email'] ?? ($profile['mail'] ?? ($profile['userPrincipalName'] ?? ''))));
    $name = trim($profile['name'] ?? ($profile['displayName'] ?? ''));
    if ($provider === 'google' && ($profile['email_verified'] ?? false) !== true) {
        throw new RuntimeException('The provider email address is not verified.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('The identity provider did not return a usable email address.');
    }

    $lookupUser = $conn->prepare('SELECT id, name, email, role FROM users WHERE email = ?');
    $lookupUser->bind_param('s', $email);
    $lookupUser->execute();
    $user = $lookupUser->get_result()->fetch_assoc();

    if (!$user && $flow === 'signup') {
        $role = $oauthState['role'] ?? '';
        if (!in_array($role, ['student', 'employee', 'guest'], true)) {
            throw new RuntimeException('Select a valid account type and try signing up again.');
        }
        if ($name === '') {
            $name = strstr($email, '@', true) ?: $email;
        }
        $passwordHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
        $plateNumber = $oauthState['plate_number'] ?? '';
        $insertUser = $conn->prepare('INSERT INTO users (name, email, plate_number, password, role) VALUES (?, ?, ?, ?, ?)');
        $insertUser->bind_param('sssss', $name, $email, $plateNumber, $passwordHash, $role);
        $insertUser->execute();
        $user = [
            'id' => $conn->insert_id,
            'name' => $name,
            'email' => $email,
            'role' => $role,
        ];
    } elseif (!$user) {
        throw new RuntimeException('No oPark account uses this email. Sign up first.');
    }

    $plateNumber = $oauthState['plate_number'] ?? '';
    $updatePlate = $conn->prepare('UPDATE users SET plate_number = ? WHERE id = ?');
    $updatePlate->bind_param('si', $plateNumber, $user['id']);
    $updatePlate->execute();

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['is_admin'] = $user['role'] === 'admin';
    header('Location: ' . ($user['role'] === 'admin' ? './admin_page.php' : './user_page.php'));
    exit();
} catch (Throwable $error) {
    opark_oauth_fail($flow, 'Social sign-in failed. Check the provider setup and try again.');
}