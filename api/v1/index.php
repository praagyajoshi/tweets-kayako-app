<?php

include_once __DIR__.'/../../config/application.php';

// The .htaccess file gives us 'method' as a GET parameter.
// We utilise this for routing.
$method = isset($_GET['method']) ? $_GET['method'] : '';

$output = [];

switch($method) {

    // GET /tweets
    case 'tweets':

        // This GET call accepts two parameters:
        // 1. 'max_id' - used for load more
        // 2. 'count' - number of results to be fetched
        $max_id = isset($_GET['max_id']) ? $_GET['max_id'] : '';
        $count = isset($_GET['count']) ? $_GET['count'] : 20;

        // Asking our controller to fetch
        // the tweets for us.
        $controller = new \Controllers\Twitter();
        $result = $controller->getTweets($max_id, $count);

        // Creating the response
        $output = [
            'response_code' => 200,
            'status' => 'success',
            'message' => 'Tweeets fetched',
            'result' => $result
        ];
        break;

    // Default case for when the method
    // received isn't listed here but is
    // listed in the .htaccess
    // (If it isn't listed in .htaccess, Apache
    // will respond with the 404 page)
    default:
        $output = [
            'response_code' => 404,
            'status' => 'failure',
            'message' => 'Method not found'
        ];
        break;
}

// Dynamically setting the response code
http_response_code($output['response_code']);

// Setting some response headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Returning the output as JSON
echo json_encode($output);

?>
