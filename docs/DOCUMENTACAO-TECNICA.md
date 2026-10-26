# Sistema de RH — Documentação Técnica

Sistema completo de Recursos Humanos desenvolvido em **PHP puro (orientado a objetos) + MySQL/MariaDB + Bootstrap 5 + JavaScript vanilla**, pronto para rodar em **XAMPP (localhost)**.

## Novidades da v2

Todos os itens da lista de melhorias identificada na v1 foram implementados:

| # | Item | Onde está |
|---|---|---|
| 1 | Meu Perfil (qualquer usuário troca a própria senha) | `modules/perfil/` |
| 2 | Recuperação de senha ("esqueci minha senha") | `modules/auth/esqueci_senha.php`, `redefinir_senha.php` |
| 3 | Foto do funcionário | Campo de upload no cadastro + `modules/funcionarios/foto.php` |
| 4 | Página pública de vagas + candidatura externa | `public/vagas.php`, `public/candidatar.php` |
| 5 | Log de auditoria com tela de consulta | `modules/auditoria/` |
| 6 | Rate limiting no login (bloqueio após 5 tentativas) | `classes/Usuario.php` |
| 7 | Cálculo automático de impostos na folha | `config/impostos.php` |
| 8 | Cron real para notificações | `cron/gerar_notificacoes.php` |
| 9 | Paginação (funcionários, notificações, auditoria) | `includes/paginacao.php` |
| 10 | Proteção de currículos (antes eram link direto) | `modules/recrutamento/curriculo.php` + `.htaccess` |
| 11 | Testes automatizados (sem Composer/PHPUnit) | `tests/` — rode com `php tests/executar.php` |
| 12 | Exportação CSV (Excel) + impressão/PDF via navegador | Botões em Folha de Pagamento e Relatórios |
| 13 | Paginação de notificações | Incluída no item 9 |

**Dark mode** também foi adicionado: botão de alternância no topo (ícone de lua/sol), tema salvo no navegador (`localStorage`) e também no banco por usuário, sem "flash" de tela clara ao carregar.

Se você já tinha a v1 instalada, rode `database/migration_v2.sql` no phpMyAdmin antes de usar a v2 (instalações novas não precisam — `schema.sql` já vem completo).

---

## 1. Instalação (XAMPP)

1. Copie a pasta `rh-sistema` inteira para `C:\xampp\htdocs\` (Windows) ou `/Applications/XAMPP/htdocs/` (Mac) ou `/opt/lampp/htdocs/` (Linux).
2. Abra o **XAMPP Control Panel** e inicie os módulos **Apache** e **MySQL**.
3. Acesse `http://localhost/phpmyadmin`, crie/importe o banco:
   - Vá em **Importar** → selecione o arquivo `database/schema.sql` → **Executar**.
   - Isso cria o banco `rh_sistema`, todas as 14 tabelas e as contas iniciais dos cinco perfis do sistema.
4. Acesse `http://localhost/rh-sistema/` no navegador.
5. Login inicial:
   - **E-mail:** `superadmin@empresa.com`
   - **Senha:** `Admin@123`
   - ⚠️ **Troque essa senha imediatamente** (Usuários e Acessos → editar o próprio usuário).

Se o nome da pasta dentro de `htdocs` for diferente de `rh-sistema`, ajuste a constante `BASE_URL` em `config/config.php`.

Se o MySQL do XAMPP usar usuário/senha diferentes do padrão (`root` sem senha), ajuste `config/database.php`.

---

## 2. Arquitetura e decisões técnicas

O projeto segue um padrão **MVC "lite"** (sem framework), adequado para rodar em qualquer hospedagem PHP compartilhada/XAMPP sem dependências externas (Composer não é necessário):

