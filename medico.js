/* ============================================================
   DR Medical Center — JavaScript do Portal do Médico
   ============================================================ */

'use strict';

/* ── Navegação entre páginas ──────────────────────────────── */
document.querySelectorAll('.nav-link[data-page]').forEach(link => {
  link.addEventListener('click', () => Nav.go(link.dataset.page));
});

document.getElementById('btn-ver-agenda')?.addEventListener('click', () => Nav.go('d-agenda'));
document.getElementById('btn-ver-pacientes')?.addEventListener('click', () => Nav.go('d-patients'));
document.getElementById('btn-cancelar-perfil-med')?.addEventListener('click', () => Nav.go('d-home'));

/* ── Filtros do histórico de consultas ────────────────────── */
document.querySelectorAll('.filter-btn[data-filter-med]').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.filter-btn[data-filter-med]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const filtro = btn.dataset.filterMed;
    document.querySelectorAll('.linha-consulta-med').forEach(linha => {
      linha.style.display = (filtro === 'todas' || linha.dataset.status === filtro) ? '' : 'none';
    });
  });
});

/* ── Busca de pacientes ───────────────────────────────────── */
document.getElementById('patient-search')?.addEventListener('input', function () {
  const termo = this.value.toLowerCase();
  document.querySelectorAll('#patients-table tbody tr').forEach(linha => {
    linha.style.display = linha.textContent.toLowerCase().includes(termo) ? '' : 'none';
  });
});

/* ================================================================
   AGENDA SEMANAL COM SETAS — inicializa UMA ÚNICA VEZ
   ================================================================ */
let agendaIniciada = false;
let paginaAtual    = 0;
const POR_PAGINA   = 7;
let diasBtns       = [];

function renderPagina() {
  const inicio = paginaAtual * POR_PAGINA;
  const fim    = inicio + POR_PAGINA;

  diasBtns.forEach((btn, i) => {
    btn.style.display = (i >= inicio && i < fim) ? 'flex' : 'none';
  });

  const btnPrev = document.getElementById('agenda-prev');
  const btnNext = document.getElementById('agenda-next');
  if (btnPrev) btnPrev.disabled = (paginaAtual === 0);
  if (btnNext) btnNext.disabled = (fim >= diasBtns.length);
}

function selecionarDia(btn) {
  diasBtns.forEach(d => d.classList.remove('active'));
  btn.classList.add('active');

  const dataSel = btn.dataset.date;
  const [ano, mes, dia] = dataSel.split('-');

  const label = document.getElementById('agenda-label');
  if (label) label.textContent = `Consultas — ${dia}/${mes}/${ano}`;

  const linhas = document.querySelectorAll('.agenda-row');
  let visiveis = 0;
  linhas.forEach(linha => {
    const mostrar = (linha.dataset.date === dataSel);
    linha.classList.toggle('hidden', !mostrar);
    if (mostrar) visiveis++;
  });

  const count = document.getElementById('agenda-count');
  const vazia = document.getElementById('agenda-vazia');
  if (count) count.textContent = `${visiveis} consulta(s)`;
  if (vazia) vazia.classList.toggle('hidden', visiveis > 0);
}

function inicializarAgenda() {
  // Garante inicialização única
  if (agendaIniciada) {
    renderPagina(); // apenas re-renderiza a página atual
    return;
  }

  diasBtns = Array.from(document.querySelectorAll('#week-strip .week-day'));
  if (!diasBtns.length) return;

  agendaIniciada = true;

  // Esconde todos os dias primeiro
  diasBtns.forEach(btn => { btn.style.display = 'none'; });

  // Renderiza a primeira página
  renderPagina();

  // Seleciona hoje
  const hoje = diasBtns.find(d => d.classList.contains('active')) || diasBtns[0];
  if (hoje) selecionarDia(hoje);

  // Seta esquerda — listener único
  document.getElementById('agenda-prev')?.addEventListener('click', () => {
    if (paginaAtual > 0) { paginaAtual--; renderPagina(); }
  });

  // Seta direita — listener único
  document.getElementById('agenda-next')?.addEventListener('click', () => {
    if ((paginaAtual + 1) * POR_PAGINA < diasBtns.length) { paginaAtual++; renderPagina(); }
  });

  // Clique em cada dia
  diasBtns.forEach(btn => {
    btn.addEventListener('click', () => selecionarDia(btn));
  });
}

// Inicializa ao clicar em "Minha Agenda" na sidebar
document.querySelectorAll('.nav-link[data-page="d-agenda"]').forEach(link => {
  link.addEventListener('click', () => setTimeout(inicializarAgenda, 30));
});

// Inicializa também pelo botão "Ver Agenda de Hoje" do banner
document.getElementById('btn-ver-agenda')?.addEventListener('click', () => {
  setTimeout(inicializarAgenda, 30);
});

// Inicializa se d-agenda já está visível ao carregar (ex: redirect após ação)
document.addEventListener('DOMContentLoaded', () => {
  const paginaAgenda = document.getElementById('d-agenda');
  if (paginaAgenda && !paginaAgenda.classList.contains('hidden')) {
    inicializarAgenda();
  }
});

/* ── Filtros do histórico de consultas ────────────────────── */
document.querySelectorAll('.filter-btn[data-filter-hist]').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.filter-btn[data-filter-hist]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const filtro = btn.dataset.filterHist;
    document.querySelectorAll('.linha-hist').forEach(linha => {
      linha.style.display = (filtro === 'todas' || linha.dataset.status === filtro) ? '' : 'none';
    });
  });
});
