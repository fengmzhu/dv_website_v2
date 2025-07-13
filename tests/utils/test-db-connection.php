<?php
echo "Database Connection Test\n";
echo "========================\n\n";

echo "Testing IT Domain Database Connection...\n";
try {
    $it_db = new PDO(
        "mysql:host=it-domain-db;dbname=it_domain_db;charset=utf8mb4",
        "it_user",
        "it_password",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ IT Domain database connection successful\n";
} catch(PDOException $e) {
    echo "❌ IT Domain database connection failed: " . $e->getMessage() . "\n";
}

echo "\nTesting NX Domain Database Connection...\n";
try {
    $nx_db = new PDO(
        "mysql:host=nx-domain-db;dbname=nx_domain_db;charset=utf8mb4",
        "nx_user",
        "nx_password",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ NX Domain database connection successful\n";
} catch(PDOException $e) {
    echo "❌ NX Domain database connection failed: " . $e->getMessage() . "\n";
}

echo "\nEnvironment variables:\n";
echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? 'not set') . "\n";
echo "DB_NAME: " . ($_ENV['DB_NAME'] ?? 'not set') . "\n";
echo "DB_USER: " . ($_ENV['DB_USER'] ?? 'not set') . "\n";

echo "\nDone.\n";
?>