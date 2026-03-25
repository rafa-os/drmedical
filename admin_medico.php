<?php
// ============================================================
//  DR Medical Center — Ações CRUD para Médicos (Admin)
//  Recebe POST com campo 'acao':
//    cadastrar | editar | toggle_ativo | excluir
// ============================================================

session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: interno.php');
    exit;
}
include 'conexao.php';

$acao = $_POST['acao'] ?? '';

// ── CADASTRAR ─────────────────────────────────────────────
if ($acao === 'cadastrar') {
    $nome            = mysqli_real_escape_string($conn, trim($_POST['nome']));
    $crm             = mysqli_real_escape_string($conn, trim($_POST['crm']));
    $especialidade_id = (int) $_POST['especialidade_id'];
    $email           = mysqli_real_escape_string($conn, trim($_POST['email']));
    $senha           = mysqli_real_escape_string($conn, trim($_POST['senha']));
    $bio             = mysqli_real_escape_string($conn, trim($_POST['bio'] ?? ''));
    $avaliacao       = 4.9;

    // Verifica duplicidade
    $check = mysqli_query($conn, "SELECT id FROM medicos WHERE crm='$crm' OR email='$email'");
    if (mysqli_num_rows($check) > 0) {
        header('Location: interno.php?pagina=a-medicos&erro=' . urlencode('CRM ou e-mail já cadastrado.'));
        exit;
    }

    $admin_id = $_SESSION['admin_id'];
    $sql = "INSERT INTO medicos (nome, crm, especialidade_id, email, senha, bio, ativo, criado_por)
            VALUES ('$nome','$crm','$especialidade_id','$email','$senha','$bio', 1, '$admin_id')";

    if (mysqli_query($conn, $sql)) {
        header('Location: interno.php?pagina=a-medicos&ok=' . urlencode('Médico cadastrado com sucesso.'));
    } else {
        header('Location: interno.php?pagina=a-medicos&erro=' . urlencode('Erro ao cadastrar: ' . mysqli_error($conn)));
    }
    exit;
}

// ── EDITAR ────────────────────────────────────────────────
if ($acao === 'editar') {
    $id              = (int) $_POST['id'];
    $nome            = mysqli_real_escape_string($conn, trim($_POST['nome']));
    $crm             = mysqli_real_escape_string($conn, trim($_POST['crm']));
    $especialidade_id = (int) $_POST['especialidade_id'];
    $email           = mysqli_real_escape_string($conn, trim($_POST['email']));
    $bio             = mysqli_real_escape_string($conn, trim($_POST['bio'] ?? ''));
    $nova_senha      = trim($_POST['nova_senha'] ?? '');

    $set_senha = $nova_senha ? ", senha='$nova_senha'" : '';

    $sql = "UPDATE medicos SET
                nome='$nome', crm='$crm', especialidade_id='$especialidade_id',
                email='$email', bio='$bio' $set_senha
            WHERE id='$id'";

    if (mysqli_query($conn, $sql)) {
        header('Location: interno.php?pagina=a-medicos&ok=' . urlencode('Dados do médico atualizados.'));
    } else {
        header('Location: interno.php?pagina=a-medicos&erro=' . urlencode('Erro ao editar: ' . mysqli_error($conn)));
    }
    exit;
}

// ── ATIVAR / DESATIVAR ────────────────────────────────────
if ($acao === 'toggle_ativo') {
    $id     = (int) $_POST['id'];
    $ativo  = (int) $_POST['ativo']; // valor atual — inverte
    $novo   = $ativo ? 0 : 1;
    $texto  = $novo ? 'reativado' : 'desativado';

    mysqli_query($conn, "UPDATE medicos SET ativo=$novo WHERE id=$id");
    header('Location: interno.php?pagina=a-medicos&ok=' . urlencode("Médico $texto com sucesso."));
    exit;
}

// ── EXCLUIR ───────────────────────────────────────────────
if ($acao === 'excluir') {
    $id = (int) $_POST['id'];

    // Verifica se tem consultas vinculadas
    $check = mysqli_query($conn, "SELECT COUNT(*) AS total FROM consultas WHERE medico_id=$id");
    $row   = mysqli_fetch_assoc($check);

    if ($row['total'] > 0) {
        header('Location: interno.php?pagina=a-medicos&erro=' . urlencode('Não é possível excluir: médico possui consultas registradas. Desative-o ao invés de excluir.'));
        exit;
    }

    mysqli_query($conn, "DELETE FROM medicos WHERE id=$id");
    header('Location: interno.php?pagina=a-medicos&ok=' . urlencode('Médico excluído com sucesso.'));
    exit;
}

header('Location: interno.php');
exit;
