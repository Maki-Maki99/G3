<?php
class DatabaseConnection {
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $database = 'coffee';
    private $connection;

    public function __construct() {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $this->connection = new mysqli(
            $this->host,
            $this->username,
            $this->password,
            $this->database
        );

        $this->connection->set_charset("utf8mb4");
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql) {
        try {
            return $this->connection->query($sql);
        } catch (mysqli_sql_exception $e) {
            // Show exact error + query
            die(
                "SQL Error: " . htmlspecialchars($e->getMessage()) .
                "<br><br>Query:<br><pre>" . htmlspecialchars($sql) . "</pre>"
            );
        }
    }

    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }

    public function close() {
        $this->connection->close();
    }
}
?>
