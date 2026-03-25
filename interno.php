<?php
// ============================================================
//  DR Medical Center — Painel Administrativo
//  Acesso por URL direta (sem link público).
//  Somente administradores cadastrados na tabela 'administradores'.
// ============================================================

session_start();
include "conexao.php";

$erro_login = '';

// ── Processamento do Login ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] == 'POST' && ($_POST['acao'] ?? '') === 'login') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $sql    = "SELECT * FROM administradores WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $admin = mysqli_fetch_assoc($result);

        if ($senha === $admin['senha']) {
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_nome'] = $admin['nome'];

            header("Location: interno.php");
            exit;
        }
    }

    $erro_login = "E-mail ou senha incorretos.";
}

// ── Dados do sistema (só carrega se estiver logado) ───────
if (isset($_SESSION['admin_id'])) {
    $hoje = date('Y-m-d');
    $mes  = date('Y-m');

    // Estatísticas gerais
    $total_pacientes = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM pacientes"))[0];
    $total_medicos   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM medicos "))[0];
    $total_hoje      = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM consultas WHERE data = '$hoje' AND status != 'cancelada'"))[0];
    $total_mes       = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM consultas WHERE DATE_FORMAT(data,'%Y-%m') = '$mes'"))[0];

    // Todas as consultas com dados completos (últimas 50)
    $sql_c = "SELECT c.*, p.nome AS paciente_nome, m.nome AS medico_nome, e.nome AS especialidade
              FROM consultas c
              JOIN pacientes p ON c.paciente_id = p.id
              JOIN medicos m   ON c.medico_id   = m.id
              JOIN especialidades e ON m.especialidade_id = e.id
              ORDER BY c.data DESC, c.hora DESC
              LIMIT 50";
    $result_c = mysqli_query($conn, $sql_c);
    $consultas = [];
    while ($row = mysqli_fetch_assoc($result_c)) {
        $consultas[] = $row;
    }

    // Lista de médicos
    $sql_m = "SELECT m.*, e.nome AS especialidade
              FROM medicos m
              JOIN especialidades e ON m.especialidade_id = e.id
              ORDER BY m.nome";
    $result_m = mysqli_query($conn, $sql_m);
    $medicos  = [];
    while ($row = mysqli_fetch_assoc($result_m)) {
        $medicos[] = $row;
    }

    // Lista de pacientes
    $sql_p = "SELECT * FROM pacientes ORDER BY nome";
    $result_p = mysqli_query($conn, $sql_p);
    $pacientes = [];
    while ($row = mysqli_fetch_assoc($result_p)) {
        $pacientes[] = $row;
    }

    // Especialidades (para formulário de cadastro de médico)
    $result_esp = mysqli_query($conn, "SELECT * FROM especialidades ORDER BY nome");
    $especialidades = [];
    while ($row = mysqli_fetch_assoc($result_esp)) {
        $especialidades[] = $row;
    }

    // Página ativa (vinda de redirect após ação)
    $pagina_ativa = $_GET['pagina'] ?? 'a-home';
    $msg_ok   = $_GET['ok']   ?? '';
    $msg_erro = $_GET['erro'] ?? '';

    // Consultas sem limite para a página de consultas
    $sql_all = "SELECT c.*, p.nome AS paciente_nome, m.nome AS medico_nome, e.nome AS especialidade
                FROM consultas c
                JOIN pacientes p ON c.paciente_id = p.id
                JOIN medicos m   ON c.medico_id   = m.id
                JOIN especialidades e ON m.especialidade_id = e.id
                ORDER BY c.data DESC, c.hora DESC";
    $result_all = mysqli_query($conn, $sql_all);
    $todas_consultas = [];
    while ($row = mysqli_fetch_assoc($result_all)) {
        $todas_consultas[] = $row;
    }
}

