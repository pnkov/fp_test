<?php
declare(strict_types=1);
require_once __DIR__ . '/vendor/autoload.php';

$mysqli = @new mysqli('db', 'root', 'password', 'database', 3306);
if ($mysqli->connect_errno) {
    throw new Exception($mysqli->connect_error);
}

$db = new App\FpDbTest\Database($mysqli);
$test = new App\FpDbTest\DatabaseTest($db);
$test->testBuildQuery();

$testExt = new App\FpDbTest\DatabaseTestExtended($db);
$testExt->testBuildQuery();

exit('OK');
