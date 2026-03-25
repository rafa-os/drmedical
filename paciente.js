/* ============================================================
   DR Medical Center — JavaScript do Portal do Paciente
   Responsável por:
   - Navegação entre as páginas do painel
   - Renderizar os cards de médicos
   - Renderizar as pílulas de especialidade
   - Mostrar o painel de horários disponíveis
   - Preencher o formulário de agendamento
   - Filtrar a tabela de consultas
   ============================================================ */

'use strict';

/* ── Navegação entre as páginas (sidebar) ─────────────────── */
document.querySelectorAll('.nav-link[data-page]').forEach(link => {
  link.addEventListener('click', () => Nav.go(link.dataset.page));
});

/* ── Atalhos rápidos na home ──────────────────────────────── */
document.getElementById('btn-quick-doctors')?.addEventListener('click', () => {
  Nav.go('p-doctors');
});

/* ── Botão principal "Agendar Consulta" na home ───────────── */
document.getElementById('btn-home-agendar')?.addEventListener('click', () => {
  Nav.go('p-doctors');
});

document.getElementById('btn-quick-appointments')?.addEventListener('click', () => {
  Nav.go('p-appointments');
});

document.getElementById('btn-ver-todas')?.addEventListener('click', () => {
  Nav.go('p-appointments');
});

document.getElementById('btn-agendar-agora')?.addEventListener('click', () => {
  Nav.go('p-doctors');
});

/* ── Página de consultas ──────────────────────────────────── */
document.getElementById('btn-nova-consulta')?.addEventListener('click', () => {
  Nav.go('p-doctors');
});