```
rh-sistema/
├── config/             # Configuração (não contém lógica de negócio)
│   ├── config.php        # Constantes globais, sessão, timezone
│   └── database.php      # Credenciais de conexão MySQL
├── classes/            # MODEL — uma classe por entidade, só acesso a dados (PDO)
│   ├── Database.php      # Conexão PDO Singleton
│   ├── Funcionario.php, Departamento.php, Cargo.php, Usuario.php,
│   ├── Presenca.php, Ferias.php, FolhaPagamento.php,
│   ├── Vaga.php, Candidato.php, Entrevista.php,
│   ├── Avaliacao.php, Documento.php, Notificacao.php
├── includes/            # Infraestrutura compartilhada
│   ├── bootstrap.php      # Ponto único de inicialização + autoload das classes
│   ├── auth.php           # Autenticação, RBAC (exigirPerfil), CSRF, auditoria
│   ├── functions.php      # Helpers: formatação, upload, mensagens flash, notificações
│   ├── header.php / sidebar.php / footer.php   # Layout (VIEW) comum
├── modules/             # CONTROLLER + VIEW — uma pasta por funcionalidade
│   ├── auth/              # login, logout, esqueci/redefinir senha, acesso negado
│   ├── perfil/             # "Meu Perfil" — troca de senha por qualquer usuário
│   ├── auditoria/          # Consulta do log de ações (Admin/RH)
│   ├── dashboard/         # KPIs e gráficos
│   ├── funcionarios/      # CRUD de funcionários + foto + abas (documentos, avaliações...)
│   ├── departamentos/, cargos/     # CRUDs simples
│   ├── presenca/          # Ponto (bater entrada/saída) + lançamento manual
│   ├── ferias/             # Solicitação e aprovação de férias/licenças
│   ├── folha-pagamento/   # Geração automática (com impostos), ajustes, CSV, impressão
│   ├── recrutamento/      # Vagas, candidatos, entrevistas, download de currículo
│   ├── avaliacao/          # Avaliação de desempenho (4 critérios, nota 1–5)
│   ├── documentos/         # Upload/download seguro de arquivos
│   ├── relatorios/         # Gráficos consolidados + exportação CSV
│   ├── notificacoes/       # Central de notificações do usuário (paginada)
│   └── usuarios/           # Gestão de contas e perfis (apenas Administrador)
├── public/               # Páginas SEM login: vagas abertas + candidatura externa
├── cron/                 # Scripts para agendar via cron/Agendador de Tarefas
├── tests/                # Suíte de testes própria (ver seção 5)
├── assets/css/style.css, assets/js/scripts.js   # inclui dark mode e impressão
├── uploads/              # Arquivos enviados (fora do controle de versão)
│   ├── documentos/  (.htaccess bloqueia acesso direto — só via download.php)
│   ├── curriculos/  (acesso direto permitido — ver seção de melhorias)
│   └── fotos/       (.htaccess bloqueia acesso direto)
├── database/schema.sql    # Schema completo + seed (departamentos, cargos, admin)
└── index.php              # Redireciona para dashboard ou login
```

### Por que esse padrão, e não um framework (Laravel etc.)?
Você pediu HTML5/CSS/Bootstrap/JS/PHP puro para rodar em XAMPP. Um framework completo
exigiria Composer, configuração de roteamento e um passo de build — atrito desnecessário
para um ambiente local simples. Em compensação, a estrutura acima já separa
**Model (classes/)**, **lógica de página (topo dos arquivos em modules/)** e
**View (HTML dentro de modules/ + includes/)**, then aplicando os princípios abaixo:

- **DRY:** partials `_form.php` reaproveitados entre criar/editar; `bootstrap.php` centraliza
  includes; `header.php`/`footer.php`/`sidebar.php` evitam repetir o layout em 25+ páginas.
- **SRP (Single Responsibility):** cada classe em `classes/` cuida de uma única entidade;
  `auth.php` só cuida de autenticação/RBAC; `functions.php` só helpers puros.
- **Segurança por padrão:** toda query usa **prepared statements PDO** (nunca concatenação de
  SQL), toda saída HTML passa por `e()` (htmlspecialchars), todo formulário POST exige
  **token CSRF**, senhas usam `password_hash`/`password_verify` (bcrypt).

---

## 3. Perfis de acesso (RBAC)

| Perfil | Acesso |
|---|---|
| **Super Admin** | Acesso total ao sistema, incluindo gestão de utilizadores e auditoria. |
| **Administrador RH** | Gestão operacional de RH, sem administração de contas Super Admin. |
| **Gestor** | Acesso à sua equipa, presença, férias e avaliações do próprio departamento. |
| **Funcionário** | Acesso somente aos próprios dados, presença, férias, documentos e avaliações. |
| **Auditor** | Consulta somente leitura do log de auditoria e dashboard de rastreabilidade. |

O controle é feito centralmente por `exigirPerfil([...])` (em `includes/auth.php`), chamada
no topo de cada página. O Super Admin é reconhecido centralmente, enquanto os demais perfis
só passam nas listas explicitamente autorizadas. Além disso, páginas sensíveis (ex.:
`funcionarios/visualizar.php`, `documentos/download.php`) fazem uma segunda checagem por
registro para evitar **IDOR**. A base de dados está consolidada num único ficheiro,
`database/schema.sql`, que também contém dados fictícios para testar os cinco acessos.

