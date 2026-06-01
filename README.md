# Vagas RJ

Portal de vagas leve em PHP + SQLite, focado no Rio de Janeiro (RJ), com SEO forte, JobPosting por vaga, admin funcional e preparacao para Google AdSense sem CLS.

## 1) Caminho do projeto

`C:\Users\Joelson\Documents\Portal_Vagas_UF`

## 2) Requisitos

- PHP 8.1+ (`pdo`, `pdo_sqlite`, `simplexml`)
- Para importacao `.xlsx`: extensao `zip` (`ZipArchive`)
- Apache com `mod_rewrite` (Hostinger) ou Nginx com fallback para `public/index.php`

## Reset de dados demonstrativos (RJ)

Para remover dados antigos de SP e recriar o demo correto do Rio de Janeiro:

```bash
cd C:\Users\Joelson\Documents\Portal_Vagas_UF
php scripts/reset-demo-rj.php
```

O script remove vagas demo antigas, cidades invalidas, empresas legadas de SP e recria cidades, empresas e vagas demo do RJ.

Cidades permitidas estao em `config/app.php` -> `site.allowed_cities`.


```bash
cd C:\Users\Joelson\Documents\Portal_Vagas_UF\public
php -S localhost:8000
```

- Site: `http://localhost:8000`
- Admin: `http://localhost:8000/admin/login`

## 4) Diagnostico rapido local

Rode:

```bash
cd C:\Users\Joelson\Documents\Portal_Vagas_UF
php scripts/diagnostico.php
```

Esse script valida extensoes, permissoes, `.htaccess`, `ads.txt` e alerta sobre credenciais padrao.

## 5) Configuracoes principais

Arquivo: `config/app.php`

- `site.name` (padrao: `Vagas RJ`)
- `site.main_uf` (padrao: `RJ`)
- `site.main_state_name` (padrao: `Rio de Janeiro`)
- `site.base_url`

Admin:

- `admin.username`
- `admin.password` (uso simples)
- `admin.password_hash` (mais seguro, recomendado para producao)

Gerar hash de senha (recomendado):

```bash
php -r "echo password_hash('SUA_SENHA_FORTE', PASSWORD_DEFAULT), PHP_EOL;"
```

Variaveis de ambiente suportadas:

- `APP_ENV` (`production` ou `development`)
- `SITE_BASE_URL` (URL publica, ex: `https://vagasrj.com.br`)
- `SITE_NAME`
- `ADMIN_USERNAME`
- `ADMIN_PASSWORD`
- `ADMIN_PASSWORD_HASH` (recomendado)

## 6) Importacao de planilha (.csv e .xlsx)

No admin, menu `Importacao`.

Aceita:

- `.csv`
- `.xlsx`

Colunas esperadas:

**Obrigatorias:** `title`, `company`, `city`, `state`, `description`, `applyUrl`

**Opcionais:** `category`, `salary`, `employmentType`, `publishedAt`, `validThrough`

Campos legados (`requirements`, `activities`, `benefits`, `bonus`, `additionalInfo`) sao ignorados ou mesclados em `description` se presentes.

Regras:

- Importa apenas vagas da UF configurada
- Linhas de outra UF sao ignoradas com motivo no resumo
- Valida campos obrigatorios
- Normaliza texto e gera slug automaticamente
- Salva historico em `imports` e erros em `import_errors`

Exemplo:

`storage/imports/exemplo-importacao.csv`

## 7) URLs amigaveis

URLs principais:

- `/`
- `/vagas`
- `/vagas/{slug}`
- `/cidade/{slug}`
- `/empresa/{slug}`
- `/categoria/{slug}`
- `/blog`
- `/blog/{slug}`
- `/admin/login`

`.htaccess` pronto em:

- raiz do projeto (fallback seguro para `/public`)
- `public/.htaccess` (front-controller e remove `index.php`)

## 8) Seguranca de exposicao de pastas

Pastas protegidas com `.htaccess`:

- `app/`
- `config/`
- `database/`
- `storage/`
- `templates/`
- `scripts/`

Banco SQLite fica em `database/portal.sqlite` (fora de `public`).

## 9) AdSense sem prejudicar Core Web Vitals

Controle central em `config/app.php`:

- `ads.enabled`
- `ads.adsense_client_id`
- `ads.enabled_pages.*`
- `ads.slots.*`
- `ads.show_placeholders_in_dev`