/* ── Filtros da tabela de consultas ───────────────────────── */
document.querySelectorAll('.filter-btn[data-filter]').forEach(btn => {
  btn.addEventListener('click', () => {
    // Marca o botão ativo
    document.querySelectorAll('.filter-btn[data-filter]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const filtro = btn.dataset.filter;

    // Mostra/oculta as linhas conforme o status
    document.querySelectorAll('.linha-consulta').forEach(linha => {
      const visivel = filtro === 'todas' || linha.dataset.status === filtro;
      linha.style.display = visivel ? '' : 'none';
    });
  });
});

/* ── Botão cancelar (confirmação antes de enviar o form) ──── */
document.querySelectorAll('.btn-cancelar').forEach(btn => {
  btn.addEventListener('click', e => {
    const confirmado = confirm('Tem certeza que deseja cancelar esta consulta?');
    if (!confirmado) {
      e.preventDefault(); // impede o envio do formulário
    }
  });
});

/* ── Tela de agendamento ──────────────────────────────────── */
document.getElementById('btn-back-doctors')?.addEventListener('click', () => {
  Nav.go('p-doctors');
});

document.getElementById('btn-cancel-schedule')?.addEventListener('click', () => {
  Nav.go('p-doctors');
});

/* ── Perfil ───────────────────────────────────────────────── */
document.getElementById('btn-cancelar-perfil')?.addEventListener('click', () => {
  Toast.show('Alterações descartadas.', 'info');
});



/* ── Modal de cancelamento ────────────────────────────────── */
document.getElementById('btn-fechar-modal')?.addEventListener('click', () => {
  Modal.close('modal-cancelar');
});

document.getElementById('btn-modal-voltar')?.addEventListener('click', () => {
  Modal.close('modal-cancelar');
});

/* ================================================================
   PÁGINA DE MÉDICOS — cards, pílulas, painel de horários
   Os dados vêm de MEDICOS, ESPECIALIDADES e HORARIOS_OCUPADOS
   (injetados pelo PHP em paciente.php via json_encode)
   ================================================================ */

let medicoSelecionado = null;
let dataSelecionada   = null;
let horaSelecionada   = null;

/* ── Renderiza as pílulas de filtro de especialidade ─────── */
function renderizarEspecialidades() {
  const container = document.getElementById('spec-pills');
  if (!container) return;

  // Pílula "Todas" — sempre a primeira
  const pilulaTodas = document.createElement('button');
  pilulaTodas.className    = 'spec-pill active';
  pilulaTodas.textContent  = 'Todas';
  pilulaTodas.addEventListener('click', () => {
    selecionarEspecialidade(pilulaTodas, 'Todas');
  });
  container.appendChild(pilulaTodas);

  // Uma pílula para cada especialidade vinda do banco
  ESPECIALIDADES.forEach(esp => {
    const pilula = document.createElement('button');
    pilula.className   = 'spec-pill';
    pilula.textContent = esp.nome;
    pilula.addEventListener('click', () => {
      selecionarEspecialidade(pilula, esp.nome);
    });
    container.appendChild(pilula);
  });
}

/* Filtra os cards pela especialidade selecionada */
function selecionarEspecialidade(pilulaSelecionada, especialidade) {
  document.querySelectorAll('.spec-pill').forEach(p => p.classList.remove('active'));
  pilulaSelecionada.classList.add('active');

  const filtrados = especialidade === 'Todas'
    ? MEDICOS
    : MEDICOS.filter(m => m.especialidade === especialidade);

  renderizarCards(filtrados);
  resetarPainelHorario();
}

/* ── Renderiza os cards de médico na grade ───────────────── */
function renderizarCards(lista) {
  const grid = document.getElementById('doctors-grid');
  if (!grid) return;

  if (!lista.length) {
    grid.innerHTML = '<p class="empty-state">Nenhum médico encontrado para esta especialidade.</p>';
    return;
  }

  grid.innerHTML = lista.map(m => {
    // Iniciais para o avatar (ex: "Carlos Mendes" → "CM")
    const partes   = m.nome.split(' ');
    const iniciais = (partes[0][0] + (partes[partes.length - 1][0] || '')).toUpperCase();
    const selecionado = medicoSelecionado?.id == m.id ? 'selected' : '';
    // Gera caminho da foto: remove título Dr./Dra., pega primeiro nome, lowercase
    const primeiroNome = m.nome.replace(/^(Dr\.|Dra\.)\s*/i, '').trim().split(' ')[0].toLowerCase();
    const fotoPath = `img/${primeiroNome}.jpg`;
    const avatarHtml = `<div class="doctor-avatar-wrap">
      <img src="${fotoPath}" alt="${m.nome}" class="doctor-avatar doctor-avatar-photo" data-iniciais="${iniciais}"
        onload="this.style.display='block';this.previousElementSibling&&(this.previousElementSibling.style.display='none')"
        onerror="this.style.display='none';this.nextElementSibling&&(this.nextElementSibling.style.display='flex')">
      <div class="doctor-avatar" style="display:none">${iniciais}</div>
    </div>`;

    return `
      <div class="doctor-card ${selecionado}" data-id="${m.id}">
        <div class="doctor-card-header">
          ${avatarHtml}
          <div class="doctor-card-info">
            <div class="doctor-card-name">${m.nome}</div>
            <div class="doctor-card-crm">${m.crm}</div>
            <span class="doctor-card-spec">${m.especialidade}</span>
          </div>
        </div>
        <div class="doctor-card-body">
          ${m.bio ? `<p class="doctor-card-bio">${m.bio}</p>` : ''}

        </div>
        <div class="doctor-card-footer">
          <button class="btn btn-sm btn-green btn-ver-horarios" data-id="${m.id}">
            Ver Horários
          </button>
        </div>
      </div>
    `;
  }).join('');

  // Evento de clique no botão "Ver Horários" de cada card
  grid.querySelectorAll('.btn-ver-horarios').forEach(btn => {
    btn.addEventListener('click', () => {
      const medico = MEDICOS.find(m => m.id == btn.dataset.id);
      if (medico) mostrarPainelHorario(medico);
    });
  });
}

/* ── Mostra o painel lateral com datas e horários ─────────── */
function mostrarPainelHorario(medico) {
  medicoSelecionado = medico;

  // Atualiza o visual dos cards (marca o selecionado)
  const especialidadeAtiva = document.querySelector('.spec-pill.active')?.textContent;
  const listaAtual = especialidadeAtiva === 'Todas'
    ? MEDICOS
    : MEDICOS.filter(m => m.especialidade === especialidadeAtiva);
  renderizarCards(listaAtual);

  const painel = document.getElementById('schedule-panel');
  if (!painel) return;

  const nomesSemana = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
  const nomesMes    = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];

  // Gera os próximos 28 dias úteis (excluindo fins de semana)
  const diasUteis = [];
  let d = new Date();
  while (diasUteis.length < 28) {
    const dow = d.getDay();
    if (dow !== 0 && dow !== 6) diasUteis.push(new Date(d));
    d.setDate(d.getDate() + 1);
  }

  // Controle de paginação: mostra 5 dias por vez
  const POR_PAGINA = 5;
  let paginaDia = 0;

  function renderDias() {
    const inicio = paginaDia * POR_PAGINA;
    const fatia  = diasUteis.slice(inicio, inicio + POR_PAGINA);

    const btnPrev = painel.querySelector('#dia-prev');
    const btnNext = painel.querySelector('#dia-next');
    if (btnPrev) btnPrev.disabled = (paginaDia === 0);
    if (btnNext) btnNext.disabled = (inicio + POR_PAGINA >= diasUteis.length);

    const strip = painel.querySelector('#date-strip');
    if (!strip) return;

    strip.innerHTML = fatia.map((dia, i) => {
      const iso    = dia.toISOString().split('T')[0];
      const ativo  = (iso === dataSelecionada) ? 'active' : (i === 0 && paginaDia === 0 ? 'active' : '');
      return `
        <button class="date-btn ${ativo}" data-date="${iso}">
          <span class="date-btn-weekday">${nomesSemana[dia.getDay()]}</span>
          <span class="date-btn-num">${dia.getDate()}</span>
          <span class="date-btn-month">${nomesMes[dia.getMonth()]}</span>
        </button>`;
    }).join('');

    // Clique nos dias
    strip.querySelectorAll('.date-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        strip.querySelectorAll('.date-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        dataSelecionada = btn.dataset.date;
        carregarSlots(dataSelecionada);
      });
    });
  }

  // Monta o HTML do painel com setas
  const nomeSemTitulo = medico.nome.replace(/^(Dr\.|Dra\.)\s*/i, '').trim();
  const primeiroNomePanel = nomeSemTitulo.split(' ')[0].toLowerCase();
  const iniciaisPanel = (nomeSemTitulo.split(' ')[0][0] + (nomeSemTitulo.split(' ').at(-1)[0] || '')).toUpperCase();
  const panelAvatar = `<div class="doctor-avatar-wrap">
    <img src="img/${primeiroNomePanel}.jpg" alt="${medico.nome}" class="panel-doctor-photo"
      onerror="this.style.display='none';this.nextElementSibling&&(this.nextElementSibling.style.display='flex')">
    <div class="panel-doctor-initials" style="display:none">${iniciaisPanel}</div>
  </div>`;

  painel.innerHTML = `
    <div class="panel-header">
      ${panelAvatar}
      <div>
        <div class="panel-doctor-name">${medico.nome}</div>
        <div class="panel-doctor-spec">${medico.especialidade} · ${medico.crm}</div>
      </div>
    </div>
    <div class="panel-section">
      <div class="panel-label">Selecione o dia</div>
      <div class="date-nav-row">
        <button class="date-nav-arrow" id="dia-prev" disabled>
          <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <div class="date-strip" id="date-strip"></div>
        <button class="date-nav-arrow" id="dia-next">
          <svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      </div>
    </div>
    <div class="panel-section" id="slots-section">
      <div class="panel-label">Horários disponíveis</div>
      <div class="slots-grid" id="slots-grid"></div>
    </div>
  `;

  // Inicializa dias e seleciona o primeiro
  dataSelecionada = diasUteis[0].toISOString().split('T')[0];
  renderDias();
  carregarSlots(dataSelecionada);

  // Setas de navegação entre semanas
  painel.querySelector('#dia-prev')?.addEventListener('click', () => {
    if (paginaDia > 0) { paginaDia--; renderDias(); }
  });
  painel.querySelector('#dia-next')?.addEventListener('click', () => {
    if ((paginaDia + 1) * POR_PAGINA < diasUteis.length) { paginaDia++; renderDias(); }
  });
}

