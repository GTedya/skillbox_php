<?php

require_once 'Controllers/Funds.php';
require_once 'Controllers/Users.php';
require_once 'Controllers/File.php';
use Controllers\Funds;
use Controllers\Users;
use Controllers\File;

$pdo = new PDO('mysql:host=mysql;dbname=skill;charset=utf8', 'root', 'root');
$funds = new Funds();
$file = new File();
$user = new Users();