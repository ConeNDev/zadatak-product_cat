<?php
require 'config.php';

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];

//uri
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uriParts = array_values(array_filter(explode('/', $uri)));

// sluzi za slanje odgovora u jsonu
function sendResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}
// rutiranje na osnovu prvog segmenta
if (isset($uriParts[0]) && $uriParts[0] === 'categories') {
    // /categories
    if (count($uriParts) === 1) {
        // GET /categories 
        if ($method === 'GET') {
            global $pdo;
            $stmt = $pdo->query("SELECT * FROM categories");
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            sendResponse($categories);
        } else {
            sendResponse(["error" => "Nepodržana metoda za /categories"], 405);
        }
    } 
}
