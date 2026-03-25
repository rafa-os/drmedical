<?php
// ============================================================
//  DR Medical Center — Portal do Médico
//  Se não logado: exibe tela de login (processa o POST aqui).
//  Se logado: exibe o painel com dados reais do banco.
// ============================================================

session_start();
include "conexao.php";

$erro_login  = '';
$msg_sucesso = $_GET['sucesso'] ?? '';
$msg_erro    = $_GET['erro']    ?? '';

// ── Processamento do Login ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] == 'POST' && ($_POST['acao'] ?? '') === 'login') {
    $crm   = $_POST['crm'];
    $senha = $_POST['senha'];

    // Busca pelo CRM ou e-mail
    $sql    = "SELECT * FROM medicos WHERE crm = '$crm' OR email = '$crm'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $medico = mysqli_fetch_assoc($result);

        if ($senha === $medico['senha']) {
            $_SESSION['medico_id']   = $medico['id'];
            $_SESSION['medico_nome'] = $medico['nome'];
            $_SESSION['medico_crm']  = $medico['crm'];

            // Redireciona para esta mesma página (agora com sessão)
            header("Location: medico.php");
            exit;
        }
    }

    $erro_login = "CRM/e-mail ou senha incorretos.";
}

// ── Dados do banco (só carrega se estiver logado) ─────────
if (isset($_SESSION['medico_id'])) {
    $medico_id = $_SESSION['medico_id'];

    // Dados do médico logado
    $sql_med = "SELECT m.*, e.nome AS especialidade
                FROM medicos m
                JOIN especialidades e ON m.especialidade_id = e.id
                WHERE m.id = '$medico_id'";
    $result_med = mysqli_query($conn, $sql_med);
    $medico     = mysqli_fetch_assoc($result_med);

    $partes   = explode(" ", $medico['nome']);
    $iniciais = strtoupper(substr($partes[0], 0, 1) . substr(end($partes), 0, 1));

    // Consultas de hoje
    $hoje     = date('Y-m-d');
    $sql_hoje = "SELECT c.*, p.nome AS paciente_nome
                 FROM consultas c
                 JOIN pacientes p ON c.paciente_id = p.id
                 WHERE c.medico_id = '$medico_id' AND c.data = '$hoje'
                 ORDER BY c.hora ASC";
    $result_hoje    = mysqli_query($conn, $sql_hoje);
    $consultas_hoje = [];
    while ($row = mysqli_fetch_assoc($result_hoje)) {
        $consultas_hoje[] = $row;
    }

    // Consultas das próximas 4 semanas (para a agenda navegável)
    $data_fim = date('Y-m-d', strtotime('+4 weeks'));
    $sql_periodo = "SELECT c.*, p.nome AS paciente_nome
                    FROM consultas c
                    JOIN pacientes p ON c.paciente_id = p.id
                    WHERE c.medico_id = '$medico_id'
                      AND c.data BETWEEN '$hoje' AND '$data_fim'
                    ORDER BY c.data ASC, c.hora ASC";
    $result_periodo    = mysqli_query($conn, $sql_periodo);
    $consultas_periodo = [];
    while ($row = mysqli_fetch_assoc($result_periodo)) {
        $consultas_periodo[] = $row;
    }
    while ($row = mysqli_fetch_assoc($result_hoje)) {
        $consultas_hoje[] = $row;
    }

    // Todas as consultas deste médico
    $sql_todas = "SELECT c.*, p.nome AS paciente_nome
                  FROM consultas c
                  JOIN pacientes p ON c.paciente_id = p.id
                  WHERE c.medico_id = '$medico_id'
                  ORDER BY c.data DESC, c.hora DESC";
    $result_todas    = mysqli_query($conn, $sql_todas);
    $todas_consultas = [];
    while ($row = mysqli_fetch_assoc($result_todas)) {
        $todas_consultas[] = $row;
    }

    // Pacientes que já consultaram com este médico
    $sql_pac = "SELECT DISTINCT p.*
                FROM pacientes p
                JOIN consultas c ON c.paciente_id = p.id
                WHERE c.medico_id = '$medico_id'
                ORDER BY p.nome";
    $result_pac = mysqli_query($conn, $sql_pac);
    $pacientes  = [];
    while ($row = mysqli_fetch_assoc($result_pac)) {
        $pacientes[] = $row;
    }
}

