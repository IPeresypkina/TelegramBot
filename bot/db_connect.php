<?php
/*подключение к базе данных*/

$host = "localhost";
$password = "пароль";
$username = "имя юзера";
$dbname = "имя базы";

$pdo = new PDO("mysql:dbname={$dbname};host={$host};charset=utf8", $username, $password);
