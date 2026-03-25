<?php
// ============================================================
//  DR Medical Center — Portal do Paciente
//  Bloco PHP: sessão, consultas ao banco, dados para a view
//  Bloco HTML: estrutura da página usando classes do style.css
// ============================================================

session_start();
include "conexao.php";

// Protege a página — redireciona se não estiver logado
if (!isset($_SESSION['paciente_id'])) {
    header("Location: login.html");
    exit;
}

$paciente_id = $_SESSION['paciente_id'];

// ── Dados do paciente logado ──────────────────────────────
$sql     = "SELECT * FROM pacientes WHERE id = '$paciente_id'";
$result  = mysqli_query($conn, $sql);
$paciente = mysqli_fetch_assoc($result);

// Iniciais para o avatar (ex: "João Costa" → "JC")
$partes  = explode(" ", $paciente['nome']);
$iniciais = strtoupper(substr($partes[0], 0, 1) . substr(end($partes), 0, 1));

// ── Consultas do paciente ─────────────────────────────────
$sql_c = "SELECT c.*, m.nome AS medico_nome, m.crm, e.nome AS especialidade
          FROM consultas c
          JOIN medicos m ON c.medico_id = m.id
          JOIN especialidades e ON m.especialidade_id = e.id
          WHERE c.paciente_id = '$paciente_id'
          ORDER BY c.data DESC, c.hora DESC";
$result_c = mysqli_query($conn, $sql_c);

$consultas = [];
while ($row = mysqli_fetch_assoc($result_c)) {
    $consultas[] = $row;
}

// Próxima consulta (data futura, status ativo)
$proxima = null;
foreach ($consultas as $c) {
    if (in_array($c['status'], ['agendada','confirmada']) && $c['data'] >= date('Y-m-d')) {
        $proxima = $c;
        break;
    }
}

// ── Médicos disponíveis (para agendamento) ────────────────
$sql_m = "SELECT m.*, e.nome AS especialidade
          FROM medicos m
          JOIN especialidades e ON m.especialidade_id = e.id

          ORDER BY e.nome, m.nome";
$result_m = mysqli_query($conn, $sql_m);
$medicos  = [];
while ($row = mysqli_fetch_assoc($result_m)) {
    $medicos[] = $row;
}

// ── Especialidades (para os filtros) ─────────────────────
$sql_e  = "SELECT * FROM especialidades ORDER BY nome";
$result_e = mysqli_query($conn, $sql_e);
$especialidades = [];
while ($row = mysqli_fetch_assoc($result_e)) {
    $especialidades[] = $row;
}

// ── Horários já ocupados (para bloquear nos slots) ────────
$sql_ocup = "SELECT medico_id, data, hora FROM consultas WHERE status != 'cancelada'";
$result_ocup = mysqli_query($conn, $sql_ocup);
$horarios_ocupados = [];
while ($row = mysqli_fetch_assoc($result_ocup)) {
    $horarios_ocupados[] = $row;
}

