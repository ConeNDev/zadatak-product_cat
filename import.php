<?php
require 'config.php';

$csvFile = __DIR__ . '/product_categories.csv';


if (!file_exists($csvFile)) {
    die("CSV fajl '$csvFile' ne postoji.\n");
}

if (($handle = fopen($csvFile, 'r')) !== false) {
    // prvo cita header red
    $header = fgetcsv($handle, 1000, ',');
    if (!$header) {
        die("CSV fajl je prazan ili nema header red.\n");
    }

    $stmtSelectCategory     = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
    $stmtInsertCategory     = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");

    $stmtSelectDepartment   = $pdo->prepare("SELECT id FROM departments WHERE name = ?");
    $stmtInsertDepartment   = $pdo->prepare("INSERT INTO departments (name) VALUES (?)");

    $stmtSelectManufacturer = $pdo->prepare("SELECT id FROM manufacturers WHERE name = ?");
    $stmtInsertManufacturer = $pdo->prepare("INSERT INTO manufacturers (name) VALUES (?)");

    $stmtInsertProduct      = $pdo->prepare("
        INSERT INTO products 
            (product_code, category_id, department_id, manufacturer_id, upc, sku, regular_price, sale_price, description)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    // prolazimo kroz svaki red csv fajla
    while (($data = fgetcsv($handle, 1000, ',')) !== false) {
        
        var_dump($data);
        // citamo vrednosti iz reda
        list(
            $productCode,
            $categoryName,
            $departmentName,
            $manufacturerName,
            $upc,
            $sku,
            $regularPrice,
            $salePrice,
            $description
        ) = $data;
        var_dump($productCode, $categoryName, $departmentName, $manufacturerName, $upc, $sku, $regularPrice, $salePrice, $description);
        // 1) Proveri, ubaci kategoriju
        $stmtSelectCategory->execute([$categoryName]);
        $catRow = $stmtSelectCategory->fetch(PDO::FETCH_ASSOC);
        if ($catRow) {
            $categoryId = $catRow['id'];
        } else {
            $stmtInsertCategory->execute([$categoryName]);
            $categoryId = $pdo->lastInsertId();
        }

        // 2) Proveri, ubaci odeljak
        $stmtSelectDepartment->execute([$departmentName]);
        $depRow = $stmtSelectDepartment->fetch(PDO::FETCH_ASSOC);
        if ($depRow) {
            $departmentId = $depRow['id'];
        } else {
            $stmtInsertDepartment->execute([$departmentName]);
            $departmentId = $pdo->lastInsertId();
        }

        // 3) Proveri, ubaci proizvodjaca
        $stmtSelectManufacturer->execute([$manufacturerName]);
        $manRow = $stmtSelectManufacturer->fetch(PDO::FETCH_ASSOC);
        if ($manRow) {
            $manufacturerId = $manRow['id'];
        } else {
            $stmtInsertManufacturer->execute([$manufacturerName]);
            $manufacturerId = $pdo->lastInsertId();
        }

        // 4) Ubaci proizvod
        $stmtInsertProduct->execute([
            $productCode,
            $categoryId,
            $departmentId,
            $manufacturerId,
            $upc,
            $sku,
            $regularPrice,
            $salePrice,
            $description
        ]);
    }

    fclose($handle);
    echo "CSV podaci su uspešno ubačeni u bazu.\n";
} else {
    die("Ne mogu da otvorim CSV fajl.\n");
}
