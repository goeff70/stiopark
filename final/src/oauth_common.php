<?php

function normalizePlateNumber($value): ?string
{
    $plateNumber = strtoupper(preg_replace('/\s+/', ' ', trim((string) $value)));
    return preg_match('/\A[A-Z0-9](?:[A-Z0-9 -]{0,18}[A-Z0-9])?\z/', $plateNumber)
        ? $plateNumber
        : null;
}

function opark_oauth_config(): array
{
    $configPath = __DIR__ . '/oauth_config.local.php';
    if (!is_file($configPath)) {
        return [];
    }

    $config = require $configPath;
    return is_array($config) ? $config : [];
}

function opark_oauth_fail(string $flow, string $message): void
{
    if ($flow === 'signup') {
        $_SESSION['register_error'] = $message;
        $_SESSION['activate_form'] = 'register';
        header('Location: ./signup/index.php');
    } else {
        $_SESSION['login_error'] = $message;
        $_SESSION['activate_form'] = 'login';
        header('Location: ./login/index.php');
    }
    exit();
}

function opark_oauth_http(string $url, ?string $accessToken = null, ?array $postFields = null): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('PHP cURL must be enabled to use social sign-in.');
    }

    $curl = curl_init($url);
    $headers = ['Accept: application/json'];
    if ($accessToken !== null) {
        $headers[] = 'Authorization: Bearer ' . $accessToken;
    }

    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    if ($postFields !== null) {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postFields));
    }

    $response = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    if ($response === false || $status < 200 || $status >= 300) {
        throw new RuntimeException($error !== '' ? $error : 'The identity provider rejected the request.');
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        throw new RuntimeException('The identity provider returned an invalid response.');
    }

    return $data;
}