// ── Mensagens de feedback vindas de agendar.php/cancelar.php
$msg_sucesso = $_GET['sucesso'] ?? '';
$msg_erro    = $_GET['erro']    ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Portal do Paciente — DR Medical Center</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="app-shell">

  <!-- ══════════════════ SIDEBAR ══════════════════ -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <img src="img/logo.png" alt="DR Medical Center" style="display:block; height:72px; margin:0 auto;">
    </div>

    <!-- Avatar com iniciais do paciente logado -->
    <div class="sidebar-profile">
      <div class="profile-avatar-sm"><?= $iniciais ?></div>
      <div>
        <div class="sidebar-profile-name"><?= $paciente['nome'] ?></div>
        <div class="sidebar-profile-role">Paciente</div>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section-label">Menu Principal</div>
      <button class="nav-link active" data-page="p-home">
        <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        Início
      </button>
      <button class="nav-link" data-page="p-doctors">
        <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Médicos
      </button>
      <button class="nav-link" data-page="p-appointments">
        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/></svg>
        Minhas Consultas
      </button>
      <button class="nav-link" data-page="p-profile">
        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Meu Perfil
      </button>
    </nav>

    <div class="sidebar-footer">
      <!-- Link de logout redireciona para logout.php que destrói a sessão -->
      <a href="logout.php" class="nav-link exit" onclick="return confirm('Deseja realmente sair da sua conta?')">
        <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Sair
      </a>
    </div>
  </aside>

  <!-- ══════════════════ ÁREA PRINCIPAL ══════════════════ -->
  <div class="main-area">

    <!-- Barra superior -->
    <header class="topbar">
      <div class="topbar-left">
        <h2 id="topbar-title">Painel</h2>
        <p id="topbar-sub">Visão geral da sua conta</p>
      </div>
      <div class="topbar-right">
        <span class="tag-badge patient">
          <svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          Paciente
        </span>
      </div>
    </header>

    <!-- ══════════ PÁGINA: Início ══════════ -->
    <main id="p-home" class="js-page page-content">

      <?php if ($msg_sucesso): ?>
        <script>
          document.addEventListener('DOMContentLoaded', () => {
            Toast.show(<?= json_encode($msg_sucesso) ?>);
          });
        </script>
      <?php endif; ?>
      <?php if ($msg_erro): ?>
        <script>
          document.addEventListener('DOMContentLoaded', () => {
            Toast.show(<?= json_encode($msg_erro) ?>, 'error');
          });
        </script>
      <?php endif; ?>

      <!-- Saudação -->
      <div class="greeting-block">
        <p class="greeting-date"><?php
