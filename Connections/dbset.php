<?php
// phpinfo();
require __DIR__ . '/../vendor/autoload.php';

// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
// $dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
$dotenv = Dotenv\Dotenv::createMutable(__DIR__ . '/../');
$dotenv->load();
/* $dbHost = getenv('DEV_DB_HOST');

echo getenv('DEV_DB_HOST').'<br>'; // modern_mysql
echo $_ENV['DEV_DB_HOST'].'<br>'; // modern_mysql
echo $_ENV['DEV_DB_NAME'].'<br>'; // test

echo "host => {$dbHost}<br>"; */
// Connections/dbset.php
/* return [
    'host' => 'localhost',
    'dbname' => 'wu_shop',
    'username' => 'root',
    'password' => 'root',
]; */
// $config = require 'Connections/dbset.php';
// // $config = require __DIR__ . '/../config/database.php';  // 調整為正確路徑
// $conn = mysqli_connect($config['host'], $config['username'], $config['password'], $config['dbname']);
// 後台懶得改成用class的方式
if($_SERVER['HTTP_HOST'] == "127.0.0.1" || $_SERVER['HTTP_HOST'] == "localhost" || $_SERVER['HTTP_HOST'] == "mylocalhost:8082" || $_SERVER['HTTP_HOST'] == "127.0.0.1:8082"){
    return [
        'host' => $_ENV['DEV_DB_HOST'],
        'dbname' => $_ENV['DEV_DB_NAME'],
        'username' => $_ENV['DEV_DB_USER'],
        'password' => $_ENV['DEV_DB_PASS'],
    ];
}else{
    return [
        'host' => $_ENV['DB_HOST'],
        'dbname' => $_ENV['DB_NAME'],
        'username' => $_ENV['DB_USER'],
        'password' => $_ENV['DB_PASS'],
        // 'host' => getenv('DB_HOST'),
        // 'dbname' => getenv('DB_NAME'),
        // 'username' => getenv('DB_USER'),
        // 'password' => getenv('DB_PASS'),
    ];
}