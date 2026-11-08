<?php

/**
 * A stand-in authorization server for local work, run with: php -S 127.0.0.1:8081 tools/fake-as.php
 * It knows two clients and hands out opaque tokens. Like a real server it binds a token to the brain
 * named in the "resource" parameter (RFC 8707), which the brain checks as the aud claim. Use a real server (Keycloak, an OAuth bundle) in production.
 */

const CLIENTS = [
    // client id => [secret, scopes the client may get]
    'reader' => ['reader-secret', 'brain:read'],
    'admin' => ['admin-secret', 'brain:read brain:write'],
];
const RESOURCE_SERVER = ['brain-server', 'brain-server-secret'];
$store = sys_get_temp_dir().'/fake-as-tokens.json';
$tokens = is_file($store) ? json_decode((string) file_get_contents($store), true) : [];

header('Content-Type: application/json');
$path = parse_url($_SERVER['REQUEST_URI'], \PHP_URL_PATH);

if ('/token' === $path) {
    [$id, $secret] = [$_POST['client_id'] ?? '', $_POST['client_secret'] ?? ''];
    if (!isset(CLIENTS[$id]) || !hash_equals(CLIENTS[$id][0], $secret)) {
        http_response_code(401);
        exit(json_encode(['error' => 'invalid_client']));
    }
    $token = bin2hex(random_bytes(16));
    $tokens[$token] = ['sub' => $id, 'scope' => CLIENTS[$id][1], 'aud' => $_POST['resource'] ?? '', 'exp' => time() + 3600];
    file_put_contents($store, json_encode($tokens));
    exit(json_encode(['access_token' => $token, 'token_type' => 'Bearer', 'expires_in' => 3600, 'scope' => CLIENTS[$id][1]]));
}

if ('/introspect' === $path) {
    if (($_SERVER['PHP_AUTH_USER'] ?? '') !== RESOURCE_SERVER[0] || ($_SERVER['PHP_AUTH_PW'] ?? '') !== RESOURCE_SERVER[1]) {
        http_response_code(401);
        exit(json_encode(['error' => 'invalid_client']));
    }
    $token = $tokens[$_POST['token'] ?? ''] ?? null;
    exit(json_encode($token && $token['exp'] > time() ? ['active' => true] + $token : ['active' => false]));
}

http_response_code(404);
echo json_encode(['error' => 'not_found']);