$dias_pt  = ['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'];
$meses_pt = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
echo $dias_pt[date('w')] . ', ' . date('d') . ' de ' . $meses_pt[date('n')-1] . ' de ' . date('Y');
?></p>
        <h2 class="greeting-title">Olá, <?= explode(" ", $paciente['nome'])[0] ?>. Como vai?</h2>
        <p class="greeting-sub">Acompanhe suas consultas e gerencie sua saúde.</p>
      </div>

      <!-- CTA principal: botão de agendar consulta -->
      <div class="home-cta-agendar">
        <button class="btn-agendar-home" id="btn-home-agendar">
          <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="12" y1="14" x2="12" y2="18"/><line x1="10" y1="16" x2="14" y2="16"/></svg>
          Agendar Consulta
        </button>
      </div>

      <div class="grid-2 section">

        <!-- Coluna esquerda: próximas consultas + atalhos -->
        <div class="flex flex-col gap-3">

          <!-- Destaque da próxima consulta -->
          <?php if ($proxima): ?>
          <div class="appt-highlight">
            <div class="appt-highlight-deco-1"></div>
            <div class="appt-highlight-deco-2"></div>
            <div class="appt-highlight-inner">
              <p class="appt-highlight-label">Próxima Consulta</p>
              <div class="appt-highlight-row">
                <div class="appt-date-badge">
                  <div class="appt-date-num"><?= date('d', strtotime($proxima['data'])) ?></div>
                  <div class="appt-date-mon"><?= strtoupper(date('M', strtotime($proxima['data']))) ?></div>
                </div>
                <div class="appt-highlight-info">
                  <div class="appt-highlight-name"><?= htmlspecialchars($proxima['medico_nome']) ?></div>
                  <div class="appt-highlight-spec"><?= $proxima['especialidade'] ?> · <?= ucfirst($proxima['tipo']) ?></div>
                  <div class="appt-highlight-time">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <?= substr($proxima['hora'], 0, 5) ?>
                  </div>
                  <div class="appt-highlight-badges">
                    <span class="badge-confirm"><?= ucfirst($proxima['status']) ?></span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php else: ?>
          <div class="appt-highlight">
            <div class="appt-highlight-inner">
              <div class="appt-highlight-empty">
                Nenhuma consulta agendada. Agende agora mesmo!
              </div>
            </div>
          </div>
          <?php endif; ?>

          <!-- Atalhos rápidos -->
          <div class="quick-actions">
            <div class="quick-actions-header">
              <p class="quick-actions-label">Acesso Rápido</p>
            </div>
            <div class="quick-actions-grid">
              <button class="quick-action-btn" id="btn-quick-doctors">
                <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
                <div class="quick-action-title">Encontrar Médico</div>
                <div class="quick-action-sub">Ver especialistas</div>
              </button>
              <button class="quick-action-btn" id="btn-quick-appointments">
                <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <div class="quick-action-title">Minhas Consultas</div>
                <div class="quick-action-sub">Histórico completo</div>
              </button>
            </div>
          </div>
        </div>

        <!-- Coluna direita: consultas recentes -->
        <div class="history-card">
          <div class="history-header">
            <p class="history-header-label">Consultas Recentes</p>
            <button class="btn btn-xs btn-ghost" id="btn-ver-todas">Ver todas →</button>
          </div>
          <?php
          $recentes = array_slice($consultas, 0, 4);
          if (empty($recentes)):
          ?>
            <div class="empty-state">Nenhuma consulta encontrada.</div>
          <?php else: ?>
            <?php foreach ($recentes as $c):
              $pts_med  = explode(" ", $c['medico_nome']);
              $init_med = strtoupper(substr(end($pts_med), 0, 2));
              $badge    = 'badge-amber';
              if ($c['status'] == 'confirmada') $badge = 'badge-green';
              if ($c['status'] == 'realizada')  $badge = 'badge-violet';
              if ($c['status'] == 'cancelada')  $badge = 'badge-red';
            ?>
            <div class="history-row">
              <div class="avatar av-navy"><?= $init_med ?></div>
              <div class="history-row-info">
                <div class="history-row-name"><?= htmlspecialchars($c['medico_nome']) ?></div>
                <div class="history-row-sub"><?= $c['especialidade'] ?> · <?= date('d/m/Y', strtotime($c['data'])) ?></div>
              </div>
              <div class="history-row-actions">
                <span class="badge <?= $badge ?>"><?= ucfirst($c['status']) ?></span>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

      </div>
    </main>

    <!-- ══════════ PÁGINA: Médicos ══════════ -->
    <main id="p-doctors" class="js-page page-content hidden">
      <div class="page-header">
        <div>
          <div class="eyebrow">Encontre seu especialista</div>
          <h2>Corpo Clínico</h2>
          <p>Veja disponibilidade e agende sua consulta</p>
        </div>
        <div class="search-wrap">
          <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" id="doctor-search-input" placeholder="Buscar por nome ou especialidade...">
        </div>
      </div>

      <!-- Pílulas de filtro — preenchidas pelo JS -->
      <div id="spec-pills" class="spec-pills"></div>

      <!-- Grade de médicos + painel de horários -->
      <div class="doctors-layout">
        <div id="doctors-grid" class="doctors-grid"></div>
        <div>
          <div id="schedule-panel" class="schedule-panel">
            <div class="empty-panel">
              <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              <p>Selecione um médico para ver os horários disponíveis</p>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- ══════════ PÁGINA: Agendar Consulta ══════════ -->
    <main id="p-schedule" class="js-page page-content hidden">
      <div class="page-header">
        <div>
          <div class="eyebrow">Confirmação</div>
          <h2>Agendar Consulta</h2>
          <p>Revise os dados e confirme seu agendamento</p>
        </div>
        <button class="btn btn-outline" id="btn-back-doctors">
          <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
          Voltar e escolher outro médico
        </button>
      </div>

      <!-- Indicador de etapas -->
      <div class="steps">
        <div class="step-item done"><div class="step-dot">✓</div> Especialista</div>
        <div class="step-line done"></div>
        <div class="step-item done"><div class="step-dot">✓</div> Horário</div>
        <div class="step-line done"></div>
        <div class="step-item active"><div class="step-dot">3</div> Confirmação</div>
      </div>

      <!-- Resumo (preenchido pelo JavaScript) -->
      <div id="schedule-summary"></div>

      <!-- Formulário que envia para agendar.php -->
      <form id="form-agendar" action="agendar.php" method="POST">
        <!-- Campos ocultos preenchidos pelo JS quando usuário seleciona médico/data/hora -->
        <input type="hidden" id="field-medico-id" name="medico_id">
        <input type="hidden" id="field-data"      name="data">
        <input type="hidden" id="field-hora"      name="hora">

        <div class="card-flat">
          <div class="card-header">
            <span class="card-title">Dados da Consulta</span>
          </div>
          <div class="card-body">
            <div class="form-grid form-mb">
              <div class="form-group">
                <label class="field-label">Tipo de Consulta</label>
                <select name="tipo">
                  <option value="presencial">Consulta Presencial</option>
                  <option value="teleconsulta">Teleconsulta</option>
                  <option value="retorno">Consulta de Retorno</option>
                </select>
              </div>
              <div class="form-group">
                <label class="field-label">Convênio / Plano de Saúde</label>
                <select name="convenio">
                  <option>Particular</option>
                  <option>Unimed</option>
                  <option>Amil</option>
                  <option>Bradesco Saúde</option>
                  <option>SulAmérica</option>
                  <option>Hapvida</option>
                </select>
              </div>
            </div>
            <div class="form-group form-mb">
              <label class="field-label">Sintomas / Motivo da Consulta</label>
              <textarea name="motivo" placeholder="Descreva seus sintomas para que o médico possa se preparar melhor..."></textarea>
            </div>
            <div class="callout callout-amber form-mb">
              <svg viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/></svg>
              <div>Cancelamentos devem ser feitos com no mínimo <strong>2 horas de antecedência</strong>.</div>
            </div>
          </div>
          <div class="card-footer">
            <button type="button" class="btn btn-outline" id="btn-cancel-schedule">Voltar</button>
            <button type="submit" class="btn btn-green">
              <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              Confirmar Agendamento
            </button>
          </div>
        </div>
      </form>
    </main>

    <!-- ══════════ PÁGINA: Minhas Consultas ══════════ -->
    <main id="p-appointments" class="js-page page-content hidden">
      <!-- Navegação por data nas consultas -->
      <div class="date-nav">
        <button class="date-nav-btn" id="appt-prev" title="Período anterior">
          <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <span class="date-nav-label" id="appt-period-label">Todas as consultas</span>
        <button class="date-nav-btn" id="appt-next" title="Próximo período">
          <svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      </div>

      <div class="page-header">
        <div>
          <div class="eyebrow">Histórico</div>
          <h2>Minhas Consultas</h2>
        </div>
        <button class="btn btn-green" id="btn-nova-consulta">
          <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Nova Consulta
        </button>
      </div>

      <!-- Filtros por status -->


      <div class="card-flat">
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Médico / Especialidade</th>
                <th>Data &amp; Hora</th>
                <th>Tipo</th>
                <th>Convênio</th>
                <th>Status</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($consultas)): ?>
                <tr><td colspan="6" class="empty-state">Nenhuma consulta encontrada.</td></tr>
              <?php else: ?>
                <?php foreach ($consultas as $c):
                  $pts_med  = explode(" ", $c['medico_nome']);
                  $init_med = strtoupper(substr(end($pts_med), 0, 2));
                  $badge    = 'badge-amber';
                  if ($c['status'] == 'confirmada') $badge = 'badge-green';
                  if ($c['status'] == 'realizada')  $badge = 'badge-violet';
                  if ($c['status'] == 'cancelada')  $badge = 'badge-red';
                ?>
                <tr class="linha-consulta" data-status="<?= $c['status'] ?>">
                  <td>
                    <div class="patient-cell">
                      <div class="avatar av-navy"><?= $init_med ?></div>
                      <div>
                        <div class="patient-cell-name"><?= htmlspecialchars($c['medico_nome']) ?></div>
                        <div class="patient-cell-sub"><?= $c['especialidade'] ?></div>
                      </div>
                    </div>
                  </td>
                  <td class="td-primary"><?= date('d/m/Y', strtotime($c['data'])) ?></td>
                  <td><?= substr($c['hora'], 0, 5) ?> · <?= ucfirst($c['tipo']) ?></td>
                  <td><?= $c['convenio'] ?></td>
                  <td><span class="badge <?= $badge ?>"><?= ucfirst($c['status']) ?></span></td>
                  <td>
                    <?php if ($c['status'] === 'agendada'): ?>
                      <form method="POST" action="confirmar_paciente.php" style="display:inline;">
                        <input type="hidden" name="consulta_id" value="<?= $c['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-green"
                                onclick="return confirm('Confirmar sua presença nesta consulta?')">Confirmar</button>
                      </form>
                      <form method="POST" action="cancelar.php" class="form-inline-cancel" style="display:inline;">
                        <input type="hidden" name="consulta_id" value="<?= $c['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-red-soft btn-cancelar">Cancelar</button>
                      </form>
                    <?php elseif ($c['status'] === 'confirmada'): ?>
                      <form method="POST" action="cancelar.php" class="form-inline-cancel" style="display:inline;">
                        <input type="hidden" name="consulta_id" value="<?= $c['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-red-soft btn-cancelar">Cancelar</button>
                      </form>
                    <?php else: ?>
                      <span class="td-muted">—</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- ══════════ PÁGINA: Meu Perfil ══════════ -->
    <main id="p-profile" class="js-page page-content hidden">
      <div class="page-header">
        <div>
          <div class="eyebrow">Conta</div>
          <h2>Meu Perfil</h2>
        </div>
      </div>

      <form method="POST" action="atualizar_perfil_paciente.php">
        <div class="card-flat">
          <div class="card-body">
            <div class="profile-hero">
              <div class="profile-avatar-xl"><?= $iniciais ?></div>
              <div>
                <h3><?= htmlspecialchars($paciente['nome']) ?></h3>
                <p><?= htmlspecialchars($paciente['email']) ?></p>
              </div>
            </div>

            <div class="form-divider"><h5>Dados Pessoais</h5></div>
            <div class="form-grid form-mb">
              <div class="form-group">
                <label class="field-label">Nome completo</label>
                <input type="text" value="<?= htmlspecialchars($paciente['nome']) ?>" readonly>
              </div>
              <div class="form-group">
                <label class="field-label">CPF</label>
                <input type="text" value="<?= htmlspecialchars($paciente['cpf']) ?>" readonly>
              </div>
            </div>
            <div class="form-grid form-mb">
              <div class="form-group">
                <label class="field-label">Telefone</label>
                <input type="tel" name="telefone" value="<?= htmlspecialchars($paciente['telefone'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label class="field-label">Data de Nascimento</label>
                <input type="date" value="<?= $paciente['nascimento'] ?? '' ?>" readonly>
              </div>
            </div>
            <div class="form-group form-mb">
              <label class="field-label">E-mail</label>
              <input type="email" name="email" value="<?= htmlspecialchars($paciente['email']) ?>" required>
            </div>

            <div class="form-divider"><h5>Segurança</h5></div>
            <div class="form-group form-mb">
              <label class="field-label">Nova senha <span class="text-muted">(deixe em branco para manter a atual)</span></label>
              <input type="password" name="nova_senha" placeholder="Nova senha">
            </div>
          </div>
          <div class="card-footer">
            <button type="button" class="btn btn-outline" id="btn-cancelar-perfil">Cancelar</button>
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
          </div>
        </div>
      </form>
    </main>

  </div><!-- /main-area -->
