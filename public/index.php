<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Payone\AjaxGateway\Payone as Payone;

if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../src/Payone.php';
require __DIR__ . '/../vendor/autoload.php';

session_start();

// Instantiate the app
$settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App($settings);

// Set up dependencies
require __DIR__ . '/../src/dependencies.php';

// Register middleware
require __DIR__ . '/../src/middleware.php';

// Register routes
require __DIR__ . '/../src/routes.php';

$app->get('/v1/invoice/', function (Request $request, Response $response) {
    $jsonp = $request->getQueryParams()['callback'];

    $parameters = array(
        "request" => "authorization",
        "clearingtype" => "rec",
        "amount" => "100000",
        "reference" => uniqid(),
        "narrative_text" => "Your momma's order",
    );
    $personalData = array(
        "salutation" => "Herr",
        "title" => "Dr.",
        "firstname" => "Paul",
        "lastname" => "Payer",
        "street" => "Fraunhofer Straße 2-4",
        "addressaddition" => "EG",
        "zip" => "24118",
        "city" => "Kiel",
        "country" => "DE",
        "email" => "paul.neverpayer@payone.de",
        "telephonenumber" => "043125968533",
        "birthday" => "19700204",
        "language" => "de",
        "gender" => "m",
        "ip" => "8.8.8.8"
    );

    $shippingData = array(
        "shipping_firstname" => "Paul",
        "shipping_lastname" => "Neverpayer",
        "shipping_street" => "Alt-Moabit 70",
        "shipping_zip" => "10551",
        "shipping_city" => "Berlin",
        "shipping_country" => "DE"
    );
    $articles = array(
        'de[1]' => 'Artikel 1',
        'it[1]' => 'goods',
        'id[1]' => '4711',
        'pr[1]' => '45000',
        'no[1]' => '2',
        'va[1]' => '19',
        'de[2]' => 'Versandkosten',
        'it[2]' => 'shipment',
        'id[2]' => '1234',
        'pr[2]' => '11000',
        'no[2]' => '1',
        'va[2]' => '19',
        'de[3]' => 'Gutschein %',
        'it[3]' => 'voucher',
        'id[3]' => 'GUT100',
        'pr[3]' => '-1000',
        'no[3]' => '1',
        'va[3]' => '19',
    );
    $p1request = array_merge($parameters, $personalData, $shippingData, $articles);
    try {
        $p1response = Payone::doCurl($p1request);
        $response->getBody()->write($jsonp . "(" . json_encode($p1response) . ")");
    } catch (\Exception $e) {
        $p1response = $e->getMessage();
        // Exception message is already json
        $response->getBody()->write($jsonp . "(" . $p1response . ")");
    }


    return $response->withStatus(200)->withHeader("Content-Type", "text/javascript");
});

// Run app
$app->run();
