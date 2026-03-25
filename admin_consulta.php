<?php
// ============================================================
//  DR Medical Center — Ações CRUD para Consultas (Admin)
//  Ações: alterar_status | excluir
// ============================================================

session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: interno.php');
    exit;
}
include 'conexao.php';

$acao = $_POST['acao'] ?? '';

// ── CADASTRAR ────────────────────────────────────────────
if ($acao === 'cadastrar') {
    $paciente_id = (int) $_POST['paciente_id'];
    $medico_id   = (int) $_POST['medico_id'];
    $data        = mysqli_real_escape_string($conn, $_POST['data']);
    $hora        = mysqli_real_escape_string($conn, $_POST['hora']);
    $tipo        = mysqli_real_escape_string($conn, $_POST['tipo'] ?? 'presencial');
    $convenio    = mysqli_real_escape_string($conn, $_POST['convenio'] ?? 'Particular');
    $motivo      = mysqli_real_escape_string($conn, trim($_POST['motivo'] ?? ''));

    // Verifica conflito de horário
    $check = mysqli_query($conn, "SELECT id FROM consultas
        WHERE medico_id=$medico_id AND data='$data' AND hora='$hora:00'");
    if (mysqli_num_rows($check) > 0) {
        header('Location: interno.php?pagina=a-consultas&erro=' . urlencode('Horário já ocupado para este médico.'));
        exit;
    }

    $admin_id = $_SESSION['admin_id'];
    $sql = "INSERT INTO consultas (paciente_id, medico_id, data, hora, tipo, convenio, motivo, status, agendado_por)
            VALUES ($paciente_id, $medico_id, '$data', '$hora:00', '$tipo', '$convenio', '$motivo', 'confirmada', $admin_id)";

    if (mysqli_query($conn, $sql)) {
        header('Location: interno.php?pagina=a-consultas&ok=' . urlencode('Consulta agendada com sucesso.'));
    } else {
        header('Location: interno.php?pagina=a-consultas&erro=' . urlencode('Erro ao agendar: ' . mysqli_error($conn)));
    }
    exit;
}

// ── ALTERAR STATUS ────────────────────────────────────────
if ($acao === 'alterar_status') {
    $id     = (int) $_POST['id'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $permitidos = ['agendada', 'confirmada', 'realizada', 'cancelada'];
    if (!in_array($status, $permitidos)) {
        header('Location: interno.php?pagina=a-consultas&erro=' . urlencode('Status inválido.'));
        exit;
    }

    mysqli_query($conn, "UPDATE consultas SET status='$status' WHERE id=$id");
    header('Location: interno.php?pagina=a-consultas&ok=' . urlencode('Status da consulta atualizado.'));
    exit;
}

// ── EXCLUIR ───────────────────────────────────────────────
if ($acao === 'excluir') {
    $id = (int) $_POST['id'];
    mysqli_query($conn, "DELETE FROM consultas WHERE id=$id");
    header('Location: interno.php?pagina=a-consultas&ok=' . urlencode('Consulta excluída com sucesso.'));
    exit;
}

header('Location: interno.php');
exit;