// Função auxiliar: classe do badge por status
function badgeStatus($status) {
    switch ($status) {
        case 'confirmada': return 'badge-green';
        case 'realizada':  return 'badge-violet';
        case 'cancelada':  return 'badge-red';
        default:           return 'badge-amber';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Painel Administrativo — DR Medical Center</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php if (!isset($_SESSION['admin_id'])): ?>
<!-- ══════════════════ TELA DE LOGIN ADMIN ══════════════════ -->
<div class="int-auth">
  <div class="int-auth-box">

    <!-- Logo -->
    <div class="int-auth-brand">
      <img src="img/logo.png" alt="DR Medical Center" style="display:block; height:96px; margin:0 auto;">
      <div class="int-auth-brand-name">
        <span>Sistema Administrativo</span>
      </div>
    </div>

    <!-- Badge de área restrita -->
    <div class="badge-restrito">
      <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
      Área Restrita
    </div>

    <div class="int-auth-heading">
      <h3>Acesso Administrativo</h3>
      <p>Área exclusiva para funcionários autorizados. Credenciais criadas pelo administrador do sistema.</p>
    </div>

    <!-- Mensagem de erro -->
    <?php if ($erro_login): ?>
      <div class="callout callout-red form-mb">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
        <div><?= htmlspecialchars($erro_login) ?></div>
      </div>
    <?php endif; ?>

    <!-- Formulário de login -->
    <form method="POST" action="interno.php">
      <input type="hidden" name="acao" value="login">
      <div class="form-group form-mb">
        <label class="field-label">E-mail institucional</label>
        <input type="email" name="email" placeholder="admin@drmedical.com" required>
      </div>
      <div class="form-group form-mb">
        <label class="field-label">Senha</label>
        <input type="password" name="senha" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn-block-navy">Entrar no Sistema</button>
    </form>

    <div style="text-align:center; margin-top:16px;">
      <a href="index.html" style="font-size:.78rem; color:var(--ink-muted); text-decoration:none;">← Voltar ao site</a>
    </div>

  </div>
</div>

<?php else: ?>
<!-- ══════════════════ PAINEL ADMIN ══════════════════ -->
<div class="app-shell">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <img src="img/logo.png" alt="DR Medical Center" style="display:block; height:72px; margin:0 auto;">
    </div>

    <div class="sidebar-profile">
      <div class="profile-avatar-sm">AD</div>
      <div>
        <div class="sidebar-profile-name"><?= htmlspecialchars($_SESSION['admin_nome']) ?></div>
        <div class="sidebar-profile-role">Administrador</div>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section-label">Gestão</div>
      <button class="nav-link active" data-page="a-home">
        <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        Início
      </button>
      <button class="nav-link" data-page="a-consultas">
        <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/></svg>
        Consultas
      </button>
      <button class="nav-link" data-page="a-medicos">
        <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        Médicos
      </button>
      <button class="nav-link" data-page="a-pacientes">
        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Pacientes
      </button>
    </nav>

    <div class="sidebar-footer">
      <a href="logout.php" class="nav-link exit" onclick="return confirm('Deseja realmente sair da sua conta?')">
        <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Sair
      </a>
    </div>
  </aside>

  <!-- Área principal -->
  <div class="main-area">
    <header class="topbar">
      <div class="topbar-left">
        <h2 id="topbar-title">Painel</h2>
        <p id="topbar-sub">Visão geral do sistema</p>
      </div>
      <div class="topbar-right">
        <span class="tag-badge admin">Admin</span>
      </div>
    </header>

    <!-- ══════════ PÁGINA: Início ══════════ -->
    <main id="a-home" class="js-page page-content">

      <div class="greeting-block">
        <p class="greeting-date"><?= date('l, d \d\e F \d\e Y') ?></p>
        <h2 class="greeting-title">Painel Geral da Clínica</h2>
      </div>

      <!-- Cards de estatísticas -->
      <div class="grid-4 section">
        <div class="stat-card">
          <div class="stat-number"><?= $total_pacientes ?></div>
          <div class="stat-label">Pacientes Cadastrados</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?= $total_medicos ?></div>
          <div class="stat-label">Médicos Ativos</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?= $total_hoje ?></div>
          <div class="stat-label">Consultas Hoje</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?= $total_mes ?></div>
          <div class="stat-label">Consultas no Mês</div>
        </div>
      </div>

      <!-- Últimas consultas -->
      <div class="card-flat">
        <div class="card-header">
          <span class="card-title">Consultas Recentes</span>
          <button class="btn btn-sm btn-outline" id="btn-ver-todas-admin">Ver todas</button>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Paciente</th><th>Médico</th><th>Data &amp; Hora</th><th>Status</th></tr>
            </thead>
            <tbody>
              <?php foreach (array_slice($consultas, 0, 5) as $c): ?>
              <tr>
                <td><?= htmlspecialchars($c['paciente_nome']) ?></td>
                <td>
                  <?= htmlspecialchars($c['medico_nome']) ?>
                  <span class="td-muted">(<?= $c['especialidade'] ?>)</span>
                </td>
                <td><?= date('d/m/Y', strtotime($c['data'])) ?> às <?= substr($c['hora'], 0, 5) ?></td>
                <td><span class="badge <?= badgeStatus($c['status']) ?>"><?= ucfirst($c['status']) ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- ══════════ PÁGINA: Todas as Consultas ══════════ -->
    <main id="a-consultas" class="js-page page-content hidden">



      <div class="page-header">
        <div>
          <div class="eyebrow">Registro Geral</div>
          <h2>Todas as Consultas</h2>
          <p><?= count($todas_consultas) ?> consulta(s) registradas</p>
        </div>
        <button class="btn btn-green" id="btn-nova-consulta-admin">
          <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Nova Consulta
        </button>
      </div>

      <!-- Formulário Nova Consulta (toggle) -->
      <div id="form-consulta-wrap" class="card-flat form-mb hidden">
        <div class="card-header">
          <span class="card-title">Agendar Nova Consulta</span>
        </div>
        <form method="POST" action="admin_consulta.php">
          <input type="hidden" name="acao" value="cadastrar">
          <div class="card-body">
            <div class="form-grid form-mb">
              <div class="form-group">
                <label class="field-label">Paciente *</label>
                <select name="paciente_id" required>
                  <option value="">Selecione o paciente</option>
                  <?php foreach ($pacientes as $p): ?>
                  <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?> — <?= $p['cpf'] ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="field-label">Médico *</label>
                <select name="medico_id" required>
                  <option value="">Selecione o médico</option>
                  <?php foreach ($medicos as $m): if (!$m['ativo']) continue; ?>
                  <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nome']) ?> — <?= $m['especialidade'] ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="form-grid form-mb">
              <div class="form-group">
                <label class="field-label">Data *</label>
                <input type="date" name="data" min="<?= date('Y-m-d') ?>" required>
              </div>
              <div class="form-group">
                <label class="field-label">Horário *</label>
                <select name="hora" required>
                  <option value="">Selecione</option>
                  <?php
                  $slots = ['08:00','08:30','09:00','09:30','10:00','10:30','11:00','11:30',
                            '13:00','13:30','14:00','14:30','15:00','15:30','16:00','16:30','17:00'];
                  foreach ($slots as $s): ?>
                  <option value="<?= $s ?>"><?= $s ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="form-grid form-mb">
              <div class="form-group">
                <label class="field-label">Tipo</label>
                <select name="tipo">
                  <option value="presencial">Presencial</option>
                  <option value="teleconsulta">Teleconsulta</option>
                  <option value="retorno">Retorno</option>
                </select>
              </div>
              <div class="form-group">
                <label class="field-label">Convênio</label>
                <select name="convenio">
                  <option value="Particular">Particular</option>
                  <option value="Unimed">Unimed</option>
                  <option value="Amil">Amil</option>
                  <option value="Bradesco Saúde">Bradesco Saúde</option>
                  <option value="SulAmérica">SulAmérica</option>
                  <option value="Hapvida">Hapvida</option>
                </select>
              </div>
            </div>
            <div class="form-group form-mb">
              <label class="field-label">Motivo / Observações</label>
              <textarea name="motivo" placeholder="Sintomas, motivo da consulta..."></textarea>
            </div>
          </div>
          <div class="card-footer">
            <button type="button" class="btn btn-outline" id="btn-cancelar-consulta">Cancelar</button>
            <button type="submit" class="btn btn-green">Confirmar Agendamento</button>
          </div>
        </form>
      </div>

      <!-- Filtros por status -->
      <div class="filter-bar">
        <button class="filter-btn active" data-filter-admin="todas">Todas</button>
        <button class="filter-btn" data-filter-admin="agendada">Agendadas</button>
        <button class="filter-btn" data-filter-admin="confirmada">Confirmadas</button>
        <button class="filter-btn" data-filter-admin="realizada">Realizadas</button>
        <button class="filter-btn" data-filter-admin="cancelada">Canceladas</button>
      </div>

      <div class="card-flat">
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Paciente</th>
                <th>Médico</th>
                <th>Especialidade</th>
                <th>Data &amp; Hora</th>
                <th>Tipo</th>
                <th>Status</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($todas_consultas as $c): ?>
              <tr class="linha-consulta-admin" data-status="<?= $c['status'] ?>">
                <td><?= htmlspecialchars($c['paciente_nome']) ?></td>
                <td class="td-primary"><?= htmlspecialchars($c['medico_nome']) ?></td>
                <td class="td-muted"><?= $c['especialidade'] ?></td>
                <td><?= date('d/m/Y', strtotime($c['data'])) ?> · <?= substr($c['hora'], 0, 5) ?></td>
                <td><?= ucfirst($c['tipo']) ?></td>
                <td><span class="badge <?= badgeStatus($c['status']) ?>"><?= ucfirst($c['status']) ?></span></td>
                <td>
                  <!-- Alterar status -->
                  <form method="POST" action="admin_consulta.php" style="display:inline;">
                    <input type="hidden" name="acao" value="alterar_status">
                    <input type="hidden" name="id"   value="<?= $c['id'] ?>">
                    <select name="status" class="select-sm" data-original="<?= $c['status'] ?>"
                            onchange="if(confirm('Alterar status desta consulta para \'' + this.options[this.selectedIndex].text + '\'?')) this.form.submit(); else this.value = this.dataset.original;"
                            title="Alterar status">
                      <?php foreach (['agendada','confirmada','realizada','cancelada'] as $s): ?>
                      <option value="<?= $s ?>" <?= $c['status'] === $s ? 'selected' : '' ?>>
                        <?= ucfirst($s) ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </form>

                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- ══════════ PÁGINA: Médicos ══════════ -->
    <!-- ══════════ PÁGINA: Médicos ══════════ -->
    <main id="a-medicos" class="js-page page-content hidden">



      <div class="page-header">
        <div>
          <div class="eyebrow">Corpo Clínico</div>
          <h2>Médicos Cadastrados</h2>
          <p><?= count($medicos) ?> médico(s) no sistema</p>
        </div>
        <button class="btn btn-green" id="btn-novo-medico">
          <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Novo Médico
        </button>
      </div>

      <!-- Formulário de Cadastro / Edição (toggle) -->
      <div id="form-medico-wrap" class="card-flat form-mb hidden">
        <div class="card-header">
          <span class="card-title" id="form-medico-titulo">Cadastrar Novo Médico</span>
        </div>
        <form method="POST" action="admin_medico.php">
          <input type="hidden" name="acao" id="medico-acao" value="cadastrar">
          <input type="hidden" name="id"   id="medico-id"   value="">
          <div class="card-body">
            <div class="form-grid form-mb">
              <div class="form-group">
                <label class="field-label">Nome completo *</label>
                <input type="text" name="nome" id="medico-nome" placeholder="Dr. Nome Sobrenome" required>
              </div>
              <div class="form-group">
                <label class="field-label">CRM *</label>
                <input type="text" name="crm" id="medico-crm" placeholder="123456/SP" required>
              </div>
            </div>
            <div class="form-grid form-mb">
              <div class="form-group">
                <label class="field-label">Especialidade *</label>
                <select name="especialidade_id" id="medico-esp" required>
                  <option value="">Selecione</option>
                  <?php foreach ($especialidades as $e): ?>
                  <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nome']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="field-label">E-mail profissional *</label>
                <input type="email" name="email" id="medico-email" placeholder="medico@drmedical.com" required>
              </div>
            </div>
            <div class="form-grid form-mb">
              <div class="form-group">
                <label class="field-label">Senha <span id="senha-hint" class="text-muted">(obrigatória no cadastro)</span></label>
                <input type="text" name="senha" id="medico-senha" placeholder="Senha de acesso">
              </div>
            </div>
            <div class="form-group form-mb">
              <label class="field-label">Biografia <span class="text-muted">(exibida para pacientes)</span></label>
              <textarea name="bio" id="medico-bio" placeholder="Breve descrição profissional..."></textarea>
            </div>
          </div>
          <div class="card-footer">
            <button type="button" class="btn btn-outline" id="btn-cancelar-medico">Cancelar</button>
            <button type="submit" class="btn btn-green">Salvar</button>
          </div>
        </form>
      </div>

      <!-- Tabela de médicos -->
      <div id="medico-table-wrap" class="card-flat">
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Nome</th>
                <th>CRM</th>
                <th>Especialidade</th>
                <th>E-mail</th>
                <th>Status</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($medicos as $m): ?>
              <tr>
                <td class="td-primary"><?= htmlspecialchars($m['nome']) ?></td>
                <td class="td-muted"><?= $m['crm'] ?></td>
                <td><?= $m['especialidade'] ?></td>
                <td><?= htmlspecialchars($m['email']) ?></td>
                <td>
                  <?php if ($m['ativo']): ?>
                    <span class="badge badge-green">Ativo</span>
                  <?php else: ?>
                    <span class="badge badge-red">Inativo</span>
                  <?php endif; ?>
                </td>
                <td>
                  <button class="btn btn-sm btn-outline btn-editar-medico"
                    data-id="<?= $m['id'] ?>"
                    data-nome="<?= htmlspecialchars($m['nome']) ?>"
                    data-crm="<?= $m['crm'] ?>"
                    data-esp="<?= $m['especialidade_id'] ?>"
                    data-email="<?= htmlspecialchars($m['email']) ?>"
                    data-bio="<?= htmlspecialchars($m['bio'] ?? '') ?>">
                    Editar
                  </button>

                  <form method="POST" action="admin_medico.php" style="display:inline;">
                    <input type="hidden" name="acao"  value="toggle_ativo">
                    <input type="hidden" name="id"    value="<?= $m['id'] ?>">
                    <input type="hidden" name="ativo" value="<?= $m['ativo'] ?>">
                    <button type="submit" class="btn btn-sm <?= $m['ativo'] ? 'btn-amber-soft' : 'btn-green' ?>">
                      <?= $m['ativo'] ? 'Desativar' : 'Reativar' ?>
                    </button>
                  </form>

                  <form method="POST" action="admin_medico.php" style="display:inline;"
                        onsubmit="return confirm('Excluir este médico permanentemente?')">
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="id"   value="<?= $m['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-red-soft">Excluir</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- ══════════ PÁGINA: Pacientes ══════════ -->
    <main id="a-pacientes" class="js-page page-content hidden">



      <div class="page-header">
        <div>
          <div class="eyebrow">Cadastros</div>
          <h2>Pacientes</h2>
          <p><?= count($pacientes) ?> paciente(s) registrado(s)</p>
        </div>
        <button class="btn btn-green" id="btn-novo-paciente">
          <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Novo Paciente
        </button>
      </div>

      <!-- Formulário de cadastro / edição (toggle) -->
      <div id="form-paciente-wrap" class="card-flat form-mb hidden">
        <div class="card-header">
          <span class="card-title" id="form-paciente-titulo">Cadastrar Novo Paciente</span>
        </div>
        <form method="POST" action="admin_paciente.php">
          <input type="hidden" name="acao"  id="pac-acao" value="cadastrar">
          <input type="hidden" name="id"    id="pac-id" value="">
          <div class="card-body">
            <div class="form-grid form-mb">
              <div class="form-group">
                <label class="field-label">Nome</label>
                <input type="text" name="nome" id="pac-nome" required>
              </div>
              <div class="form-group">
                <label class="field-label">CPF</label>
                <input type="text" name="cpf" id="pac-cpf">
              </div>
            </div>
            <div class="form-grid form-mb">
              <div class="form-group">
                <label class="field-label">E-mail</label>
                <input type="email" name="email" id="pac-email" required>
              </div>
              <div class="form-group">
                <label class="field-label">Telefone</label>
                <input type="tel" name="telefone" id="pac-telefone">
              </div>
            </div>
            <div class="form-grid form-mb">
              <div class="form-group">
                <label class="field-label">Nascimento</label>
                <input type="date" name="nascimento" id="pac-nascimento">
              </div>
              <div class="form-group">
                <label class="field-label">Senha <span class="text-muted" id="pac-senha-hint">(obrigatória no cadastro)</span></label>
                <input type="text" name="nova_senha" id="pac-senha" placeholder="Senha de acesso">
              </div>
            </div>
          </div>
          <div class="card-footer">
            <button type="button" class="btn btn-outline" id="btn-cancelar-paciente">Cancelar</button>
            <button type="submit" class="btn btn-green">Salvar Alterações</button>
          </div>
        </form>
      </div>

      <div id="paciente-table-wrap" class="card-flat">
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Nome</th>
                <th>CPF</th>
                <th>E-mail</th>
                <th>Telefone</th>
                <th>Nascimento</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pacientes as $p): ?>
              <tr>
                <td class="td-primary"><?= htmlspecialchars($p['nome']) ?></td>
                <td class="td-muted"><?= $p['cpf'] ?></td>
                <td><?= htmlspecialchars($p['email']) ?></td>
                <td><?= $p['telefone'] ?? '—' ?></td>
                <td><?= $p['nascimento'] ? date('d/m/Y', strtotime($p['nascimento'])) : '—' ?></td>
                <td>
                  <button class="btn btn-sm btn-outline btn-editar-paciente"
                    data-id="<?= $p['id'] ?>"
                    data-nome="<?= htmlspecialchars($p['nome']) ?>"
                    data-cpf="<?= $p['cpf'] ?>"
                    data-email="<?= htmlspecialchars($p['email']) ?>"
                    data-telefone="<?= htmlspecialchars($p['telefone'] ?? '') ?>"
                    data-nascimento="<?= $p['nascimento'] ?? '' ?>">
                    Editar
                  </button>
                  <form method="POST" action="admin_paciente.php" style="display:inline;"
                        onsubmit="return confirm('Excluir este paciente e todas as suas consultas?')">
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="id"   value="<?= $p['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-red-soft">Excluir</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>

  </div><!-- /main-area -->  </div><!-- /main-area -->
</div><!-- /app-shell -->
<?php endif; ?>

<div id="toast-root"></div>
<script src="utils.js"></script>
<script src="admin.js"></script>
<?php if ($msg_ok): ?>
<script>
  document.addEventListener('DOMContentLoaded', () => Toast.show(<?= json_encode($msg_ok) ?>));
</script>
<?php elseif ($msg_erro): ?>
<script>
  document.addEventListener('DOMContentLoaded', () => Toast.show(<?= json_encode($msg_erro) ?>, 'error'));
</script>
<?php endif; ?>
</body>
</html>
