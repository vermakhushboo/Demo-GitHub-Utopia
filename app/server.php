<?php

require 'vendor/autoload.php';
use Ahc\Jwt\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as Psr7Request;
use Utopia\App;
use Utopia\Swoole\Request;
use Utopia\Swoole\Response;
use Utopia\Swoole\Files;
use Utopia\CLI\Console;
use Swoole\Http\Server;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Utopia\Validator\Wildcard;

$http = new Server("0.0.0.0", 8080);

Files::load(__DIR__ . '/../public'); // Static files location

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..');
$dotenv->load();

App::init(function ($response) {
    $response
        ->addHeader('Cache-control', 'no-cache, no-store, must-revalidate')
        ->addHeader('Expires', '-1')
        ->addHeader('Pragma', 'no-cache')
        ->addHeader('X-XSS-Protection', '1;mode=block');
}, ['response'], '*');

App::shutdown(function ($request) {
    $date = new DateTime();
    Console::success($date->format('c') . ' ' . $request->getURI());
}, ['request'], '*');

App::get('/')
    ->inject('request')
    ->inject('response')
    ->action(
        function ($request, $response) {
            // Return a static file
            $response->send(Files::getFileContents('/index.html'));
        }
    );

App::post('/todos')
    ->inject('response')
    ->param('task', "", new Wildcard(), 'Prefs key-value JSON object.')
    ->param('is_complete', true, new Wildcard(), 'Tells whether task is complete or not')
    ->action(
        function ($task, $is_complete, $response) {
            $id = uniqid();
            $path = \realpath('/app/app/todos.json');
            $data = json_decode(file_get_contents($path), true);
            $task_entry = ['id' => $id, 'task' => $task, 'is_complete' => $is_complete];
            array_push($data, $task_entry);
            $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            file_put_contents($path, $jsonData);
            $response->json($data);
        }
    );

App::post('/')
    ->inject('request')
    ->inject('response')
    ->action(
        function ($request, $response) {
            try{
                // fetch env variables from .env file
                $privateKeyString = getenv('GITHUB_PRIVATE_KEY');
                $privateKey = openssl_pkey_get_private($privateKeyString);
                $webhookSecret = getenv('GITHUB_WEBHOOK_SECRET');
                $appIdentifier = getenv('GITHUB_APP_IDENTIFIER');
            } catch (\Exception $e) {
                $response->json(['Error3' => 'World']);
                return;
            }
            // Get Payload request
            $payload = $request->getParams();
            // Fetch params like installation id from payload
            $installationId = $payload['installation']['id'];
            try {
                $payloadRaw = json_encode($payload, true);
            } catch (\Exception $e) {
                $response->json(['Error1' => 'World']);
                return;
            }

            try{
                // verify webhook signature
                $theirSignatureHeader = $request->header['http_x_hub_signature_256'] ?? 'sha256=';
                // [$method, $theirDigest] = explode('=', $theirSignatureHeader);
                // $ourDigest = hash_hmac('sha256', $payloadRaw, $webhookSecret);
                // if (!hash_equals($theirDigest, $ourDigest)) {
                //     var_dump("hehew");
                //     $response->json(['status' => 'Unauthorized']);
                //     return;
                // }

                $iat = time();
                $exp = $iat + 10 * 60;
                $payload = [
                    'iat' => $iat,
                    'exp' => $exp,
                    'iss' => $appIdentifier,
                ];

                $key = openssl_pkey_new([
                    'digest_alg' => 'sha256',
                    'private_key_bits' => 1024,
                    'private_key_type' => OPENSSL_KEYTYPE_RSA,
                ]);
                // authenticate app
                $jwt = new JWT($privateKey, 'RS256');
                $token = $jwt->encode($payload);
                // Authenticate the app installation in order to run API operations
                $client = new Client([
                    // Base URI is used with relative requests
                    'base_uri' => 'https://api.github.com',
                    // You can set any number of default request options.
                    'timeout'  => 2.0,
                ]);
                // Use the generated bearer token to make API requests on behalf of the GitHub App
                $req = new Psr7Request('POST', "/app/installations/$installationId/access_tokens", ["Authorization" => "Bearer $token"]);
                $res = $client->send($req);
                $response->status(200);
                $response->send();

            } catch (\Exception $e) {
                var_dump($e->getTraceAsString());
                var_dump($e->getMessage());
                $response->json(['Error' => 'World']);
                return;
            }
            
        }
    );

/*
    Configure the Swoole server to respond with the Utopia app.    
*/

$http->on('request', function (SwooleRequest $swooleRequest, SwooleResponse $swooleResponse) {

    $request = new Request($swooleRequest);
    $response = new Response($swooleResponse);
    $app = new App('America/Toronto');

    try {
        $app->run($request, $response);
    } catch (\Throwable $th) {
        Console::error('There\'s a problem with ' . $request->getURI());
        $swooleResponse->end('500: Server Error');
    }
});

$http->start();