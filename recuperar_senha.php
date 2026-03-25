<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recuperar Senha — DR Medical Center</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php
session_start();
include 'conexao.php';

$tipo = $_GET['tipo'] ?? $_POST['tipo'] ?? 'paciente';

// Reenviar código — antes de qualquer limpeza de sessão
if (($_GET['reenviar'] ?? '') === '1' && isset($_SESSION['recuperacao_email'])) {
    $_SESSION['recuperacao_codigo'] = '1234';
    $_SESSION['recuperacao_expira'] = time() + 600;
    $_SESSION['recuperacao_etapa']  = 2;
}

// Acesso direto via GET limpa sessão anterior (exceto reenviar)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['reenviar'])) {
    unset($_SESSION['recuperacao_etapa'], $_SESSION['recuperacao_codigo'],
          $_SESSION['recuperacao_email'], $_SESSION['recuperacao_tipo'],
          $_SESSION['recuperacao_expira'], $_SESSION['recuperacao_contato']);
}

$etapa    = $_SESSION['recuperacao_etapa'] ?? 1;
$msg_erro = '';
$msg_ok   = '';

// Etapa 1 — Valida identidade e gera código
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['passo'] ?? '') === 'solicitar') {
    $email       = mysqli_real_escape_string($conn, trim($_POST['email']));
    $cpf_crm_raw = trim($_POST['cpf_crm']);
    $tabela      = ($tipo === 'paciente') ? 'pacientes' : 'medicos';
    $campo       = ($tipo === 'paciente') ? 'cpf' : 'crm';

    // Normaliza CPF do paciente (aceita com ou sem pontuação)
    if ($tipo === 'paciente') {
        $digits = preg_replace('/[^0-9]/', '', $cpf_crm_raw);
        if (strlen($digits) === 11) {
            $cpf_crm_raw = substr($digits,0,3).'.'.substr($digits,3,3).'.'.substr($digits,6,3).'-'.substr($digits,9,2);
        }
    }
    $cpf_crm = mysqli_real_escape_string($conn, $cpf_crm_raw);
    $result  = mysqli_query($conn, "SELECT * FROM $tabela WHERE email='$email' AND $campo='$cpf_crm'");

    if ($result && mysqli_num_rows($result) === 1) {
        $usuario = mysqli_fetch_assoc($result);
        $codigo  = '1234'; // Código fixo para demonstração
        $_SESSION['recuperacao_codigo']  = $codigo;
        $_SESSION['recuperacao_email']   = $email;
        $_SESSION['recuperacao_tipo']    = $tipo;
        $_SESSION['recuperacao_expira']  = time() + 600;
        $_SESSION['recuperacao_etapa']   = 2;
        $_SESSION['recuperacao_contato'] = $usuario['email'];
        $etapa = 2;
    } else {
        $label    = ($tipo === 'paciente') ? 'CPF' : 'CRM';
        $msg_erro = "E-mail ou $label não encontrados.";
        $etapa    = 1;
    }
}

// Etapa 2 — Verifica código
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['passo'] ?? '') === 'verificar') {
    $digitado = trim($_POST['codigo'] ?? '');
    $salvo    = $_SESSION['recuperacao_codigo'] ?? '';
    $expira   = $_SESSION['recuperacao_expira'] ?? 0;

    if (time() > $expira) {
        $msg_erro = 'O código expirou. Solicite um novo.';
        unset($_SESSION['recuperacao_etapa'], $_SESSION['recuperacao_codigo']);
        $etapa = 1;
    } elseif ($digitado === $salvo) {
        $_SESSION['recuperacao_etapa'] = 3;
        $etapa = 3;
    } else {
        $msg_erro = 'Código incorreto. Tente novamente.';
        $etapa = 2;
    }
}

// Etapa 3 — Define nova senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['passo'] ?? '') === 'nova_senha') {
    $nova     = trim($_POST['nova_senha']    ?? '');
    $confirma = trim($_POST['confirma_senha'] ?? '');
    $email_s  = $_SESSION['recuperacao_email'] ?? '';
    $tipo_s   = $_SESSION['recuperacao_tipo']  ?? $tipo;

    if (strlen($nova) < 6) {
        $msg_erro = 'A senha deve ter pelo menos 6 caracteres.';
        $etapa = 3;
    } elseif ($nova !== $confirma) {
        $msg_erro = 'As senhas não coincidem.';
        $etapa = 3;
    } else {
        $tabela = ($tipo_s === 'paciente') ? 'pacientes' : 'medicos';
        $e = mysqli_real_escape_string($conn, $email_s);
        $s = mysqli_real_escape_string($conn, $nova);
        mysqli_query($conn, "UPDATE $tabela SET senha='$s' WHERE email='$e'");
        unset($_SESSION['recuperacao_etapa'], $_SESSION['recuperacao_codigo'],
              $_SESSION['recuperacao_email'], $_SESSION['recuperacao_tipo'],
              $_SESSION['recuperacao_expira']);
        $msg_ok = 'Senha alterada com sucesso!';
        $etapa  = 4;
        $tipo   = $tipo_s;
    }
}



$eh_medico  = ($tipo === 'medico');
$link_back  = $eh_medico ? 'medico.php' : 'login.html';
$codigo_sim = $_SESSION['recuperacao_codigo'] ?? '';
$email_sim  = $_SESSION['recuperacao_contato'] ?? ($_SESSION['recuperacao_email'] ?? '');
?>

