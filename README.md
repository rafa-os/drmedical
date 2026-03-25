# DR Medical Center — Sistema de Gestão de Consultas

Sistema web para gerenciamento de consultas médicas com portais para pacientes, médicos e administradores. Desenvolvido como projeto acadêmico no curso Técnico em Desenvolvimento de Sistemas — SENAI "Roberto Simonsen".

---

## Tecnologias Utilizadas

- **Frontend:** HTML5, CSS3, JavaScript
- **Backend:** PHP (sem frameworks)
- **Banco de dados:** MySQL
- **Ambiente:** XAMPP
- **Editor:** Visual Studio Code
- **Prototipagem:** Figma

---

## Estrutura do Projeto
```
drmedical/
├── index.html                  → Landing page pública da clínica
├── login.html                  → Tela de login e cadastro do paciente
├── login.php                   → Processa login e cadastro do paciente
├── paciente.php                → Portal do paciente
├── paciente.js                 → Lógica do portal do paciente
├── medico.php                  → Portal do médico
├── medico.js                   → Lógica do portal do médico
├── interno.php                 → Painel administrativo (acesso restrito)
├── admin.js                    → Lógica do painel administrativo
├── agendar.php                 → Processa agendamento de consultas
├── cancelar.php                → Processa cancelamento pelo paciente
├── confirmar_paciente.php      → Processa confirmação de presença pelo paciente
├── confirmar_medico.php        → Processa cancelamento de consultas pelo médico
├── atualizar_perfil.php        → Atualiza perfil do médico
├── atualizar_perfil_paciente.php → Atualiza perfil do paciente
├── recuperar_senha.php         → Recuperação de senha (paciente e médico)
├── admin_medico.php            → CRUD de médicos pelo administrador
├── admin_paciente.php          → CRUD de pacientes pelo administrador
├── admin_consulta.php          → Gestão de consultas pelo administrador
├── logout.php                  → Encerra sessão e redireciona ao login
├── conexao.php                 → Conexão com o banco de dados MySQL
├── banco.sql                   → Script para criar o banco e inserir dados de teste
├── style.css                   → Design system completo
├── utils.js                    → Funções JS compartilhadas (Toast, Nav)
├── paciente.js                 → Lógica do portal do paciente
├── medico.js                   → Lógica do portal do médico
└── img/                        → Imagens do sistema (logo, fotos dos médicos)
```

---

## Como Rodar no XAMPP

### 1. Instale o XAMPP
- Baixe em https://www.apachefriends.org
- Abra o **XAMPP Control Panel** e clique em **Start** no **Apache** e no **MySQL**

### 2. Copie os arquivos do projeto
- Vá até `C:\xampp\htdocs\`
- Crie uma pasta chamada `drmedical`
- Copie todos os arquivos do projeto para dentro dela

### 3. Importe o banco de dados
- Abra o **MySQL Workbench** e conecte ao servidor local
- Crie um schema chamado `drmedical`
- Vá em **File → Open SQL Script**, selecione o arquivo `banco.sql` e clique em **Execute** (ícone do raio)

### 4. Configure a conexão
- Abra o arquivo `conexao.php` e verifique as credenciais:
```php
$host  = 'localhost';
$user  = 'root';
$pass  = '';
$banco = 'drmedical';
```

### 5. Acesse o sistema
- Abra o navegador em: http://localhost/drmedical/

---

## Credenciais para Teste

| Portal        | Login                   | Senha  |
|---------------|-------------------------|--------|
| Paciente      | joao@email.com          | 123456 |
| Médico        | 123456/SP               | 123456 |
| Administrador | admin@drmedical.com     | 123456 |

> O painel administrativo é acessado diretamente por:
> http://localhost/drmedical/interno.php

---

## Fluxo Principal do Paciente

1. Acessa `index.html` → clica em **Agendar Consulta**
2. Faz login ou cria conta
3. No painel, clica em **Médicos**
4. Filtra por especialidade ou busca por nome
5. Clica em **Ver Horários** de um médico
6. Seleciona o dia e o horário disponível
7. Confirma o agendamento — consulta criada com status **agendada**
8. Pode confirmar presença (status **confirmada**) ou cancelar a consulta

## Fluxo Principal do Médico

1. Faz login com CRM (ex: `123456/SP`) e senha
2. Visualiza as consultas do dia no painel inicial
3. Acessa **Minha Agenda** para navegar pelos próximos 28 dias
4. Pode cancelar consultas agendadas ou confirmadas
5. Acessa **Pacientes** para ver o histórico de atendimentos
6. Acessa **Meu Perfil** para atualizar e-mail, bio e senha

## Fluxo Principal do Administrador

1. Acessa `interno.php` diretamente pelo navegador
2. Faz login com e-mail e senha institucionais
3. Visualiza estatísticas gerais no dashboard
4. Gerencia médicos: cadastrar, editar, ativar/desativar
5. Gerencia pacientes: cadastrar, editar, excluir
6. Gerencia consultas: agendar presencialmente, alterar status

---

## Banco de Dados

O banco `drmedical` é composto por 5 tabelas:

| Tabela | Descrição |
|---|---|
| `especialidades` | Especialidades médicas oferecidas |
| `administradores` | Usuários administradores do sistema |
| `medicos` | Médicos cadastrados na clínica |
| `pacientes` | Pacientes cadastrados |
| `consultas` | Consultas agendadas, confirmadas, realizadas ou canceladas |

---

## Autores

- **Daniel Silva dos Santos**
- **Rafael Oliveira de Souza**

Projeto desenvolvido para a disciplina de Desenvolvimento de Sistemas — SENAI "Roberto Simonsen", 2026.
