<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'Class/Database.php';
require_once 'Class/User.php';
require_once 'Controllers/JobController.php';

$database = new Database();
$db = $database->getConnection(); 
$userModel = new User($db);

$controller = new JobController($db, $userModel);

$controller->handleRequest();
?>