<?php

// ============================================================
//  DR Medical Center — Cancelar Consulta
//  Recebe o ID da consulta e muda o status para 'cancelada'
// ============================================================

session_start();
include "conexao.php";

// Verifica se o paciente está logado
if (!isset($_SESSION['paciente_id'])) {
    header("Location: login.php");
    exit;
}

// Verifica se o formulário foi enviado via POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: paciente.php");
    exit;
}

$consulta_id = $_POST['consulta_id'];
$paciente_id = $_SESSION['paciente_id'];

// Segurança: garante que a consulta pertence ao paciente logado
// (evita que um paciente cancele consulta de outro)
$sql_verifica = "SELECT id FROM consultas 
                 WHERE id = '$consulta_id' 
                   AND paciente_id = '$paciente_id'
                   AND status IN ('agendada','confirmada')";
$resultado = mysqli_query($conn, $sql_verifica);

if (mysqli_num_rows($resultado) == 0) {
    header("Location: paciente.php?erro=Consulta nao encontrada ou ja cancelada");
    exit;
}

// Atualiza o status da consulta para 'cancelada'
$sql = "UPDATE consultas SET status = 'cancelada' WHERE id = '$consulta_id'";

if (mysqli_query($conn, $sql)) {
    header("Location: paciente.php?sucesso=Consulta cancelada com sucesso.");
} else {
    header("Location: paciente.php?erro=Erro ao cancelar consulta.");
}
exit;
