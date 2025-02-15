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
    elseif (count($uriParts) >= 2) {
        // /categories/{id} ili /categories/{id}/products ili /categories/{id}/export
        $categoryId = (int)$uriParts[1];

        // provera da li je segment [2] "products" ili "export"
        if (isset($uriParts[2]) && $uriParts[2] === 'products') {
            // GET /categories/{id}/products - prikaz proizvoda u kategoriji
            if ($method === 'GET') {
                global $pdo;
                $stmt = $pdo->prepare("
                    SELECT p.*, c.name AS category_name
                    FROM products p
                    JOIN categories c ON p.category_id = c.id
                    WHERE c.id = ?
                ");
                $stmt->execute([$categoryId]);
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                sendResponse($products);
            } else {
                sendResponse(["error" => "Nepodržana metoda"], 405);
            }
        }
        elseif (isset($uriParts[2]) && $uriParts[2] === 'export') {
            // BONUS: GET /categories/{id}/export - generisanje CSV fajla za proizvode iz date kategorije
            if ($method === 'GET') {
                global $pdo;
                // 1) provera da li kategorija postoji
                $stmtCat = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
                $stmtCat->execute([$categoryId]);
                $category = $stmtCat->fetch(PDO::FETCH_ASSOC);

                if (!$category) {
                    sendResponse(["error" => "Kategorija nije pronađena"], 404);
                }

                // 2) uzimamo proizvode iz te kategorije
                $stmtProd = $pdo->prepare("
                    SELECT * FROM products
                    WHERE category_id = ?
                ");
                $stmtProd->execute([$categoryId]);
                $products = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

                // 3) generišemo CSV "u letu" i saljemo ga klijentu
                $categorySafe = preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($category['name']));
                $filename = $categorySafe . '_' . date("Y_m_d-H_i") . ".csv";

                // Podesavamo HTTP zaglavlja za download
                header('Content-Type: text/csv');
                header("Content-Disposition: attachment; filename=\"$filename\"");

                // otvaramo "output stream"
                $output = fopen('php://output', 'w');

                // upisujemo header red CSV fajla (prilagodi po potrebi)
                fputcsv($output, ['id','product_code','category_id','department_id','manufacturer_id','upc','sku','regular_price','sale_price','description']);

                // upisujemo svaku vrstu
                foreach ($products as $prod) {
                    fputcsv($output, [
                        $prod['id'],
                        $prod['product_code'],
                        $prod['category_id'],
                        $prod['department_id'],
                        $prod['manufacturer_id'],
                        $prod['upc'],
                        $prod['sku'],
                        $prod['regular_price'],
                        $prod['sale_price'],
                        $prod['description'],
                    ]);
                }
                fclose($output);
                exit; // prekidamo skriptu posto smo vec poslali CSV
            } else {
                sendResponse(["error" => "Nepodržana metoda"], 405);
            }
        }
        else {
            // /categories/{id} => PUT (izmena naziva), DELETE (brisanje)
            if ($method === 'PUT') {
                // azuriranje update kategorije
                $input = json_decode(file_get_contents('php://input'), true);
                if (!isset($input['name']) || empty($input['name'])) {
                    sendResponse(["error" => "Nedostaje 'name' za kategoriju"], 400);
                }

                global $pdo;
                $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
                $stmt->execute([$input['name'], $categoryId]);
                sendResponse(["message" => "Kategorija ažurirana"]);
            } elseif ($method === 'DELETE') {
                // brisanje kategorije
                global $pdo;
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$categoryId]);
                sendResponse(["message" => "Kategorija obrisana"]);
            } else {
                sendResponse(["error" => "Nepodržana metoda"], 405);
            }
        }
    }
}
