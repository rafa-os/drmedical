<?php
// ============================================================
//  DR Medical Center — Atualização de Perfil do Paciente
// ============================================================

session_start();
if (!isset($_SESSION['paciente_id'])) {
    header('Location: login.html');
    exit;
}
include 'conexao.php';

$id         = $_SESSION['paciente_id'];
$email      = mysqli_real_escape_string($conn, trim($_POST['email']));
$nova_senha = trim($_POST['nova_senha'] ?? '');

// Normaliza telefone
$tel_raw = trim($_POST['telefone'] ?? '');
$tel_dig = preg_replace('/[^0-9]/', '', $tel_raw);
if (strlen($tel_dig) === 11) {
    $telefone = '(' . substr($tel_dig,0,2) . ') ' . substr($tel_dig,2,5) . '-' . substr($tel_dig,7,4);
} elseif (strlen($tel_dig) === 10) {
    $telefone = '(' . substr($tel_dig,0,2) . ') ' . substr($tel_dig,2,4) . '-' . substr($tel_dig,6,4);
} else {
    $telefone = $tel_raw;
}
$telefone = mysqli_real_escape_string($conn, $telefone);

$set_senha = $nova_senha ? ", senha='" . mysqli_real_escape_string($conn, $nova_senha) . "'" : '';

$sql = "UPDATE pacientes SET
            email='$email', telefone='$telefone' $set_senha
        WHERE id=$id";

if (mysqli_query($conn, $sql)) {
    header('Location: paciente.php?sucesso=' . urlencode('Perfil atualizado com sucesso.'));
} else {
    header('Location: paciente.php?erro=' . urlencode('Erro ao atualizar: ' . mysqli_error($conn)));
}
exit;
