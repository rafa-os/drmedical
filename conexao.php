<?php
$host  = 'crossover.proxy.rlwy.net';
$user  = 'root';
$pass  = 'kMYvxyMjidwogcZXzQYlEHwEsugJkTHW';
$banco = 'railway';
$port  = 14822;

$conn = mysqli_connect($host, $user, $pass, $banco, $port);

if (!$conn) {
    die('Erro ao conectar ao banco de dados: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');
