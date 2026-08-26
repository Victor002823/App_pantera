<?php
require_once __DIR__ . '/rastreo_conexion.php';
require_once __DIR__ . '/model/RastreoModel.php';
require_once __DIR__ . '/controller/RastreoController.php';

$token = $_GET['token'] ?? '';
$controller = new RastreoController($pdo);
$controller->mostrarPagina($token);
