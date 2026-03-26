<?php
// ============================================================
//  DR Medical Center — Conexão com o Banco de Dados
//  Configuração para XAMPP (ambiente local)
// ============================================================

$host  = 'mysql.railway.internal';
$user  = 'root';
$pass  = 'kMYvxyMjidwogcZXzQYlEHwEsugJkTHW';
$banco = 'railway';

$conn = mysqli_connect($host, $user, $pass, $banco);

if (!$conn) {
    die('Erro ao conectar ao banco de dados: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');
