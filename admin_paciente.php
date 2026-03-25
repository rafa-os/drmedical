<?php
// ============================================================
//  DR Medical Center — Ações CRUD para Pacientes (Admin)
//  Ações: editar | excluir
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
    $nome       = mysqli_real_escape_string($conn, trim($_POST['nome']));
    $cpf        = mysqli_real_escape_string($conn, trim($_POST['cpf']));
    $email      = mysqli_real_escape_string($conn, trim($_POST['email']));
    $tel_raw  = trim($_POST['telefone'] ?? '');
    $tel_dig  = preg_replace('/[^0-9]/', '', $tel_raw);
    if (strlen($tel_dig) === 11) {
        $telefone = '(' . substr($tel_dig,0,2) . ') ' . substr($tel_dig,2,5) . '-' . substr($tel_dig,7,4);
    } elseif (strlen($tel_dig) === 10) {
        $telefone = '(' . substr($tel_dig,0,2) . ') ' . substr($tel_dig,2,4) . '-' . substr($tel_dig,6,4);
    } else {
        $telefone = mysqli_real_escape_string($conn, $tel_raw);
    }
    $telefone = mysqli_real_escape_string($conn, $telefone);
    $nascimento = mysqli_real_escape_string($conn, trim($_POST['nascimento'] ?? ''));
    $senha      = mysqli_real_escape_string($conn, trim($_POST['nova_senha']));

    if (empty($senha)) {
        header('Location: interno.php?pagina=a-pacientes&erro=' . urlencode('A senha é obrigatória no cadastro.'));
        exit;
    }

    $check = mysqli_query($conn, "SELECT id FROM pacientes WHERE email='$email' OR cpf='$cpf'");
    if (mysqli_num_rows($check) > 0) {
        header('Location: interno.php?pagina=a-pacientes&erro=' . urlencode('E-mail ou CPF já cadastrado.'));
        exit;
    }

    $admin_id = $_SESSION['admin_id'];
    $sql = "INSERT INTO pacientes (nome, cpf, email, senha, telefone, nascimento, criado_por)
            VALUES ('$nome','$cpf','$email','$senha','$telefone',
                    " . ($nascimento ? "'$nascimento'" : "NULL") . ", '$admin_id')";

    if (mysqli_query($conn, $sql)) {
        header('Location: interno.php?pagina=a-pacientes&ok=' . urlencode('Paciente cadastrado com sucesso.'));
    } else {
        header('Location: interno.php?pagina=a-pacientes&erro=' . urlencode('Erro ao cadastrar: ' . mysqli_error($conn)));
    }
    exit;
}

// ── EDITAR ────────────────────────────────────────────────
if ($acao === 'editar') {
    $id         = (int) $_POST['id'];
    $nome       = mysqli_real_escape_string($conn, trim($_POST['nome']));
    $cpf        = mysqli_real_escape_string($conn, trim($_POST['cpf']));
    $email      = mysqli_real_escape_string($conn, trim($_POST['email']));
    $tel_raw  = trim($_POST['telefone'] ?? '');
    $tel_dig  = preg_replace('/[^0-9]/', '', $tel_raw);
    if (strlen($tel_dig) === 11) {
        $telefone = '(' . substr($tel_dig,0,2) . ') ' . substr($tel_dig,2,5) . '-' . substr($tel_dig,7,4);
    } elseif (strlen($tel_dig) === 10) {
        $telefone = '(' . substr($tel_dig,0,2) . ') ' . substr($tel_dig,2,4) . '-' . substr($tel_dig,6,4);
    } else {
        $telefone = mysqli_real_escape_string($conn, $tel_raw);
    }
    $telefone = mysqli_real_escape_string($conn, $telefone);
    $nascimento = mysqli_real_escape_string($conn, trim($_POST['nascimento'] ?? ''));
    $nova_senha = trim($_POST['nova_senha'] ?? '');

    $set_senha = $nova_senha ? ", senha='$nova_senha'" : '';

    $sql = "UPDATE pacientes SET
                nome='$nome', cpf='$cpf', email='$email',
                telefone='$telefone', nascimento='$nascimento' $set_senha
            WHERE id=$id";

    if (mysqli_query($conn, $sql)) {
        header('Location: interno.php?pagina=a-pacientes&ok=' . urlencode('Dados do paciente atualizados.'));
    } else {
        header('Location: interno.php?pagina=a-pacientes&erro=' . urlencode('Erro ao editar: ' . mysqli_error($conn)));
    }
    exit;
}

// ── EXCLUIR ───────────────────────────────────────────────
if ($acao === 'excluir') {
    $id = (int) $_POST['id'];

    // Exclui consultas vinculadas primeiro
    mysqli_query($conn, "DELETE FROM consultas WHERE paciente_id=$id");
    mysqli_query($conn, "DELETE FROM pacientes WHERE id=$id");

    header('Location: interno.php?pagina=a-pacientes&ok=' . urlencode('Paciente e suas consultas foram excluídos.'));
    exit;
}

header('Location: interno.php');
exit;
