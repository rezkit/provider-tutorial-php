<?php

namespace RezKit\Provider\Tutorial\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Authentication Middleware
 */
class Authentication implements MiddlewareInterface
{
    public function __construct(private array $credentials)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        [$auth] = $request->getHeader('X-Request-Signature');

        [$credentialId, $timestamp, $signature] = explode(' ', $auth);

        $unauthorized = new Response(403);
        $unauthorized->getBody()->write(json_encode(['error' => 'Invalid authentication']));

        // Check the public credential is correct
        if (!in_array($credentialId, array_keys($this->credentials))) {
            return $unauthorized;
        }


        // Check the timestamp is within 1 minute
        if ( abs(time() - (int)$timestamp) > 60 ) {
            // Invalid timestamp
            return $unauthorized;
        }

        // Check that the request signature matches
        [$algo, $sigv] = explode(':', $signature);

        if ($algo !== 'SHA256') {
            // Unsupported algorithm
            return $unauthorized;
        }

        $payload = $timestamp;

        if ($request->getMethod() === 'POST') $payload .= $request->getBody()->read(1024);
        $expected = hash_hmac('sha256', $payload, $this->credentials[$credentialId]['secret']);

        if ($sigv !== $expected) {
            // Invalid signature...
            return $unauthorized;
        }

        // Everything is valid...
        $request = $request->withAttribute('credentialId', $credentialId);
        return $handler->handle($request);
    }
}