/* ── Renderiza os botões de horário para data selecionada ─── */
function carregarSlots(data) {
  const grid = document.getElementById('slots-grid');
  if (!grid) return;

  // Horários padrão de atendimento
  const todos = [
    '08:00','08:30','09:00','09:30','10:00','10:30',
    '11:00','11:30','13:00','13:30','14:00','14:30',
    '15:00','15:30','16:00','16:30','17:00'
  ];

  // Verifica quais horários já estão ocupados para este médico/data
  const ocupados = HORARIOS_OCUPADOS
    .filter(h => h.medico_id == medicoSelecionado.id && h.data === data)
    .map(h => h.hora.substring(0, 5));

  grid.innerHTML = todos.map(hora => {
    const ocupado = ocupados.includes(hora);
    return `
      <button class="slot-btn" data-hora="${hora}" ${ocupado ? 'disabled' : ''}>
        ${hora}
      </button>
    `;
  }).join('');

  // Ao clicar em um horário disponível, vai para a tela de confirmação
  grid.querySelectorAll('.slot-btn:not(:disabled)').forEach(btn => {
    btn.addEventListener('click', () => {
      horaSelecionada = btn.dataset.hora;
      irParaConfirmacao();
    });
  });
}

/* ── Preenche o formulário e vai para a tela de confirmação ─ */
function irParaConfirmacao() {
  // Preenche os campos ocultos do formulário
  document.getElementById('field-medico-id').value = medicoSelecionado.id;
  document.getElementById('field-data').value       = dataSelecionada;
  document.getElementById('field-hora').value       = horaSelecionada;

  // Monta o resumo exibido na tela de confirmação
  const partes   = medicoSelecionado.nome.split(' ');
  const iniciais = (partes[0][0] + (partes[partes.length - 1][0] || '')).toUpperCase();
  const dataFmt  = new Date(dataSelecionada + 'T12:00').toLocaleDateString('pt-BR');

  document.getElementById('schedule-summary').innerHTML = `
    <div class="booking-summary">
      <div class="avatar av-navy" style="width:48px;height:48px;font-size:1rem;">${iniciais}</div>
      <div class="booking-summary-info">
        <div class="booking-summary-name">${medicoSelecionado.nome}</div>
        <div class="booking-summary-meta">
          ${medicoSelecionado.especialidade} · ${dataFmt} às ${horaSelecionada}
        </div>
      </div>
    </div>
  `;

  Nav.go('p-schedule');
}