// Função auxiliar: iniciais do nome de um paciente
function iniciaisPaciente($nome) {
    $partes = explode(" ", $nome);
    return strtoupper(substr($partes[0], 0, 1) . substr(end($partes), 0, 1));
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
  <title>Portal Médico — DR Medical Center</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php if (!isset($_SESSION['medico_id'])): ?>
<!-- ══════════════════ TELA DE LOGIN ══════════════════ -->
<div class="auth-shell">

  <!-- Painel esquerdo -->
  <div class="auth-panel doctor-panel">
    <div class="auth-brand">
      <img src="img/logo.png" alt="DR Medical Center" style="display:block; height:150px; margin:0 auto 8px auto;">
    </div>
    <div class="auth-panel-body">
      <div class="auth-role-pill doctor">Área Médica</div>
      <h2 class="auth-panel-h2">Gestão clínica<br><em class="gold-em">de alto padrão.</em></h2>
      <p class="auth-panel-desc">Gerencie sua agenda, acompanhe pacientes e consulte prontuários.</p>
    </div>

  </div>

  <!-- Painel direito: formulário -->
  <div class="auth-form-panel">
    <div class="auth-form-box">

      <button class="auth-back" onclick="window.location.href='login.html'">
        <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        Voltar ao início
      </button>

      <!-- Apenas login — cadastro de médicos é feito pelo Administrador -->
      <div class="auth-heading">
        <h3>Acesso Médico</h3>
        <p>Entre com suas credenciais profissionais</p>
      </div>

      <!-- Mensagem de erro do login -->
      <?php if ($erro_login): ?>
        <div class="callout callout-red form-mb">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
          <div><?= htmlspecialchars($erro_login) ?></div>
        </div>
      <?php endif; ?>

      <!-- Formulário de login — envia para medico.php (esta mesma página) -->
      <div id="form-login" class="auth-form">
        <form method="POST" action="medico.php">
          <input type="hidden" name="acao" value="login">
          <div class="form-group form-mb">
            <label class="field-label">CRM</label>
            <div class="input-icon">
              <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <input type="text" name="crm" placeholder="Ex: 123456/SP" required>
            </div>
          </div>
          <div class="form-group form-mb">
            <label class="field-label">Senha</label>
            <div class="input-icon">
              <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              <input type="password" name="senha" placeholder="••••••••" required>
            </div>
          </div>
          <button type="submit" class="btn-block-navy">Acessar o Painel</button>
        </form>
        <div class="forgot-link">
          <a href="recuperar_senha.php?tipo=medico">Esqueci minha senha</a>
        </div>
      </div>

    </div>
  </div>
</div>

<?php else: ?>
<!-- ══════════════════ PAINEL DO MÉDICO ══════════════════ -->
<div class="app-shell">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <img src="img/logo.png" alt="DR Medical Center" style="display:block; height:72px; margin:0 auto;">
    </div>

    <div class="sidebar-profile">
      <?php
        // Gera caminho da foto a partir do primeiro nome em minúsculas
        $primeiro_nome = strtolower(explode(' ', trim(preg_replace('/^(Dr\.|Dra\.)\s*/i', '', $medico['nome'])))[0]);
        $foto_path = 'img/' . $primeiro_nome . '.jpg';
        $foto_existe = file_exists($foto_path);
      ?>
      <?php if ($foto_existe): ?>
        <img src="<?= $foto_path ?>" alt="<?= htmlspecialchars($medico['nome']) ?>"
             class="profile-avatar-sm profile-avatar-photo">
      <?php else: ?>
        <div class="profile-avatar-sm"><?= $iniciais ?></div>
      <?php endif; ?>
      <div>
        <div class="sidebar-profile-name"><?= htmlspecialchars($medico['nome']) ?></div>
        <div class="sidebar-profile-role"><?= $medico['especialidade'] ?> · <?= $medico['crm'] ?></div>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section-label">Painel</div>
      <button class="nav-link active" data-page="d-home">
        <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        Início
      </button>
      <button class="nav-link" data-page="d-agenda">
        <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Minha Agenda
      </button>
      <button class="nav-link" data-page="d-patients">
        <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
        Pacientes
      </button>
      <button class="nav-link" data-page="d-historico">
        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Histórico
      </button>
      <button class="nav-link" data-page="d-perfil">
        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Meu Perfil
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
        <p id="topbar-sub">Visão geral do dia</p>
      </div>
      <div class="topbar-right">
        <span class="tag-badge doctor">🩺 Médico</span>
      </div>
    </header>

    <!-- Mensagens de retorno via toast (não empurra layout) -->
    <?php if ($msg_sucesso): ?>
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        Toast.show(<?= json_encode($msg_sucesso) ?>);
      });
    </script>
    <?php elseif ($msg_erro): ?>
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        Toast.show(<?= json_encode($msg_erro) ?>, 'error');
      });
    </script>
    <?php endif; ?>

    <!-- ══════════ PÁGINA: Início ══════════ -->
    <main id="d-home" class="js-page page-content">

      <!-- Banner de boas-vindas -->
      <div class="banner doc-banner">
        <div class="banner-eyebrow"><?php
