<?php

session_start();
require_once __DIR__ . '/oauth_common.php';

$provider = strtolower($_GET['provider'] ?? '');
$flow = $_GET['flow'] ?? 'login';
$allowedProviders = ['google', 'facebook', 'sti'];
if (!in_array($provider, $allowedProviders, true) || !in_array($flow, ['login', 'signup'], true)) {
    opark_oauth_fail('login', 'That sign-in option is not available.');
}

$plateNumber = normalizePlateNumber($_POST['plate_number'] ?? '');
if ($plateNumber === null) {
    opark_oauth_fail($flow, 'Please enter a valid plate number before continuing.');
}

$role = $_POST['role'] ?? $_GET['role'] ?? '';
if ($flow === 'signup' && !in_array($role, ['student', 'employee', 'guest'], true)) {
    opark_oauth_fail('signup', 'Select an account type before continuing.');
}

$providerConfig = opark_oauth_config()[$provider] ?? [];
if (empty($providerConfig['client_id']) || empty($providerConfig['client_secret']) || empty($providerConfig['redirect_uri'])) {
    opark_oauth_fail($flow, 'Social sign-in is not configured yet.');
}

$state = bin2hex(random_bytes(32));
$_SESSION['opark_oauth'] = [
    'provider' => $provider,
    'flow' => $flow,
    'role' => $role,
    'plate_number' => $plateNumber,
    'state' => $state,
];

if ($provider === 'google') {
    $authorizationUrl = 'https://accounts.google.com/o/oauth2/v2/auth';
    $parameters = [
        'client_id' => $providerConfig['client_id'],
        'redirect_uri' => $providerConfig['redirect_uri'],
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'state' => $state,
        'prompt' => 'select_account',
    ];
} elseif ($provider === 'facebook') {
    $authorizationUrl = 'https://www.facebook.com/dialog/oauth';
    $parameters = [
        'client_id' => $providerConfig['client_id'],
        'redirect_uri' => $providerConfig['redirect_uri'],
        'response_type' => 'code',
        'scope' => 'email,public_profile',
        'state' => $state,
    ];
} else {
    $tenantId = $providerConfig['tenant_id'] ?? '';
    if ($tenantId === '' || in_array(strtolower($tenantId), ['common', 'organizations', 'consumers'], true)) {
        opark_oauth_fail($flow, 'Configure the STI Microsoft Entra tenant ID before using STI sign-in.');
    }

    $authorizationUrl = 'https://login.microsoftonline.com/' . rawurlencode($tenantId) . '/oauth2/v2.0/authorize';
    $parameters = [
        'client_id' => $providerConfig['client_id'],
        'redirect_uri' => $providerConfig['redirect_uri'],
        'response_type' => 'code',
        'response_mode' => 'query',
        'scope' => 'openid profile email User.Read',
        'state' => $state,
        'prompt' => 'select_account',
    ];
}

header('Location: ' . $authorizationUrl . '?' . http_build_query($parameters));
exit();