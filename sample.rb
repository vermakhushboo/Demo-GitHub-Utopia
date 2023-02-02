<?php
use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Firebase\JWT\JWT;

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::create(__DIR__);
$dotenv->load();

$privateKey = openssl_pkey_get_private(getenv('GITHUB_PRIVATE_KEY'));
$webhookSecret = getenv('GITHUB_WEBHOOK_SECRET');
$appIdentifier = getenv('GITHUB_APP_IDENTIFIER');

$server = new Server("0.0.0.0", 3000);

$server->on('request', function (Request $request, Response $response) use ($privateKey, $webhookSecret, $appIdentifier) {
    $payloadRaw = $request->rawContent();
    try {
        $payload = json_decode($payloadRaw, true);
    } catch (\Exception $e) {
        $response->status(400);
        $response->end("Invalid JSON: $payloadRaw");
        return;
    }

    $theirSignatureHeader = $request->header['http_x_hub_signature_256'] ?? 'sha256=';
    [$method, $theirDigest] = explode('=', $theirSignatureHeader);
    $ourDigest = hash_hmac('sha256', $payloadRaw, $webhookSecret);
    if (!hash_equals($theirDigest, $ourDigest)) {
        $response->status(401);
        $response->end('Unauthorized');
        return;
    }

    $iat = time();
    $exp = $iat + 10 * 60;
    $payload = [
        'iat' => $iat,
        'exp' => $exp,
        'iss' => $appIdentifier,
    ];
    $jwt = JWT::encode($payload, $privateKey, 'RS256');

    $octokit = new \Octokit\Client(['base_uri' => 'https://api.github.com/']);
    $octokit->authenticate($jwt, \Octokit\Client::AUTH_JWT);

    $installationId = $payload['installation']['id'];
    $installationToken = $octokit->apps()->createInstallationToken($installationId)->token;
    $installationClient = new \Octokit\Client(['base_uri' => 'https://api.github.com/']);
    $installationClient->authenticate($installationToken, \Octokit\Client::AUTH_BEARER);

    $response->status(200);
    $response->end();
});

$server->start();
