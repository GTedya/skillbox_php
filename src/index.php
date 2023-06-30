<?php

require_once 'autoload.php';
header('Content-type: application/json; charset=utf-8');
$pathPieces = explode('/', $_SERVER['REQUEST_URI']);

$param = '';
$email = '';
if (isset($_GET['email'])) {
    $param = (array_key_first($_GET));
    $email = $_GET['email'];
}

if (count($pathPieces) == 6 && $pathPieces[3] == 'share') {
    $userId = array_pop($pathPieces);
}
$id = end($pathPieces);

$urlList = [
    '/funds/' => ['GET' => 'Funds::list()', 'POST' => 'Funds::add()'],
    '/users/list' => ['GET' => array('Controllers\Users', 'list')],
    "/files/list" => ['GET' => array('Controllers\File', 'list')],
    "/files/add" => ['POST' => array('Controllers\File', 'add')],
    "/directories/add" => ['POST' => array('Controllers\File', 'makedir')],
    "/directories/rename" => ['PUT' => array('Controllers\File', 'renameDir')],
    "/directories/get/$id" => ['GET' => array('Controllers\File', 'getDirectories')],
    "/files/get/$id" => ['GET' => array('Controllers\File', 'get')],
    "/files/rename/$id" => ['PUT' => array('Controllers\File', 'rename')],
    "/users/get/$id" => ['GET' => array('Controllers\Users', 'getById')],
    "/admin/users/get/$id" => ['GET' => array('Controllers\Users', 'getByIdAdmin')],
    "/admin/users/delete/$id" => ['DELETE' => array('Controllers\Users', 'adminDelete')],
    "/files/delete/$id" => ['DELETE' => array('Controllers\File', 'delete')],
    "/directories/delete/$id" => ['DELETE' => array('Controllers\File', 'deleteDir')],
    "/users/register" => ['POST' => array('Controllers\Users', 'register')],
    "/users/login" => ['POST' => array('Controllers\Users', 'login')],
    "/users/logout" => ['GET' => array('Controllers\Users', 'logout')],
    "/users/reset_password?$param=$email" => ['GET' => array('Controllers\Users', 'reset_password')],
    "/users/search/$id" => ['GET' => array('Controllers\Users', 'search')],
    "/users/update" => ['PUT' => array('Controllers\Users', 'update')],
    "/admin/users/update/$id" => ['PUT' => array('Controllers\Users', 'adminUpdate')],
    "/admin/users/list" => ['GET' => array('Controllers\Users', 'listForAdmin')],
    "/files/share/$id" => ['GET' => array('Controllers\File', 'getShare')],
    "/files/share/$id/$userId" => ['PUT' => array('Controllers\File', 'shareWith')],
    "/files/share/$id/$userId" => ['DELETE' => array('Controllers\File', 'deleteShare')],
];

$method = $_SERVER['REQUEST_METHOD'];
$path = str_replace('/index.php', '', $_SERVER['REQUEST_URI']);

if (array_key_exists($path, $urlList)) {
    print_r(json_encode(call_user_func($urlList[$path][$method])));
};

