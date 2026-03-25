/* ============================================================
   DR Medical Center — Utilitários Compartilhados
   Carregado em todas as páginas do portal.
   Disponibiliza: Toast, Modal, Nav
   ============================================================ */

'use strict';

/* ── Toast — notificação flutuante ────────────────────────── */
window.Toast = (() => {
  function show(mensagem, tipo = 'success', duracao = 3600) {
    const root = document.getElementById('toast-root');
    if (!root) return;

    const el = document.createElement('div');
    el.className  = `toast${tipo !== 'success' ? ' t-' + tipo : ''}`;
    el.textContent = mensagem;
    root.appendChild(el);

    // Remove o toast após a duração definida
    setTimeout(() => {
      el.style.transition = 'opacity .28s';
      el.style.opacity    = '0';
      setTimeout(() => el.remove(), 300);
    }, duracao);
  }

  return { show };
})();

/* ── Modal — janelas de diálogo ───────────────────────────── */
window.Modal = (() => {
  function open(id) {
    const el = document.getElementById(id);
    if (el) {
      el.classList.remove('hidden');
      document.body.style.overflow = 'hidden'; // trava o scroll
    }
  }

  function close(id) {
    const el = document.getElementById(id);
    if (el) {
      el.classList.add('hidden');
      document.body.style.overflow = '';
    }
  }

  function closeAll() {
    document.querySelectorAll('.modal-backdrop').forEach(m => m.classList.add('hidden'));
    document.body.style.overflow = '';
  }

  return { open, close, closeAll };
})();

// Fecha o modal ao clicar fora (no backdrop escuro)
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-backdrop')) Modal.closeAll();
});

/* ── Nav — navegação entre páginas do painel ──────────────── */
window.Nav = (() => {
  // Título e subtítulo de cada página, exibidos na topbar
  const paginas = {
    'p-home':         { titulo: 'Painel',            sub: 'Visão geral da sua conta' },
    'p-doctors':      { titulo: 'Médicos',            sub: 'Encontre especialistas e agende sua consulta' },
    'p-schedule':     { titulo: 'Agendar Consulta',   sub: 'Revise e confirme seu agendamento' },
    'p-appointments': { titulo: 'Minhas Consultas',   sub: 'Histórico e próximas consultas' },
    'p-profile':      { titulo: 'Meu Perfil',         sub: 'Seus dados cadastrais' },
    'd-home':         { titulo: 'Painel',             sub: 'Visão geral do dia' },
    'd-agenda':       { titulo: 'Minha Agenda',       sub: 'Semana atual e histórico de consultas' },
    'd-patients':     { titulo: 'Pacientes',          sub: 'Lista de pacientes cadastrados' },
    'a-home':         { titulo: 'Painel',             sub: 'Visão geral do sistema' },
    'a-consultas':    { titulo: 'Consultas',          sub: 'Todos os agendamentos registrados' },
    'a-medicos':      { titulo: 'Médicos',            sub: 'Corpo clínico cadastrado' },
  };

  function go(paginaId) {
    // Esconde todas as páginas
    document.querySelectorAll('.js-page').forEach(p => p.classList.add('hidden'));

    // Mostra a página solicitada
    const pagina = document.getElementById(paginaId);
    if (pagina) pagina.classList.remove('hidden');

    // Atualiza o link ativo na sidebar
    document.querySelectorAll('.nav-link[data-page]').forEach(link => {
      link.classList.toggle('active', link.dataset.page === paginaId);
    });

    // Atualiza o título na topbar
    const info = paginas[paginaId];
    if (info) {
      const tituloEl = document.getElementById('topbar-title');
      const subEl    = document.getElementById('topbar-sub');
      if (tituloEl) tituloEl.textContent = info.titulo;
      if (subEl)    subEl.textContent    = info.sub;
    }

    // Volta ao topo
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  return { go };
})();
