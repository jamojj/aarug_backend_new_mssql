<?php

$server = "127.0.0.1,1433";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
];

try {
    $pdo = new PDO(
        "sqlsrv:Server=$server;Database=aarug;TrustServerCertificate=true",
        "aarug_laravel",
        "@Aarug12345",
        $options
    );

    echo "SQL SERVER PDO CONNECTION SUCCESSFUL";

    $attributes = [
    'ATTR_CASE' => [PDO::ATTR_CASE, PDO::CASE_NATURAL],
    'ATTR_ERRMODE' => [PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION],
    'ATTR_ORACLE_NULLS' => [PDO::ATTR_ORACLE_NULLS, PDO::NULL_NATURAL],
    'ATTR_STRINGIFY_FETCHES' => [PDO::ATTR_STRINGIFY_FETCHES, true],
    'ATTR_EMULATE_PREPARES' => [PDO::ATTR_EMULATE_PREPARES, false],
];

foreach ($attributes as $name => [$attribute, $value]) {
    try {
        $result = $pdo->setAttribute($attribute, $value);
        echo "$name: SUCCESS (result: " . var_export($result, true) . ")\n";
    } catch (Throwable $e) {
        echo "$name: FAILED - " . $e->getMessage() . "\n";
    }
}
} catch (PDOException $e) {
    echo $e->getMessage();
}