$dias_pt   = ['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'];
$meses_pt  = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
echo $dias_pt[date('w')] . ', ' . date('d') . ' de ' . $meses_pt[date('n')-1] . ' de ' . date('Y');
?></div>
        <h2 class="banner-title">
          Olá, <?= htmlspecialchars($partes[0] . ' ' . ($partes[1] ?? '')) ?>
        </h2>
        <p class="banner-sub">
          Você tem <strong><?= count($consultas_hoje) ?> consulta(s)</strong> agendada(s) para hoje.
        </p>
        <div class="banner-actions">
          <button class="banner-btn banner-btn-green" id="btn-ver-agenda">Ver Agenda de Hoje</button>
        </div>
      </div>

      <div class="grid-2 section">

        <!-- Agenda do dia -->
        <div class="card-flat">
          <div class="card-header">
            <span class="card-title">Agenda de Hoje</span>
            <span class="badge badge-navy"><?= date('d/m') ?></span>
          </div>
          <div class="card-body">
            <?php if (empty($consultas_hoje)): ?>
              <div class="empty-state">Nenhuma consulta hoje.</div>
            <?php else: ?>
              <?php foreach ($consultas_hoje as $c): ?>
              <div class="timeline-item">
                <div class="timeline-time"><?= substr($c['hora'], 0, 5) ?></div>
                <div class="avatar av-navy"><?= iniciaisPaciente($c['paciente_nome']) ?></div>
                <div class="patient-list-info">
                  <div class="patient-list-name"><?= htmlspecialchars($c['paciente_nome']) ?></div>
                  <div class="patient-list-email"><?= ucfirst($c['tipo']) ?> · <?= $c['convenio'] ?></div>
                </div>
                <span class="badge <?= badgeStatus($c['status']) ?>"><?= ucfirst($c['status']) ?></span>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Últimos pacientes -->
        <div class="card-flat">
          <div class="card-header">
            <span class="card-title">Pacientes</span>
            <button class="btn btn-sm btn-outline" id="btn-ver-pacientes">Ver todos</button>
          </div>
          <div class="card-body">
            <?php if (empty($pacientes)): ?>
              <div class="empty-state">Nenhum paciente cadastrado.</div>
            <?php else: ?>
              <?php foreach (array_slice($pacientes, 0, 5) as $p): ?>
              <div class="patient-list-row">
                <div class="avatar av-navy"><?= iniciaisPaciente($p['nome']) ?></div>
                <div class="patient-list-info">
                  <div class="patient-list-name"><?= htmlspecialchars($p['nome']) ?></div>
                  <div class="patient-list-email"><?= htmlspecialchars($p['email']) ?></div>
                </div>
                <span class="badge badge-navy">Ativo</span>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

      </div>
    </main>

    <!-- ══════════ PÁGINA: Minha Agenda ══════════ -->
    <!-- ══════════ PÁGINA: Minha Agenda ══════════ -->
    <main id="d-agenda" class="js-page page-content hidden">
      <div class="page-header">
        <div>
          <div class="eyebrow">Próximas 4 semanas</div>
          <h2>Minha Agenda</h2>
        </div>
      </div>

      <!-- Navegação de dias: setas + tira clicável -->
      <div class="agenda-nav">
        <button class="agenda-nav-btn" id="agenda-prev" title="Semana anterior">
          <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <div class="week-strip" id="week-strip">
          <?php
          $nomes_abrev = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
          for ($i = 0; $i < 28; $i++):  // 4 semanas
              $ts       = strtotime("+{$i} days");
              $data_dia = date('Y-m-d', $ts);
              $num_dia  = date('d', $ts);
              $mes_dia  = date('m', $ts);
              $nome_dia = $nomes_abrev[date('w', $ts)];
              $eh_hoje  = ($i === 0);
              $tem = false;
              foreach ($consultas_periodo as $c) {
                  if ($c['data'] === $data_dia) { $tem = true; break; }
              }
          ?>
          <div class="week-day <?= $eh_hoje ? 'active' : '' ?>"
               data-date="<?= $data_dia ?>"
               data-index="<?= $i ?>">
            <div class="week-day-name"><?= $nome_dia ?></div>
            <div class="week-day-num"><?= $num_dia ?></div>
            <div class="week-day-month"><?= date('M', $ts) ?></div>
            <?php if ($tem): ?><div class="week-day-dot"></div><?php endif; ?>
          </div>
          <?php endfor; ?>
        </div>
        <button class="agenda-nav-btn" id="agenda-next" title="Próxima semana">
          <svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      </div>

      <!-- Consultas do dia selecionado -->
      <div class="card-flat">
        <div class="card-header">
          <span class="card-title" id="agenda-label">
            Consultas — <?= date('d/m/Y') ?>
          </span>
          <span class="badge badge-navy" id="agenda-count">
            <?= count(array_filter($consultas_periodo, fn($c) => $c['data'] === $hoje)) ?> consulta(s)
          </span>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Horário</th>
                <th>Paciente</th>
                <th>Tipo</th>
                <th>Convênio</th>
                <th>Status</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($consultas_periodo as $c):
                $ocultar = ($c['data'] !== $hoje) ? 'hidden' : '';
              ?>
              <tr class="agenda-row <?= $ocultar ?>" data-date="<?= $c['data'] ?>">
                <td class="td-primary"><?= substr($c['hora'], 0, 5) ?></td>
                <td>
                  <div class="patient-cell">
                    <div class="avatar av-navy"><?= iniciaisPaciente($c['paciente_nome']) ?></div>
                    <div>
                      <div class="patient-cell-name"><?= htmlspecialchars($c['paciente_nome']) ?></div>
                      <div class="patient-cell-sub"><?= $c['convenio'] ?></div>
                    </div>
                  </div>
                </td>
                <td><?= ucfirst($c['tipo']) ?></td>
                <td><?= $c['convenio'] ?></td>
                <td><span class="badge <?= badgeStatus($c['status']) ?>"><?= ucfirst($c['status']) ?></span></td>
                <td>
                  <?php if (in_array($c['status'], ['agendada', 'confirmada'])): ?>
                    <form method="POST" action="confirmar_medico.php" style="display:inline;">
                      <input type="hidden" name="consulta_id" value="<?= $c['id'] ?>">
                      <input type="hidden" name="acao" value="cancelar">
                      <button type="submit" class="btn btn-sm btn-red-soft"
                              onclick="return confirm('Cancelar esta consulta?')">Cancelar</button>
                    </form>
                  <?php else: ?>
                    <span class="td-muted"><?= ucfirst($c['status']) ?></span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <p id="agenda-vazia" class="empty-state hidden">Nenhuma consulta agendada para este dia.</p>
        </div>
      </div>
    </main>

    <!-- ══════════ PÁGINA: Pacientes ══════════ -->
    <main id="d-patients" class="js-page page-content hidden">
      <div class="page-header">
        <div>
          <div class="eyebrow">Cadastro</div>
          <h2>Pacientes</h2>
          <p><?= count($pacientes) ?> paciente(s) cadastrado(s)</p>
        </div>
        <div class="search-wrap">
          <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" id="patient-search" placeholder="Buscar por nome ou e-mail...">
        </div>
      </div>
      <div class="card-flat">
        <div class="table-wrap">
          <table id="patients-table">
            <thead>
              <tr><th>Paciente</th><th>CPF</th><th>Telefone</th><th>E-mail</th></tr>
            </thead>
            <tbody>
              <?php if (empty($pacientes)): ?>
                <tr><td colspan="4" class="empty-state">Nenhum paciente encontrado.</td></tr>
              <?php else: ?>
                <?php foreach ($pacientes as $p): ?>
                <tr>
                  <td>
                    <div class="patient-cell">
                      <div class="avatar av-navy"><?= iniciaisPaciente($p['nome']) ?></div>
                      <div>
                        <div class="patient-cell-name"><?= htmlspecialchars($p['nome']) ?></div>
                        <div class="patient-cell-sub"><?= htmlspecialchars($p['email']) ?></div>
                      </div>
                    </div>
                  </td>
                  <td class="td-muted"><?= htmlspecialchars($p['cpf']) ?></td>
                  <td><?= htmlspecialchars($p['telefone'] ?? '—') ?></td>
                  <td><?= htmlspecialchars($p['email']) ?></td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- ══════════ PÁGINA: Histórico de Consultas ══════════ -->
    <main id="d-historico" class="js-page page-content hidden">
      <div class="page-header">
        <div>
          <div class="eyebrow">Registro Completo</div>
          <h2>Histórico de Consultas</h2>
          <p>Todas as consultas registradas no sistema</p>
        </div>
      </div>

      <div class="filter-bar">
        <button class="filter-btn active" data-filter-hist="todas">Todas</button>
        <button class="filter-btn" data-filter-hist="agendada">Agendadas</button>
        <button class="filter-btn" data-filter-hist="confirmada">Confirmadas</button>
        <button class="filter-btn" data-filter-hist="realizada">Realizadas</button>
        <button class="filter-btn" data-filter-hist="cancelada">Canceladas</button>
      </div>

      <div class="card-flat">
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Paciente</th>
                <th>Data &amp; Hora</th>
                <th>Tipo</th>
                <th>Convênio</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($todas_consultas)): ?>
                <tr><td colspan="5" class="empty-state">Nenhuma consulta registrada.</td></tr>
              <?php else: ?>
                <?php foreach ($todas_consultas as $c): ?>
                <tr class="linha-hist" data-status="<?= $c['status'] ?>">
                  <td>
                    <div class="patient-cell">
                      <div class="avatar av-navy"><?= iniciaisPaciente($c['paciente_nome']) ?></div>
                      <div>
                        <div class="patient-cell-name"><?= htmlspecialchars($c['paciente_nome']) ?></div>
                        <div class="patient-cell-sub"><?= ucfirst($c['tipo']) ?></div>
                      </div>
                    </div>
                  </td>
                  <td><?= date('d/m/Y', strtotime($c['data'])) ?> · <?= substr($c['hora'], 0, 5) ?></td>
                  <td><?= ucfirst($c['tipo']) ?></td>
                  <td><?= htmlspecialchars($c['convenio']) ?></td>
                  <td><span class="badge <?= badgeStatus($c['status']) ?>"><?= ucfirst($c['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- ══════════ PÁGINA: Perfil ══════════ -->
    <main id="d-perfil" class="js-page page-content hidden">
      <div class="page-header">
        <div>
          <div class="eyebrow">Conta</div>
          <h2>Meu Perfil</h2>
        </div>
      </div>

      <form method="POST" action="atualizar_perfil.php">
        <div class="card-flat">
          <div class="card-body">

            <!-- Hero com avatar e nome -->
            <div class="profile-hero">
              <?php
                $primeiro_nome_perf = strtolower(explode(' ', trim(preg_replace('/^(Dr\.|Dra\.)\s*/i', '', $medico['nome'])))[0]);
                $foto_perf = 'img/' . $primeiro_nome_perf . '.jpg';
              ?>
              <?php if (file_exists($foto_perf)): ?>
                <img src="<?= $foto_perf ?>" alt="<?= htmlspecialchars($medico['nome']) ?>" class="profile-avatar-xl-photo">
              <?php else: ?>
                <div class="profile-avatar-xl"><?= $iniciais ?></div>
              <?php endif; ?>
              <div>
                <h3><?= htmlspecialchars($medico['nome']) ?></h3>
                <p><?= htmlspecialchars($medico['especialidade']) ?> · <?= $medico['crm'] ?></p>
              </div>
            </div>

            <div class="form-divider"><h5>Dados Profissionais</h5></div>
            <div class="form-grid form-mb">
              <div class="form-group">
                <label class="field-label">Nome completo</label>
                <input type="text" value="<?= htmlspecialchars($medico['nome']) ?>" readonly>
              </div>
              <div class="form-group">
                <label class="field-label">CRM</label>
                <input type="text" value="<?= $medico['crm'] ?>" readonly>
              </div>
            </div>
            <div class="form-grid form-mb">
              <div class="form-group">
                <label class="field-label">Especialidade</label>
                <input type="text" value="<?= htmlspecialchars($medico['especialidade']) ?>" readonly>
              </div>
              <div class="form-group">
                <label class="field-label">E-mail profissional</label>
                <input type="email" name="email" value="<?= htmlspecialchars($medico['email']) ?>" required>
              </div>
            </div>
            <div class="form-group form-mb">
              <label class="field-label">Biografia <span class="text-muted">(exibida para pacientes)</span></label>
              <textarea name="bio" rows="3"><?= htmlspecialchars($medico['bio'] ?? '') ?></textarea>
            </div>

            <div class="form-divider"><h5>Segurança</h5></div>
            <div class="form-group form-mb">
              <label class="field-label">Nova senha <span class="text-muted">(deixe em branco para manter a atual)</span></label>
              <input type="password" name="nova_senha" placeholder="Nova senha">
            </div>

          </div>
          <div class="card-footer">
            <button type="button" class="btn btn-outline" id="btn-cancelar-perfil-med">Cancelar</button>
            <button type="submit" class="btn btn-green">Salvar Alterações</button>
          </div>
        </div>
      </form>
    </main>

  </div><!-- /main-area -->
</div><!-- /app-shell -->
<?php endif; ?>

<div id="toast-root"></div>
<script src="utils.js"></script>
<script src="medico.js"></script>
</body>
</html>
