<?php
// ============================================================
//  DR Medical Center — Confirmação de Presença pelo Paciente
// ============================================================

session_start();
if (!isset($_SESSION['paciente_id'])) {
    header('Location: login.html');
    exit;
}
include 'conexao.php';

$consulta_id = (int) ($_POST['consulta_id'] ?? 0);
$paciente_id = $_SESSION['paciente_id'];

// Verifica se a consulta pertence ao paciente e está "agendada"
$sql    = "SELECT id FROM consultas WHERE id=$consulta_id AND paciente_id=$paciente_id AND status='agendada'";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) === 1) {
    mysqli_query($conn, "UPDATE consultas SET status='confirmada' WHERE id=$consulta_id");
    header('Location: paciente.php?sucesso=' . urlencode('Presença confirmada com sucesso!'));
} else {
    header('Location: paciente.php?erro=' . urlencode('Não foi possível confirmar esta consulta.'));
}
exit;
