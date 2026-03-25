/* ============================================================
   DR Medical Center — JavaScript do Painel Administrativo
   ============================================================ */
'use strict';

/* ── Navegação entre páginas ──────────────────────────────── */
document.querySelectorAll('.nav-link[data-page]').forEach(link => {
  link.addEventListener('click', () => Nav.go(link.dataset.page));
});

document.getElementById('btn-ver-todas-admin')?.addEventListener('click', () => {
  Nav.go('a-consultas');
});

/* ── Abre a página correta após redirect com ?pagina= ─────── */
(function () {
  const params = new URLSearchParams(window.location.search);
  const pagina = params.get('pagina');
  if (pagina) setTimeout(() => Nav.go(pagina), 10);
})();

/* ── Filtros da tabela de consultas ───────────────────────── */
document.querySelectorAll('.filter-btn[data-filter-admin]').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.filter-btn[data-filter-admin]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const filtro = btn.dataset.filterAdmin;
    document.querySelectorAll('.linha-consulta-admin').forEach(linha => {
      linha.style.display = (filtro === 'todas' || linha.dataset.status === filtro) ? '' : 'none';
    });
  });
});

/* ================================================================
   CRUD DE MÉDICOS
   ================================================================ */
const formMedicoWrap = document.getElementById('form-medico-wrap');

document.getElementById('btn-novo-medico')?.addEventListener('click', () => {
  document.getElementById('medico-acao').value  = 'cadastrar';
  document.getElementById('medico-id').value    = '';
  document.getElementById('medico-nome').value  = '';
  document.getElementById('medico-crm').value   = '';
  document.getElementById('medico-esp').value   = '';
  document.getElementById('medico-email').value = '';
  document.getElementById('medico-senha').value = '';
  document.getElementById('medico-bio').value   = '';
  document.getElementById('form-medico-titulo').textContent = 'Cadastrar Novo Médico';
  document.getElementById('senha-hint').textContent = '(obrigatória no cadastro)';
  document.getElementById('medico-table-wrap')?.classList.add('hidden');
  formMedicoWrap?.classList.remove('hidden');
  formMedicoWrap?.scrollIntoView({ behavior: 'smooth', block: 'start' });
});

document.querySelectorAll('.btn-editar-medico').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('medico-acao').value  = 'editar';
    document.getElementById('medico-id').value    = btn.dataset.id;
    document.getElementById('medico-nome').value  = btn.dataset.nome;
    document.getElementById('medico-crm').value   = btn.dataset.crm;
    document.getElementById('medico-esp').value   = btn.dataset.esp;
    document.getElementById('medico-email').value = btn.dataset.email;
    document.getElementById('medico-senha').value = '';
    document.getElementById('medico-bio').value   = btn.dataset.bio;
    document.getElementById('form-medico-titulo').textContent = 'Editar Médico';
    document.getElementById('senha-hint').textContent = '(deixe em branco para manter a atual)';
    document.getElementById('medico-table-wrap')?.classList.add('hidden');
    formMedicoWrap?.classList.remove('hidden');
    formMedicoWrap?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
});

document.getElementById('btn-cancelar-medico')?.addEventListener('click', () => {
  formMedicoWrap?.classList.add('hidden');
  document.getElementById('medico-table-wrap')?.classList.remove('hidden');
});

/* ================================================================
   CRUD DE PACIENTES
   ================================================================ */
const formPacienteWrap = document.getElementById('form-paciente-wrap');

document.getElementById('btn-novo-paciente')?.addEventListener('click', () => {
  document.getElementById('pac-acao').value       = 'cadastrar';
  document.getElementById('pac-id').value         = '';
  document.getElementById('pac-nome').value       = '';
  document.getElementById('pac-cpf').value        = '';
  document.getElementById('pac-email').value      = '';
  document.getElementById('pac-telefone').value   = '';
  document.getElementById('pac-nascimento').value = '';
  document.getElementById('pac-senha').value      = '';
  document.getElementById('form-paciente-titulo').textContent = 'Cadastrar Novo Paciente';
  document.getElementById('pac-senha-hint').textContent = '(obrigatória no cadastro)';
  document.getElementById('paciente-table-wrap')?.classList.add('hidden');
  formPacienteWrap?.classList.remove('hidden');
  formPacienteWrap?.scrollIntoView({ behavior: 'smooth', block: 'start' });
});

document.querySelectorAll('.btn-editar-paciente').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('pac-acao').value       = 'editar';
    document.getElementById('pac-id').value         = btn.dataset.id;
    document.getElementById('pac-nome').value       = btn.dataset.nome;
    document.getElementById('pac-cpf').value        = btn.dataset.cpf;
    document.getElementById('pac-email').value      = btn.dataset.email;
    document.getElementById('pac-telefone').value   = btn.dataset.telefone;
    document.getElementById('pac-nascimento').value = btn.dataset.nascimento;
    document.getElementById('pac-senha').value      = '';
    document.getElementById('form-paciente-titulo').textContent = 'Editar Paciente';
    document.getElementById('pac-senha-hint').textContent = '(deixe em branco para manter)';
    document.getElementById('paciente-table-wrap')?.classList.add('hidden');
    formPacienteWrap?.classList.remove('hidden');
    formPacienteWrap?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
});

document.getElementById('btn-cancelar-paciente')?.addEventListener('click', () => {
  formPacienteWrap?.classList.add('hidden');
  document.getElementById('paciente-table-wrap')?.classList.remove('hidden');
});

/* ================================================================
   NOVA CONSULTA (admin agenda pelo paciente presencial)
   ================================================================ */
const formConsultaWrap = document.getElementById('form-consulta-wrap');

document.getElementById('btn-nova-consulta-admin')?.addEventListener('click', () => {
  formConsultaWrap?.classList.remove('hidden');
  formConsultaWrap?.scrollIntoView({ behavior: 'smooth', block: 'start' });
});

document.getElementById('btn-cancelar-consulta')?.addEventListener('click', () => {
  formConsultaWrap?.classList.add('hidden');
});