/* Reseta o painel lateral (estado inicial) */
function resetarPainelHorario() {
  medicoSelecionado = null;
  const painel = document.getElementById('schedule-panel');
  if (painel) {
    painel.innerHTML = `
      <div class="empty-panel">
        <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        <p>Selecione um médico para ver os horários disponíveis</p>
      </div>
    `;
  }
}

/* ── Busca em tempo real por nome ou especialidade ─────────── */
document.getElementById('doctor-search-input')?.addEventListener('input', function () {
  const termo    = this.value.toLowerCase();
  const filtrado = MEDICOS.filter(m =>
    m.nome.toLowerCase().includes(termo) ||
    m.especialidade.toLowerCase().includes(termo)
  );
  renderizarCards(filtrado);
});

/* ── Inicialização ───────────────────────────────────────────
   Ao carregar a página, renderiza pílulas e cards com todos os médicos */
renderizarEspecialidades();
renderizarCards(MEDICOS);

/* ================================================================
   NAVEGAÇÃO POR PERÍODO NAS CONSULTAS DO PACIENTE
   Setas ← → navegam entre: Próximas / Realizadas / Canceladas / Todas
   ================================================================ */
const periodos = [
  { label: 'Próximas consultas',  filtro: r => ['agendada','confirmada'].includes(r.dataset.status) },
  { label: 'Consultas realizadas',filtro: r => r.dataset.status === 'realizada' },
  { label: 'Consultas canceladas',filtro: r => r.dataset.status === 'cancelada' },
  { label: 'Todas as consultas',  filtro: () => true },
];
let periodoAtual = 0;

function aplicarPeriodo() {
  const p     = periodos[periodoAtual];
  const label = document.getElementById('appt-period-label');
  if (label) label.textContent = p.label;

  document.querySelectorAll('.linha-consulta').forEach(linha => {
    linha.style.display = p.filtro(linha) ? '' : 'none';
  });

  const btnPrev = document.getElementById('appt-prev');
  const btnNext = document.getElementById('appt-next');
  if (btnPrev) btnPrev.disabled = (periodoAtual === 0);
  if (btnNext) btnNext.disabled = (periodoAtual === periodos.length - 1);
}

// Listeners nos botões (DOM já carregado pois o script fica no final do body)
document.getElementById('appt-prev')?.addEventListener('click', () => {
  if (periodoAtual > 0) { periodoAtual--; aplicarPeriodo(); }
});
document.getElementById('appt-next')?.addEventListener('click', () => {
  if (periodoAtual < periodos.length - 1) { periodoAtual++; aplicarPeriodo(); }
});

// Ao navegar para "Minhas Consultas", reinicia no período 0
function abrirConsultas() {
  periodoAtual = 0;
  aplicarPeriodo();
}
document.querySelectorAll('.nav-link[data-page="p-appointments"]').forEach(l =>
  l.addEventListener('click', () => setTimeout(abrirConsultas, 10))
);
document.getElementById('btn-quick-appointments')?.addEventListener('click', () =>
  setTimeout(abrirConsultas, 10)
);
document.getElementById('btn-ver-todas')?.addEventListener('click', () =>
  setTimeout(abrirConsultas, 10)
);
document.getElementById('btn-nova-consulta')?.addEventListener('click', () => Nav.go('p-doctors'));

// Aplicar na primeira carga (caso p-appointments esteja visível)
aplicarPeriodo();
