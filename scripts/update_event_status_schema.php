<?php

// Ensure script is run from CLI
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

require_once __DIR__ . '/../src/Database.php';
// config/database.php is included by Database.php, no need to require it again here.

echo "Attempting to get database instance...\n";

try {
    // Get the singleton instance and the PDO connection
    $dbInstance = Database::getInstance();
    $pdo = $dbInstance->getConnection(); // Use getConnection() method
    echo "Database connection successful.\n";

    // SQL statement to add the status column (idempotent in SQLite if column exists)
    // Note: This script ensures the column exists. The CHECK constraint modification
    // is primarily handled in the schema.sql file for documentation and initial setup.
    // Running this ALTER TABLE won't modify an existing column's constraint.
    $sql = "ALTER TABLE events ADD COLUMN status TEXT DEFAULT 'open' CHECK(status IN ('open', 'closed'));";

    echo "Executing SQL: " . $sql . "\n";

    // Execute the SQL statement
    $stmt = $pdo->prepare($sql);
    
    // Execute and check for errors
    // SQLite's ADD COLUMN doesn't throw an error if the column already exists,
    // but we'll wrap in try-catch for other potential issues.
    if ($stmt->execute()) {
        echo "Successfully ensured 'status' column exists in 'events' table.\n";
    } else {
        // This part might not be reached for "column already exists" in SQLite,
        // but is good practice for other potential errors.
        $errorInfo = $stmt->errorInfo();
        echo "Failed to execute SQL. Error: " . ($errorInfo[2] ?? 'Unknown error') . "\n";
    }

} catch (PDOException $e) {
    // Catch potential connection errors or other PDO exceptions
    echo "Database operation failed: " . $e->getMessage() . "\n";
    exit(1); // Exit with an error code
} catch (Exception $e) {
    // Catch any other general errors
    echo "An unexpected error occurred: " . $e->getMessage() . "\n";
    exit(1); // Exit with an error code
}

exit(0); // Exit successfully

?>