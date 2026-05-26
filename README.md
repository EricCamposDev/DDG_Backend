# DDG Backend — Teste Prático Backend PHP

API RESTful em **PHP 8.3 puro** (sem frameworks) para gerenciamento de cursos, turmas, usuários e matrículas. Banco **SQLite**, ambiente **Docker** e documentação **OpenAPI 3.0 + Swagger UI**.

---

## Como rodar

### Requisitos

- Docker + Docker Compose

### Subindo o ambiente

```bash
docker compose up --build
```

A API ficará disponível em `http://localhost:8080`.

Na primeira inicialização o container:

1. Instala as dependências via Composer
2. Executa as migrations SQL (cria `courses`, `classes`, `users`, `enrollments`)
3. Sobe o servidor embutido do PHP em `0.0.0.0:8080`

O banco SQLite é persistido no volume Docker `sqlite_data` (`/var/www/html/database/database.sqlite`).

### Documentação interativa

- Swagger UI: <http://localhost:8080/docs>
- Spec OpenAPI bruta: <http://localhost:8080/openapi.yaml>

---

## Endpoints

| Método | Rota | Descrição |
|---|---|---|
| `POST` | `/courses` | Cria curso |
| `PUT` | `/courses/{id}` | Atualiza curso |
| `DELETE` | `/courses/{id}` | Exclui curso |
| `GET` | `/courses` | Lista cursos com turmas disponíveis (filtros: `title`, `theme`) |
| `POST` | `/courses/{courseId}/classes` | Cria turma |
| `PUT` | `/courses/{courseId}/classes/{id}` | Atualiza turma |
| `DELETE` | `/courses/{courseId}/classes/{id}` | Exclui turma |
| `POST` | `/users` | Cria usuário |
| `DELETE` | `/users/{id}` | Exclui usuário |
| `POST` | `/enrollments` | Matricula usuário em turma |
| `GET` | `/users/{id}/enrollments` | Lista cursos em que o usuário está matriculado |

### Exemplos

Criar curso:

```bash
curl -X POST http://localhost:8080/courses \
  -H 'Content-Type: application/json' \
  -d '{
    "title": "Curso de PHP",
    "description": "Aprenda PHP do zero",
    "theme": "tecnologia",
    "image_url": "https://example.com/img.png"
  }'
```

Criar turma:

```bash
curl -X POST http://localhost:8080/courses/1/classes \
  -H 'Content-Type: application/json' \
  -d '{
    "title": "Turma Janeiro",
    "description": "Início janeiro",
    "seats": 20,
    "status": "disponivel",
    "start_date": "2026-01-15",
    "end_date": "2026-06-15"
  }'
```

Criar usuário:

```bash
curl -X POST http://localhost:8080/users \
  -H 'Content-Type: application/json' \
  -d '{"name": "Eric Campos", "email": "eric@example.com"}'
```

Matricular usuário em turma:

```bash
curl -X POST http://localhost:8080/enrollments \
  -H 'Content-Type: application/json' \
  -d '{"user_id": 1, "class_id": 1}'
```

Listar cursos com filtro:

```bash
curl 'http://localhost:8080/courses?theme=tecnologia&title=php'
```

---

## Arquitetura

```
src/
├── Bootstrap/       Application + Database (PDO factory)
├── Controllers/     Parsing de request e dispatch para Services
├── Database/        Migrator (executa SQL ao subir o container e nos testes)
├── Enums/           CourseTheme, ClassStatus
├── Exceptions/      Hierarquia AppException (404, 409, 422)
├── Http/            Request, Response (helpers HTTP nativos)
├── Models/          DTOs readonly (CourseModel, ClassModel, ...)
├── Repositories/    Acesso a dados via PDO prepared statements
├── Services/        Regras de negócio e validações
├── Support/         Validator (validação manual sem libs)
└── Router.php       Roteamento por padrão URI + método
database/
├── migrations/      *.sql executadas em ordem
└── init.php         Script de boot (chama o Migrator)
tests/
├── Unit/            Services com SQLite :memory:
└── Integration/     Endpoints contra o Application em :memory:
public/
├── index.php        Ponto de entrada HTTP
└── docs.html        Swagger UI
openapi.yaml         Especificação OpenAPI 3.0
```

