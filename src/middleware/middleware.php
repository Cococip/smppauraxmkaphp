<?php
/**
 * Middleware Configuration
 * 
 * Middleware untuk aplikasi SMS notification
 */

use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

// Add JSON parsing middleware
$app->addBodyParsingMiddleware();

// Add middleware
$app->add(function (Request $request, RequestHandler $handler) {
    $response = $handler->handle($request);
    
    // Add CORS headers
    $response = $response->withHeader('Access-Control-Allow-Origin', '*');
    $response = $response->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization, X-API-Key');
    $response = $response->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    
    return $response;
});

// Error handling middleware
$app->addErrorMiddleware(true, true, true);

// Rate limiting middleware (basic implementation)
$app->add(function (Request $request, RequestHandler $handler) {
    $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
    $rateLimit = $_ENV['RATE_LIMIT_PER_MINUTE'] ?? 60;
    
    // Simple rate limiting using file-based storage
    $rateLimitFile = sys_get_temp_dir() . '/rate_limit_' . md5($ip);
    
    if (file_exists($rateLimitFile)) {
        $data = json_decode(file_get_contents($rateLimitFile), true);
        if ($data['count'] >= $rateLimit && (time() - $data['reset_time']) < 60) {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Rate limit exceeded. Please try again later.'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(429);
        }
        
        if ((time() - $data['reset_time']) >= 60) {
            $data = ['count' => 1, 'reset_time' => time()];
        } else {
            $data['count']++;
        }
    } else {
        $data = ['count' => 1, 'reset_time' => time()];
    }
    
    file_put_contents($rateLimitFile, json_encode($data));
    
    return $handler->handle($request);
});