Contas fictícias: `superadmin@empresa.com`, `administrador@empresa.com`, `gestor@empresa.com`,
`funcionario@empresa.com` e `auditor@empresa.com`. A palavra-passe inicial de todas é
`Admin@123`; altere-a após o primeiro login.

---

## 4. Regras de negócio implementadas

- **Ponto:** entrada após 09:00 é marcada automaticamente como "atraso"; horas além de 8h/dia
  viram "horas extras" automaticamente ao bater a saída.
- **Férias:** dias calculados automaticamente a partir do intervalo de datas; aprovação move o
  funcionário para status "em férias" e notifica o solicitante.
- **Folha de pagamento:** gerada em lote para todos os funcionários ativos; horas extras valem
  1,5× a hora normal; faltas não justificadas descontam 1 dia (8h); bônus/descontos adicionais
  (ex.: INSS/IRRF) são lançados manualmente, pois variam por legislação/região.
- **Notificações automáticas:** ao carregar o topo de qualquer página, o sistema verifica (uma
  vez por dia) aniversariantes e férias/licenças que começam nos próximos 7 dias, e cria
  notificações para RH/Administradores. *(Em produção, o ideal é mover isso para um cron job
  diário em vez de rodar a cada carregamento — ver seção 6).*

---

## 5. Como testar

O sistema foi **testado de ponta a ponta durante o desenvolvimento** (servidor PHP embutido +
MariaDB real), cobrindo:
- Login/logout, proteção CSRF, redirecionamento de usuários não autenticados;
- CRUD completo de funcionários, departamentos e cargos;
- Registro de ponto (entrada/saída) e lançamento manual;
- Geração e recálculo de folha de pagamento (incluindo o caminho de `UPDATE`, testado
  gerando a folha duas vezes seguidas);
- Solicitação e aprovação de férias, com notificação ao solicitante;
- Cadastro de vaga → candidato → entrevista → resultado;
- Avaliação de desempenho com cálculo automático da nota final;
- RBAC nos 4 perfis, incluindo bloqueio 403 de páginas restritas e proteção contra IDOR
  (funcionário tentando ver o perfil de outro funcionário pela URL).

Dois bugs reais foram encontrados e corrigidos nesse processo (documentados no código):
1. Faltava autoload das classes de `classes/` em `bootstrap.php` (`Usuario` não encontrada).
2. PDO com prepared statements nativos não permite reaproveitar o mesmo parâmetro nomeado
   duas vezes numa query `INSERT ... ON DUPLICATE KEY UPDATE` — corrigido usando sufixos
   `_upd` nos placeholders do trecho de UPDATE (`FolhaPagamento.php`, `Presenca.php`).

**Na v2**, o mesmo processo de teste ponta a ponta (subir MySQL real, testar via HTTP)
encontrou mais dois bugs reais:

3. `mb_strimwidth()` (usada para truncar a descrição de vagas) depende da extensão
   `mbstring`, que nem sempre está habilitada por padrão — corrigido com um helper
   `truncarTexto()` que usa `mb_strimwidth` quando disponível e cai para `substr()` caso
   contrário (`includes/functions.php`).
4. **Mismatch de timezone entre PHP e MySQL**: o PHP usa `America/Sao_Paulo`
   (`config/config.php`), mas o MySQL, por padrão, roda no timezone do sistema
   operacional (geralmente UTC). Isso fazia o token de "esqueci minha senha" — cuja
   validade é calculada em PHP e comparada com `NOW()` do MySQL — parecer expirado
   *imediatamente* após ser gerado (diferença de 3h entre os dois relógios). Corrigido
   sincronizando o `time_zone` da sessão MySQL com o timezone do PHP logo após abrir a
   conexão (`classes/Database.php`), resolvendo o problema na raiz para qualquer query
   futura que misture datas calculadas em PHP com `NOW()`/`CURRENT_TIMESTAMP` do MySQL
   (afeta também o agendamento de entrevistas, por exemplo).

### Para testar manualmente após a instalação
1. Faça login como admin e crie um departamento, um cargo e um funcionário.
2. Crie um usuário (Usuários e Acessos) vinculado a esse funcionário com perfil "Funcionário".
3. Faça login com essa nova conta e confirme que ela só vê seus próprios dados.
4. Bata o ponto de entrada/saída e confira em Presença (visão RH) que o registro aparece.
5. Gere a folha de pagamento do mês corrente e confira o cálculo.
6. Solicite férias como funcionário e aprove como RH — confira a notificação.

