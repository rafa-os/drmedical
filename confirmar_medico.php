<?php

// ============================================================
//  DR Medical Center — Confirmar / Cancelar Consulta (Médico)
//  O médico pode confirmar ou cancelar consultas agendadas
// ============================================================

session_start();
include "conexao.php";

// Verifica se o médico está logado
if (!isset($_SESSION['medico_id'])) {
    header("Location: medico.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: medico.php");
    exit;
}

$consulta_id = $_POST['consulta_id'];
$acao        = $_POST['acao']; // 'confirmar' ou 'cancelar'
$medico_id   = $_SESSION['medico_id'];

// Garante que a consulta pertence ao médico logado
$sql_verifica = "SELECT id FROM consultas 
                 WHERE id = '$consulta_id' AND medico_id = '$medico_id'";
$resultado = mysqli_query($conn, $sql_verifica);

if (mysqli_num_rows($resultado) == 0) {
    header("Location: medico.php?erro=Consulta nao encontrada");
    exit;
}

// Define o novo status com base na ação
if ($acao == 'confirmar') {
    $novo_status = 'confirmada';
} elseif ($acao == 'cancelar') {
    $novo_status = 'cancelada';
} else {
    header("Location: medico.php?erro=Acao invalida");
    exit;
}

// Atualiza o status da consulta
$sql = "UPDATE consultas SET status = '$novo_status' WHERE id = '$consulta_id'";

if (mysqli_query($conn, $sql)) {
    header("Location: medico.php?sucesso=Status da consulta atualizado!");
} else {
    header("Location: medico.php?erro=Erro ao atualizar consulta.");
}
exit;
