<?php

// ============================================================
//  DR Medical Center — Processador de Login / Cadastro
//  Este arquivo NÃO tem HTML. Ele só recebe o POST,
//  verifica os dados e redireciona conforme o resultado.
//
//  Formulários em: login.html
//  Redireciona para: paciente.php (sucesso) ou login.html?erro=... (falha)
// ============================================================

session_start();
include "conexao.php";

// Se o paciente já está logado, vai direto para o portal
if (isset($_SESSION['paciente_id'])) {
    header("Location: paciente.php");
    exit;
}

// Só aceita requisições POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: login.html");
    exit;
}

$acao = $_POST['acao'] ?? '';

// ── Processamento do Login ────────────────────────────────
if ($acao === 'login') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    // Busca o paciente pelo e-mail
    $sql    = "SELECT * FROM pacientes WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $paciente = mysqli_fetch_assoc($result);

        // Verifica se a senha está correta
        if ($senha === $paciente['senha']) {
            // Login válido: salva os dados na sessão
            $_SESSION['paciente_id']   = $paciente['id'];
            $_SESSION['paciente_nome'] = $paciente['nome'];

            // Redireciona para o portal
            header("Location: paciente.php");
            exit;
        }
    }

    // Login inválido: volta para o formulário com mensagem de erro
    header("Location: login.html?erro=" . urlencode("E-mail ou senha inválidos."));
    exit;
}

// ── Processamento do Cadastro ─────────────────────────────
if ($acao === 'cadastro') {
    $nome       = $_POST['nome'];
    // Normaliza CPF: aceita com ou sem pontos e traço
    $cpf_raw    = $_POST['cpf'];
    $cpf        = preg_replace('/[^0-9]/', '', $cpf_raw); // remove tudo exceto dígitos
    // Formata para padrão 000.000.000-00 se tiver 11 dígitos
    if (strlen($cpf) === 11) {
        $cpf = substr($cpf,0,3).'.'.substr($cpf,3,3).'.'.substr($cpf,6,3).'-'.substr($cpf,9,2);
    }
    $email      = $_POST['email'];
    $telefone_raw = $_POST['telefone'] ?? '';
    $tel_digits   = preg_replace('/[^0-9]/', '', $telefone_raw);
    if (strlen($tel_digits) === 11) {
        $telefone = '(' . substr($tel_digits,0,2) . ') ' . substr($tel_digits,2,5) . '-' . substr($tel_digits,7,4);
    } elseif (strlen($tel_digits) === 10) {
        $telefone = '(' . substr($tel_digits,0,2) . ') ' . substr($tel_digits,2,4) . '-' . substr($tel_digits,6,4);
    } else {
        $telefone = $telefone_raw;
    }
    $nascimento = $_POST['nascimento'];
    $senha      = $_POST['senha']; // Senha em texto simples (padrão do sistema)

    // Verifica se o e-mail ou CPF já estão cadastrados
    $sql_verifica = "SELECT id FROM pacientes WHERE email = '$email' OR cpf = '$cpf'";
    $result_verifica = mysqli_query($conn, $sql_verifica);

    if (mysqli_num_rows($result_verifica) > 0) {
        header("Location: login.html?tab=register&erro=" . urlencode("E-mail ou CPF já cadastrado."));
        exit;
    }

    // Insere o novo paciente
    $sql = "INSERT INTO pacientes (nome, cpf, email, senha, telefone, nascimento)
            VALUES ('$nome', '$cpf', '$email', '$senha', '$telefone', '$nascimento')";

    if (mysqli_query($conn, $sql)) {
        // Busca o paciente recém-inserido pelo e-mail para garantir o ID correto
        $sql_novo = "SELECT * FROM pacientes WHERE email = '$email'";
        $result_novo = mysqli_query($conn, $sql_novo);
        $paciente_novo = mysqli_fetch_assoc($result_novo);

        if ($paciente_novo) {
            $_SESSION['paciente_id']   = $paciente_novo['id'];
            $_SESSION['paciente_nome'] = $paciente_novo['nome'];
            header("Location: paciente.php");
            exit;
        } else {
            header("Location: login.html?tab=register&erro=" . urlencode("Cadastro realizado, mas houve um erro ao entrar. Faça login."));
            exit;
        }
    } else {
        header("Location: login.html?tab=register&erro=" . urlencode("Erro ao cadastrar. Tente novamente."));
        exit;
    }
}

// Ação desconhecida: volta para o login
header("Location: login.html");
exit;
