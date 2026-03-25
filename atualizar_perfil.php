<?php
// ============================================================
//  DR Medical Center — Atualização de Perfil do Médico
// ============================================================

session_start();
if (!isset($_SESSION['medico_id'])) {
    header('Location: medico.php');
    exit;
}
include 'conexao.php';

$id         = $_SESSION['medico_id'];
$email      = mysqli_real_escape_string($conn, trim($_POST['email']));
$bio        = mysqli_real_escape_string($conn, trim($_POST['bio'] ?? ''));
$nova_senha = trim($_POST['nova_senha'] ?? '');

$set_senha = $nova_senha ? ", senha='$nova_senha'" : '';

$sql = "UPDATE medicos SET email='$email', bio='$bio' $set_senha WHERE id=$id";

if (mysqli_query($conn, $sql)) {
    $_SESSION['medico_email'] = $email;
    header('Location: medico.php?sucesso=' . urlencode('Perfil atualizado com sucesso.'));
} else {
    header('Location: medico.php?erro=' . urlencode('Erro ao atualizar: ' . mysqli_error($conn)));
}
exit;