### Stack

- **PHP 8.3+** (readonly classes, enums, named arguments, match)
- **SQLite** via **PDO** (apenas prepared statements — nunca concatenação)
- **PSR-4** autoload via Composer (única dep de runtime; PHPUnit é dev)
- **PHPUnit 11** para testes (unit + integration com `:memory:`)
- **Docker** + Compose (servidor embutido `php -S`)
- **OpenAPI 3.0** + **Swagger UI** (servido em `/docs`)

### Padrões aplicados

- Arquitetura em camadas (Controller → Service → Repository → Model)
- Repository Pattern para isolar acesso a dados
- Hierarquia customizada de exceções com mapeamento automático para HTTP status
- Validação manual centralizada em `Support\Validator`
- Enums com `fromInput()` que normaliza entrada do usuário (aceita "disponível" e "disponivel")
- Injeção explícita de dependências no `Application::buildRouter()`

---

## Regras de Negócio

1. Matrícula apenas em turmas com status `disponivel`
2. Matrícula apenas dentro do intervalo de datas (`start_date` ≤ hoje ≤ `end_date`)
3. Um usuário não pode ter mais de uma matrícula por curso (mesmo em turmas diferentes)
4. Respeito ao limite de vagas (`seats`) da turma
5. Listagem de cursos só retorna aqueles com pelo menos uma turma `disponivel`
6. Atualização de turma não permite reduzir `seats` abaixo do número atual de matrículas

---

## Testes

Os testes **não rodam no Docker** (conforme orientação do desafio). Para executá-los localmente, instale o PHP 8.3+ e o Composer:

```bash
composer install
composer test                # toda a suite
composer test-unit           # apenas unitários
composer test-integration    # apenas integração
```

Cobertura:

- **Unit**: `CourseServiceTest`, `ClassServiceTest`, `EnrollmentServiceTest`
- **Integration**: `CourseEndpointTest`, `EnrollmentEndpointTest` (rodam o `Application` real contra SQLite `:memory:`)

Casos críticos cobertos para matrícula:

- Turma encerrada → rejeitar
- Fora da data de vigência → rejeitar
- Mesmo curso duas vezes → rejeitar
- Sem vagas → rejeitar
- Matrícula válida → aprovar
- Listagem de cursos do usuário

---

## Respostas padrão

**Sucesso**

```json
{ "data": { ... } }
```

**Erro**

```json
{ "error": "validation_error", "message": "Campo \"title\" é obrigatório." }
```

| Status | error code | Quando |
|---|---|---|
| 200 / 201 / 204 | — | Sucesso |
| 404 | `not_found` | Recurso não encontrado |
| 409 | `conflict` | Violação de unicidade (ex.: e-mail) |
| 422 | `validation_error` | Dado inválido |
| 422 | `enrollment_error` | Regra de negócio de matrícula violada |
| 500 | `internal_server_error` | Falha não tratada |

---

## Observações técnicas

- **Sem frameworks**: roteamento, request/response e DI são feitos à mão.
- **Sem ORM**: queries PDO diretas, todas com prepared statements.
- **Enums**: `tecnologia`, `marketing`, `inovacao`, `empreendedorismo`, `agro` para tema; `disponivel`, `encerrado` para status. As variantes com acento (`inovação`, `disponível`) são aceitas na entrada e normalizadas.
- **Datas**: formato `YYYY-MM-DD` em todos os campos.
- **Soft constraints no SQLite**: `CHECK` nas colunas de enum e nas datas, FKs com `ON DELETE CASCADE`.
- **Persistência**: volume Docker dedicado para o `.sqlite`.

---

## Entrega

Para gerar o ZIP:

```bash
git archive --format=zip --output=./api.zip HEAD
```

Ou, sem git:

```bash
# Windows PowerShell
Compress-Archive -Path * -DestinationPath api.zip -Force
```