</div><!-- /app-shell -->

<!-- Modal de confirmação de cancelamento -->
<div id="modal-cancelar" class="modal-backdrop hidden">
  <div class="modal-box">
    <div class="modal-header">
      <span class="modal-title">Cancelar Consulta</span>
      <button class="modal-close" id="btn-fechar-modal">×</button>
    </div>
    <div class="callout callout-amber form-mb">
      <svg viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/></svg>
      <div>Cancelamentos com menos de 2h de antecedência podem gerar cobrança de taxa.</div>
    </div>
    <div class="form-group form-mb">
      <label class="field-label">Motivo *</label>
      <select id="motivo-cancelamento">
        <option value="">Selecione o motivo</option>
        <option>Compromisso pessoal</option>
        <option>Problema de saúde</option>
        <option>Vou remarcar</option>
        <option>Melhora do quadro</option>
        <option>Outro</option>
      </select>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" id="btn-modal-voltar">Voltar</button>
      <button class="btn btn-red-soft" id="btn-confirmar-cancelamento">Confirmar Cancelamento</button>
    </div>
  </div>
</div>

<div id="toast-root"></div>

<!-- Dados do banco injetados pelo PHP para uso no JavaScript -->
<script>
  const MEDICOS           = <?= json_encode($medicos) ?>;
  const HORARIOS_OCUPADOS = <?= json_encode($horarios_ocupados) ?>;
  const ESPECIALIDADES    = <?= json_encode($especialidades) ?>;
</script>

<script src="utils.js"></script>
<script src="paciente.js"></script>
</body>
</html>
