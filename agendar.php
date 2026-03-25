<?php

// ============================================================
//  DR Medical Center — Agendar Consulta
//  Recebe os dados do formulário de agendamento e salva no banco
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

// Pega os dados enviados pelo formulário
$paciente_id = $_SESSION['paciente_id'];
$medico_id   = $_POST['medico_id'];
$data        = $_POST['data'];
$hora        = $_POST['hora'];
$tipo        = $_POST['tipo'];
$convenio    = $_POST['convenio'];
$motivo      = $_POST['motivo'];

// Validação básica: todos os campos obrigatórios devem estar preenchidos
if (empty($medico_id) || empty($data) || empty($hora)) {
    header("Location: paciente.php?erro=Selecione medico, data e horario");
    exit;
}

// Regra de negócio: não permite agendar consulta em data passada
if ($data < date('Y-m-d')) {
    header("Location: paciente.php?erro=Nao e possivel agendar em data passada");
    exit;
}

// Regra de negócio: verifica se o horário já está ocupado para este médico
$sql_verifica = "SELECT id FROM consultas 
                 WHERE medico_id = '$medico_id' 
                   AND data = '$data' 
                   AND hora = '$hora' 
                   AND status != 'cancelada'";
$resultado = mysqli_query($conn, $sql_verifica);

if (mysqli_num_rows($resultado) > 0) {
    header("Location: paciente.php?erro=Horario indisponivel. Escolha outro.");
    exit;
}

// Insere a consulta no banco de dados
$sql = "INSERT INTO consultas (paciente_id, medico_id, data, hora, tipo, convenio, motivo, status)
        VALUES ('$paciente_id', '$medico_id', '$data', '$hora', '$tipo', '$convenio', '$motivo', 'agendada')";

if (mysqli_query($conn, $sql)) {
    // Agendamento realizado com sucesso — volta para o portal do paciente
    header("Location: paciente.php?sucesso=Consulta agendada com sucesso!");
} else {
    header("Location: paciente.php?erro=Erro ao agendar consulta. Tente novamente.");
}
exit;
