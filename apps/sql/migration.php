<?php

require_once __DIR__ . "/connection.php";

$migrationSql = [
    <<<MIGRATION1
        CREATE TABLE IF NOT EXISTS subscribes (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            app_id VARCHAR(36) NOT NULL,
            device_token TEXT NOT NULL
        );
    MIGRATION1,
];

$pdo = createConnection();

// create migration history table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS migration_histories (index_number BIGINT PRIMARY KEY, `sql` TEXT NOT NULL)"); 

// run migration sqls
foreach ($migrationSql as $index => $targetSql) {
    // check already applied migration
    $statement = $pdo->prepare("SELECT * FROM migration_histories WHERE index_number = ?");
    $statement->execute([$index]);
    
    if ($statement->rowCount() === 0) {
        continue;
    }

    $historyApplyStatement = $pdo->prepare("INSERT INTO migration_histories (index_number, `sql`) VALUES (?, ?)");

    echo "Run migration[$index]: $targetSql\n";
    $pdo->exec($targetSql);
    $historyApplyStatement->execute([$index, $targetSql]);
}