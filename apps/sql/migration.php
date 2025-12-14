<?php

require_once __DIR__ . "/connection.php";

$migrationSql = [
    <<<MIGRATION1
        CREATE TABLE IF NOT EXISTS subscribes (
            id SERIAL NOT NULL PRIMARY KEY,
            app_id VARCHAR(36) NOT NULL,
            device_token TEXT NOT NULL
        );
    MIGRATION1,
    "CREATE INDEX subscribes_index ON subscribes (app_id);",
    "CREATE TABLE IF NOT EXISTS devices (app_id VARCHAR(36) PRIMARY KEY NOT NULL, device_token TEXT NOT NULL);",
    "CREATE INDEX devices_index ON devices (app_id);",
    "TRUNCATE TABLE subscribes;",
    "ALTER TABLE subscribes DROP COLUMN device_token, ADD COLUMN target_app_id VARCHAR(36) NOT NULL;",
    "ALTER TABLE devices ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;",
    "ALTER TABLE subscribes ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;",
];

$pdo = createConnection();

echo "RUN MIGRATION...\n";

// create migration history table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS migration_histories (index_number BIGINT PRIMARY KEY, executed_sql TEXT NOT NULL)"); 

// run migration sqls
foreach ($migrationSql as $index => $targetSql) {
    echo "RUN MIGRATION [$index]: ";

    // check already applied migration
    $statement = $pdo->prepare("SELECT * FROM migration_histories WHERE index_number = ?");
    $statement->execute([$index]);
    
    if (count($statement->fetchAll()) !== 0) {
        echo "SKIP.\n";
        continue;
    }

    $historyApplyStatement = $pdo->prepare("INSERT INTO migration_histories (index_number, executed_sql) VALUES (?, ?)");

    
    $pdo->exec($targetSql);
    $historyApplyStatement->execute([$index, $targetSql]);
    echo "APPLIED.\n";
}

echo "FINISHED MIGRATION.\n";