<div class="auth-shell">
  <div class="auth-panel <?= $eh_medico ? 'doctor-panel' : '' ?>">
    <div class="auth-brand">
      <img src="img/logo.png" alt="DR Medical Center" style="display:block;height:150px;margin:0 auto 8px auto;">
    </div>
    <div class="auth-panel-body">
      <h2 class="auth-panel-h2">Recuperar<em class="green-em">sua senha.</em></h2>
      <p class="auth-panel-desc">Informe seus dados para verificar sua identidade e redefinir o acesso.</p>
    </div>
  </div>

  <div class="auth-form-panel">
    <div class="auth-form-box">
      <a href="<?= $link_back ?>" style="display:inline-flex;align-items:center;gap:6px;font-size:.82rem;color:var(--ink-muted);text-decoration:none;margin-bottom:20px;">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
        Voltar ao login
      </a>

      <?php if ($msg_erro): ?>
        <div class="callout callout-red form-mb">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
          <div><?= htmlspecialchars($msg_erro) ?></div>
        </div>
      <?php endif; ?>
      <?php if ($msg_ok && $etapa !== 4): ?>
        <div class="callout callout-green form-mb">
          <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
          <div><?= htmlspecialchars($msg_ok) ?></div>
        </div>
      <?php endif; ?>

      <?php if ($etapa === 1): ?>
        <div class="auth-heading"><h3>Verificar Identidade</h3><p>Informe seu e-mail e <?= $eh_medico ? 'CRM' : 'CPF' ?> para confirmar quem é você.</p></div>
        <form method="POST">
          <input type="hidden" name="tipo"  value="<?= $tipo ?>">
          <input type="hidden" name="passo" value="solicitar">
          <div class="form-group form-mb">
            <label class="field-label">E-mail cadastrado</label>
            <div class="input-icon">
              <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              <input type="email" name="email" placeholder="seu@email.com" required>
            </div>
          </div>
          <div class="form-group form-mb">
            <label class="field-label"><?= $eh_medico ? 'CRM' : 'CPF' ?></label>
            <div class="input-icon">
              <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              <input type="text" name="cpf_crm" placeholder="<?= $eh_medico ? 'Ex: 123456/SP' : '000.000.000-00' ?>" required>
            </div>
          </div>
          <button type="submit" class="<?= $eh_medico ? 'btn-block-navy' : 'btn-block-green' ?>">Enviar Código de Verificação</button>
        </form>

      <?php elseif ($etapa === 2): ?>
        <div class="auth-heading"><h3>Código de Verificação</h3><p>Código enviado para <strong><?= htmlspecialchars($email_sim) ?></strong>.</p></div>
        <div class="callout callout-green form-mb">
          <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
          <div>Um código de verificação foi enviado para <strong><?= htmlspecialchars($email_sim) ?></strong>.</div>
        </div>
        <form method="POST">
          <input type="hidden" name="tipo"  value="<?= $tipo ?>">
          <input type="hidden" name="passo" value="verificar">
          <div class="form-group form-mb">
            <label class="field-label">Digite o código de 6 dígitos</label>
            <input type="text" name="codigo" maxlength="4" placeholder="0000" required
                   style="font-size:1.6rem;letter-spacing:.35em;text-align:center;font-family:'Fraunces',serif;padding:14px;">
          </div>
          <p style="font-size:.75rem;color:var(--ink-muted);margin-bottom:16px;">
            Expira em 10 minutos. &nbsp;
            <a href="recuperar_senha.php?tipo=<?= $tipo ?>&reenviar=1" style="color:var(--green);text-decoration:none;">Reenviar código</a>
          </p>
          <button type="submit" class="<?= $eh_medico ? 'btn-block-navy' : 'btn-block-green' ?>">Confirmar Código</button>
        </form>

      <?php elseif ($etapa === 3): ?>
        <div class="auth-heading"><h3>Nova Senha</h3><p>Código verificado. Defina sua nova senha de acesso.</p></div>
        <form method="POST">
          <input type="hidden" name="tipo"  value="<?= $tipo ?>">
          <input type="hidden" name="passo" value="nova_senha">
          <div class="form-group form-mb">
            <label class="field-label">Nova senha</label>
            <div class="input-icon">
              <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              <input type="password" name="nova_senha" placeholder="Mínimo 6 caracteres" required minlength="6">
            </div>
          </div>
          <div class="form-group form-mb">
            <label class="field-label">Confirmar senha</label>
            <div class="input-icon">
              <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              <input type="password" name="confirma_senha" placeholder="Repita a senha" required minlength="6">
            </div>
          </div>
          <button type="submit" class="<?= $eh_medico ? 'btn-block-navy' : 'btn-block-green' ?>">Salvar Nova Senha</button>
        </form>

      <?php elseif ($etapa === 4): ?>
        <div style="text-align:center;padding:32px 0;">
          <div style="width:68px;height:68px;border-radius:50%;background:rgba(40,92,62,.12);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
            <svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--green)" stroke-width="2.2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <h3 style="color:var(--navy);margin-bottom:8px;">Senha alterada!</h3>
          <p style="color:var(--ink-muted);font-size:.88rem;margin-bottom:28px;">Sua senha foi redefinida com sucesso. Já pode fazer login.</p>
          <a href="<?= $link_back ?>" class="<?= $eh_medico ? 'btn-block-navy' : 'btn-block-green' ?>"
             style="text-decoration:none;display:block;text-align:center;padding:13px;border-radius:9px;">
            Ir para o Login
          </a>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

</body>
</html>
