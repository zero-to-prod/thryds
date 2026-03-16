<?php

class Database {
    public function query(string $sql, bool $buffered = true, int $timeout = 30): void {}
}

$sql = 'SELECT 1';
$db = new Database();
$db->query($sql, true, 100);

?>
-----
<?php

class Database {
    public function query(string $sql, bool $buffered = true, int $timeout = 30): void {}
}

$sql = 'SELECT 1';
$db = new Database();
$db->query($sql, buffered: true, timeout: 100);

?>
