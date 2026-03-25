# DR Medical Center — Sistema de Gestão de Consultas

## Estrutura do Projeto

```
drmedical/
├── index.html          → Landing page pública da clínica
├── login.php           → Login e cadastro do paciente
├── paciente.php        → Portal do paciente (agenda, consultas, perfil)
├── medico.php          → Portal do médico (agenda do dia, pacientes)
├── interno.php         → Painel administrativo (acesso restrito)
├── agendar.php         → Processa o agendamento de consultas
├── cancelar.php        → Processa o cancelamento de consultas (paciente)
├── confirmar_medico.php→ Processa confirmar/cancelar consultas (médico)
├── logout.php          → Encerra a sessão e redireciona ao login
├── conexao.php         → Conexão com o banco de dados MySQL
├── banco.sql           → Script para criar o banco e inserir dados de teste
├── style.css           → Todo o CSS do sistema
├── utils.js            → Funções JS compartilhadas (Toast, Modal, Nav)
├── paciente.js         → Lógica do portal do paciente (cards, horários)
└── medico.js           → Lógica do portal do médico (navegação, busca)
```

---

## Como rodar no XAMPP

### 1. Instale o XAMPP
- Baixe em https://www.apachefriends.org
- Abra o **XAMPP Control Panel** e clique em **Start** no **Apache** e no **MySQL**

### 2. Copie os arquivos do projeto
- Vá até a pasta `C:\xampp\htdocs\`
- Crie uma pasta chamada `drmedical`
- Copie todos os arquivos do projeto para dentro dela

### 3. Importe o banco de dados
- Abra o navegador em http://localhost/phpmyadmin
- Clique em **Importar** no menu superior
- Selecione o arquivo `banco.sql` e clique em **Executar**

### 4. Acesse o sistema
- Abra o navegador em http://localhost/drmedical/index.html

---

## Credenciais para teste

| Portal        | Login                       | Senha       |
|---------------|-----------------------------|-------------|
| Paciente      | joao@email.com              | medico123   |
| Médico        | CRM/SP 123456               | medico123   |
| Médico        | carlos@drmedical.com        | medico123   |
| Administrador | admin@drmedical.com         | medico123   |

> O painel administrativo é acessado diretamente por:
> http://localhost/drmedical/interno.php

---

## Fluxo principal do paciente

1. Acessa `index.html` → clica em **Agendar Consulta**
2. Faz login ou cria conta em `login.php`
3. No painel, clica em **Médicos**
4. Filtra por especialidade ou busca por nome
5. Clica em **Ver Horários** de um médico
6. Seleciona o dia e o horário disponível
7. Preenche os dados e clica em **Confirmar Agendamento**
8. O formulário envia para `agendar.php`, que salva no banco e retorna ao painel

---

## Tecnologias utilizadas

- **Frontend:** HTML, CSS, JavaScript (puro, sem frameworks)
- **Backend:** PHP com sessões e mysqli
- **Banco de dados:** MySQL (via XAMPP / phpMyAdmin)
