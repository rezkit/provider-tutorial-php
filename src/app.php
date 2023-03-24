<?php
namespace RezKit\Provider\Tutorial;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require_once(__DIR__ . '/../vendor/autoload.php');

// Initialize Product Database
$products = new Products();

// Initialize a new app
$app = AppFactory::create();
$app->addRoutingMiddleware();

// Enable error logging
$app->addErrorMiddleware(false, true, true);

// Enable parsing of request bodies from JSON
$app->addBodyParsingMiddleware();

// Set up authentication with the following credential sets
// In production this would use a key-store or database.
$app->addMiddleware(new Middleware\Authentication([
    'demo' => [
        'id' => 'demo',
        'secret' => 'super_secret_value',
    ]
]));

// Ensures responses have their Content-Type set to application/json
$app->addMiddleware(new Middleware\Json());

/**
 * API method to get the service descriptor.
 * **Required Method**
 */
$app->get('/service', static function (Request  $request, Response $response) {
    $service = new ServiceDescription("My Provider");
    $response->getBody()->write(json_encode($service->generateDescription(), JSON_PRETTY_PRINT));
    return $response;
});

/**
 * API Method to search packages
 * **Required Method**
 */
$app->get('/products/search', static function (Request $req, Response $res) use ($products) {

    $results = $products->search($req->getQueryParams());

    $res->getBody()->write(json_encode($results, JSON_PRETTY_PRINT));
    return $res;
});


/**
 * API Method to get a single product
 * **Required Method**
 */
$app->get('/products/{id}', static function (Request $req, Response $res, array $params) use ($products) {
    $p = $products->find($params['id']);

    if (!$p) {
        $res->withStatus(404)->getBody()->write(json_encode(['error' => 'Product not found']));
        return $res;
    }

    $res->getBody()->write(json_encode($p, JSON_PRETTY_PRINT));
    return $res;
});

/**
 * API Method to list all package products
 * **Optional, Recommended Method**
 */
$app->get('/products', static function (Request $req, Response $res) use ($products) {
    $res->getBody()->write(json_encode($products->all(), JSON_PRETTY_PRINT));
    return $res;
});

/**
 * Create a new booking
 */
$app->post('/book', static function (Request $req, Response $res) use ($products) {
    $params = $req->getQueryParams();

    $reservation = new Reservation();
    $reservation->passengers = $params['passengers'];
    $reservation->productId = $params['productId'];
    $reservation->credentialId = $req->getAttribute('credentialId');

    try {
        $products->book($reservation);
        $res = $res->withStatus(201);
        $res->getBody()->write(json_encode(['reference' => $reservation->reference]));
    } catch (Exception $e) {
        // If there's an error, return this for RezKit to show to the agent.
        $res = $res->withStatus(422);
        $res->getBody()->write(json_encode(['error' => $e->getMessage()]));
    }

    return $res;
});


/**
 * API method to cancel a reservation
 * **Optional, Recommended Method**
 */
$app->post('/cancel', static function (Request $req, Response $res) use ($products) {
    $r = Reservation::find($req->getParsedBody()->id);

    // If the reservation can't be found, or belongs to another credential
    // Return an error
    if (!$r || $r->credentialId !== $req->getAttribute('credential')) {
        $res = $res->withStatus(404);
        $res->getBody()->write(json_encode(['error' => 'Invalid Reservation']));
        return $res;
    }

    // Check the product and return the spaces to available
    $p = $products->find($r->productId);

    if ($p && $p['inventory']['type'] === 'allocation') {
        $p['inventory']['available'] += count($r->passengers);
    }

    $r->status = Reservation::STATUS_CANCELLED;
    $r->save();

    return $res->withStatus(202);
});

$app->run();