### Testes automatizados

O projeto inclui uma suíte de testes própria em `tests/` (sem Composer/PHPUnit, coerente
com a stack "framework-free" do projeto). Para rodar:

```
cd rh-sistema
php tests/executar.php
```

A suíte cobre funções puras (formatação, cálculo de paginação, tabela de impostos) e
testes de integração reais contra o banco configurado em `config/database.php`
(cálculo da folha de pagamento, lançamento de presença e o caminho de `UPDATE` do
`ON DUPLICATE KEY`). Os testes de integração criam seus próprios dados de fixture
(prefixados com `__TESTE_`) e os removem ao final, mesmo em caso de falha — não deixam
resíduo no banco. Rode com um banco de desenvolvimento, nunca direto em produção.

Saída esperada (11 testes na versão atual):
```
=== Executando suíte de testes do Sistema de RH ===

  [OK]    formatarMoeda formata valores em Real corretamente
  [OK]    formatarData converte Y-m-d para d/m/Y
  ...
---------------------------------------------
11 teste(s): 11 passou(aram), 0 falhou(aram).
```

---

## 6. Riscos conhecidos e melhorias recomendadas (atualizado na v2)

A maioria dos itens levantados na v1 foi endereçada (ver tabela "Novidades da v2" no
topo deste documento). O que ainda vale destacar como limitação real, mesmo após a v2:

1. **Tabela de impostos é ilustrativa.** `config/impostos.php` implementa uma tabela
   progressiva simplificada, não a legislação tributária vigente de nenhum país
   específico. Um contador precisa validar/ajustar as faixas antes de uso real.
2. **"Exportar PDF" na verdade é impressão do navegador** (`window.print()` com CSS
   dedicado). Funciona bem para "Salvar como PDF" no Chrome/Edge/Firefox, mas não é
   geração de PDF no servidor — para layouts mais elaborados (logotipo, cabeçalho fixo
   em todas as páginas), uma biblioteca como `dompdf` seria o próximo passo.
3. **Cron real depende do usuário configurá-lo no SO** (`cron/gerar_notificacoes.php`
   está pronto e testado via CLI, mas o agendamento em si — crontab ou Agendador de
   Tarefas do Windows — é uma configuração externa ao código, documentada no próprio
   arquivo do script).
4. **Recuperação de senha não envia e-mail de verdade.** Sem SMTP configurado (comum em
   XAMPP local), o link de redefinição é exibido na própria tela em modo
   desenvolvimento. Para produção, seria necessário integrar um envio real (PHPMailer +
   SMTP, ou uma API como SendGrid/Mailgun) — o ponto de extensão já está isolado em
   `modules/auth/esqueci_senha.php` (variável `$linkDev`).
5. **Rate limiting é por conta, não por IP.** Um atacante pode tentar senhas em contas
   diferentes sem ser bloqueado (só é bloqueado ao errar 5x a mesma conta). Adicionar
   limitação por IP seria um reforço complementar.
6. **Mover credenciais para variáveis de ambiente**, HTTPS obrigatório e
   `APP_ENV = 'production'` continuam recomendados antes de qualquer uso real (ver
   itens originais abaixo).
7. **HTTPS obrigatório** + `session.cookie_secure = 1` (já preparado em `config.php`, só
   descomentar).
8. **Paginação ainda não cobre todas as listagens** (ex.: folha de pagamento, candidatos
   por vaga) — hoje aceitável pelo volume esperado por mês/vaga, mas listado aqui por
   honestidade.

---

## 7. Segurança implementada (resumo)

- SQL Injection: 100% prepared statements (PDO), sem concatenação de entrada do usuário.
- XSS: toda saída dinâmica passa por `e()` (htmlspecialchars).
- CSRF: token de sessão validado em todo POST (`csrfToken()` / `validarCsrf()`).
- Sessão: `httponly`, `use_strict_mode`, regeneração de ID após login (previne session fixation).
- Senhas: `password_hash` (bcrypt), nunca texto plano.
- Upload: extensão validada por whitelist, tamanho limitado, nome de arquivo randomizado
  (evita sobrescrita/colisão e execução de scripts disfarçados de imagem).
- IDOR: checagem de propriedade do registro (não só do perfil) em páginas sensíveis.
- Arquivos sensíveis (`uploads/documentos`, `uploads/fotos`): bloqueados por `.htaccess`,
  só acessíveis via script controlado (`download.php`) com RBAC.