Implementado:

- Script carregado uma unica vez por pagina quando necessario
- Nenhum anuncio no admin/login/erro
- Blocos com altura reservada (evita CLS)
- Posicoes que nao confundem com CTA de candidatura

## 10) JobPosting

Aparece **somente** na pagina individual da vaga (`/vagas/{slug}`), com dados reais disponiveis.

Validar em:

- [Rich Results Test](https://search.google.com/test/rich-results)

## 11) SEO tecnico

- `sitemap.xml` dinamico
- `robots.txt` dinamico (com `Disallow: /admin`)
- canonical por pagina
- Open Graph e Twitter Card
- titulos e descricoes por contexto
- `noindex` em paginas de erro e resultados filtrados vazios

## 12) Publicacao na Hostinger

1. Envie o projeto para o servidor.
2. Configure o document root para `public/` (obrigatorio).
3. Garanta `mod_rewrite` ativo.
4. Ajuste `config/app.php`:
   - `site.base_url`
   - UF/estado/nome
   - admin
   - AdSense
5. Atualize `public/ads.txt` com publisher real.
6. Permissoes de escrita:
   - `database/`
   - `storage/`

## 13) Onde alterar AdSense e ads.txt

- AdSense config: `config/app.php` (ou env vars `ADSENSE_*`)
- ads.txt: `public/ads.txt`

## 14) Estrutura principal

- `public/index.php` - roteamento
- `app/services/PortalService.php` - consultas e importacao
- `app/services/Database.php` - schema e seed
- `templates/pages/*` - paginas publicas
- `templates/admin/*` - painel
- `public/assets/css/app.css` - layout
- `public/assets/js/app.js` - cookies e inicializacao de anuncios

## 15) Deploy no Coolify (Docker)

O projeto inclui `Dockerfile` com **PHP 8.2 + Apache**, document root em `public/` e suporte a URLs amigaveis via `mod_rewrite` + `.htaccess`.

### Arquivos Docker

- `Dockerfile` — imagem de producao
- `docker/apache/000-default.conf` — Apache apontando para `public/`
- `docker/docker-entrypoint.sh` — permissoes em `database/` e `storage/`
- `.dockerignore` — build mais leve (nao envia SQLite local nem `.env`)

### Configuracao no Coolify

1. Crie um **Application** apontando para este repositorio.
2. Tipo de build: **Dockerfile** (caminho: `Dockerfile` na raiz).
3. **Porta do container:** `80` (Apache escuta na 80).
4. Configure o dominio e HTTPS no Coolify (proxy reverso).

### Volumes persistentes (obrigatorio)

Monte estes caminhos para nao perder dados entre deploys:

| Caminho no container | Conteudo |
|----------------------|----------|
| `/var/www/html/database` | SQLite (`portal.sqlite`) |
| `/var/www/html/storage` | importacoes, logs, uploads temporarios |

No Coolify: **Storages / Persistent Volumes** com destino exatamente nesses paths.

O banco e criado automaticamente em `database/portal.sqlite` na primeira requisicao, se o volume estiver vazio.

### Variaveis de ambiente recomendadas

```env
APP_ENV=production
SITE_BASE_URL=https://seudominio.com.br
SITE_NAME=Vagas RJ

ADMIN_USERNAME=seu_usuario
ADMIN_PASSWORD_HASH=$2y$10$...

ADS_ENABLED=true
ADSENSE_CLIENT_ID=ca-pub-4279201625870524
ADSENSE_SLOT_HOME=...
SHOW_AD_PLACEHOLDERS_IN_DEV=false
```

Consulte `.env.example` para todas as variaveis `ADSENSE_SLOT_*`.

### Checklist pos-deploy

1. Acesse `/` e `/vagas` — URLs amigaveis OK.
2. Acesse `/admin/login` — painel funcional.
3. Teste importacao CSV/XLSX no admin.
4. Confira `https://seudominio.com.br/ads.txt`.
5. Valide JobPosting em `/vagas/{slug}` (Rich Results Test).

### Build local (teste)

```bash
docker build -t vagas-rj .
docker run --rm -p 8080:80 \
  -v vagas-db:/var/www/html/database \
  -v vagas-storage:/var/www/html/storage \
  -e APP_ENV=production \
  -e SITE_BASE_URL=http://localhost:8080 \
  vagas-rj
```

Site: `http://localhost:8080`
