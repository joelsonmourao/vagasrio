# Diagnóstico — Vagas RJ (migração para Astro)

**Data:** 2026-06-03  
**Pasta analisada:** `C:\Users\Joelson\Documents\Portal_Vagas_UF`  
**Stack atual identificada:** PHP 8 + SQLite + templates PHP (não é Next.js, não é React)

---

## 1. Diagnóstico da pasta atual

| Aspecto | Situação |
|--------|----------|
| Nome do produto | **Vagas RJ** (README e `config/app.php`) |
| Entry point público | `public/index.php` (~890 linhas de roteamento) |
| Bootstrap | `app/bootstrap.php` |
| Lógica de negócio | `app/services/PortalService.php` |
| Banco local | `database/portal.sqlite` (~1,1 MB) |
| Templates | `templates/pages/*`, `templates/admin/*`, `templates/partials/*` |
| Assets estáticos | `public/assets/css`, `public/assets/js`, `public/assets/img` |
| Deploy atual | Dockerfile PHP 8.2 + Apache; Coolify com volumes em `database/` e `storage/` |
| Conteúdo “vivo” | **SQLite** (vagas, empresas, cidades, categorias, blog) + **PHP** (páginas institucionais embutidas) + **seed PHP** (121 artigos de blog gerados a partir de `app/content/blog/topics.php`) |

**Contagem atual no SQLite (local):**

| Tabela | Registros |
|--------|-----------|
| jobs | 5 |
| blog_posts | 121 |
| blog_categories | 11 |
| companies | 6 |
| cities | 12 |
| categories | 11 |
| articles | 3 (legado; blog usa `blog_posts`) |
| imports | 0 |

---

## 2. Onde estão os conteúdos atuais

### 2.1 Vagas de emprego

- **Principal:** tabela `jobs` em `database/portal.sqlite`
- **Relacionadas:** `companies`, `cities`, `categories`
- **Campos importantes:** `title`, `slug`, `description`, `apply_url`, `salary`, `employment_type`, `published_at`, `valid_through`, `is_active`
- **Modelos de importação:** `storage/imports/exemplo-importacao.csv`, `modelo-importacao-vagas.csv`, `modelo-vagas-obrigatorias.csv`
- **Importação no admin:** `.csv` e `.xlsx` (gerados também em `/admin/import/template.*`)

### 2.2 Posts do blog

- **Principal:** tabela `blog_posts` + `blog_categories` no SQLite
- **Origem editorial (seed):** `app/content/blog/topics.php` (11 categorias × 11 títulos = 121 artigos)
- **Geração de HTML:** `app/services/BlogArticleBuilder.php` + `BlogContentSeed.php`
- **Não há** arquivos `.md` por post; conteúdo está em HTML no banco

### 2.3 Imagens

| Arquivo | Caminho |
|---------|---------|
| Logo principal | `public/assets/img/logo-vagas-rj.svg` |
| Favicon SVG | `public/favicon.svg` |
| Favicon ICO | `public/favicon.ico` |
| Apple touch | `public/apple-touch-icon.png` |
| Logos de empresa | coluna `companies.logo` (URL ou caminho; pode estar vazio) |

**Não existe** pasta dedicada de uploads de blog ou galeria de mídia no projeto atual.

### 2.4 Logos

- Marca do site: `public/assets/img/logo-vagas-rj.svg`
- Empresas: campo `logo` em `companies` (SQLite)
- Script gerador: `scripts/generate-brand-assets.py`

### 2.5 Arquivos JSON

- **Nenhum arquivo JSON de conteúdo** na árvore do projeto
- JSON apenas **interno** no banco (`imports.summary_json`)

### 2.6 Arquivos Markdown

- `README.md` (documentação do projeto PHP)
- Este relatório (`MIGRACAO_ASTRO_DIAGNOSTICO.md`)

### 2.7 Arquivos HTML

| Arquivo | Uso |
|---------|-----|
| `public/google561190fa7bebb1e0.html` | Verificação Google Search Console |
| Conteúdo institucional | Embutido em `templates/pages/institutional.php` (HTML em PHP, não arquivos `.html` separados) |

### 2.8 Planilhas Excel

- **Nenhum `.xlsx` ou `.xls` versionado** no repositório
- Suporte a **importação XLSX** no admin PHP (requer extensão `zip`)
- Modelos disponíveis como **CSV** em `storage/imports/`

### 2.9 Rotas/páginas atuais (públicas)

| Rota atual | Template | Observação |
|------------|----------|------------|
| `/` | `pages/home.php` | Home |
| `/vagas` | `pages/jobs.php` | Listagem + filtros |
| `/vagas/pagina/{n}` | idem | Paginação |
| `/vagas/{slug}` | `pages/job_detail.php` | JobPosting JSON-LD |
| `/vaga/{slug}` | redirect → `/vagas/{slug}` | Legado |
| `/cidades` | `pages/cities.php` | Lista cidades |
| `/cidades/{slug}` | `pages/city.php` | Vagas por cidade |
| `/cidade/{slug}` | redirect → `/cidades/{slug}` | Legado |
| `/empresas` | `pages/companies.php` | |
| `/empresas/{slug}` | `pages/company_detail.php` | |
| `/empresa/{slug}` | redirect → `/empresas/{slug}` | Legado |
| `/categorias` | `pages/categories.php` | Extra (não na sua lista obrigatória) |
| `/categorias/{slug}` | `pages/category_detail.php` | Extra |
| `/blog` | `pages/blog.php` | |
| `/blog/{slug}` | `pages/article_detail.php` | |
| `/blog/categoria/{slug}` | `pages/blog_category.php` | Extra |
| `/sobre`, `/contato`, políticas, termos | `pages/institutional.php` | HTML embutido |
| `/aviso-legal`, `/seguranca-para-candidatos`, `/mapa-do-site` | institucional | Extras |
| `/sitemap.xml`, `/sitemap-*.xml` | dinâmico | `SitemapService.php` |
| `/robots.txt` | dinâmico | |
| `/ads.txt` | `public/ads.txt` | AdSense |

**Rotas admin atuais (PHP):**

- `/admin/login`, `/admin`, `/admin/jobs`, `/admin/companies`, `/admin/categories`
- `/admin/import`, `/admin/blog/posts`, `/admin/blog/categories`

### 2.10 Arquivos reaproveitáveis

| Item | Reaproveitamento |
|------|------------------|
| `database/portal.sqlite` | Exportar → PostgreSQL (script de migração) |
| `app/content/blog/topics.php` | Referência editorial; posts já estão no SQLite |
| `storage/imports/*.csv` | Modelos de importação Excel/CSV |
| `public/assets/css/app.css` | Base visual (adaptar para Astro) |
| `public/assets/img/logo-vagas-rj.svg` | Copiar para `public/` do Astro |
| Favicons / `site.webmanifest` / `ads.txt` | Copiar direto |
| `config/app.php` | Cidades permitidas, CEPs JobPosting, contato, AdSense |
| `app/helpers/functions.php` | Lógica JobPosting, slugs, paginação — portar para TS |
| `templates/pages/institutional.php` | Extrair HTML para páginas Astro |
| `Dockerfile` + docs Coolify | Referência; novo deploy será Node |

---

## 3. O que pode ser reaproveitado

1. **Todo o SQLite** como fonte única de verdade para migração inicial.
2. **Slugs existentes** de vagas, posts, cidades, empresas, categorias.
3. **SEO:** títulos/descriptions/canonicals dos templates PHP.
4. **Schema JobPosting** — função `build_job_posting_schema()` em `functions.php`.
5. **Sitemap/robots** — regras em `SitemapService.php` e `index.php`.
6. **Assets de marca** em `public/`.
7. **Textos institucionais** em `institutional.php`.
8. **Formato de importação** (colunas CSV/XLSX documentadas no README).

---

## 4. O que precisa ser migrado

| De | Para (Astro) |
|----|----------------|
| Tabelas SQLite | PostgreSQL via Prisma |
| Roteamento PHP | Rotas Astro + redirects 301 |
| Templates PHP | `.astro` + layouts |
| Sessão admin PHP | Auth com cookie seguro (bcrypt) |
| Upload implícito (logo empresa) | `public/uploads/` + API admin |
| `/cidades/{slug}` | `/vagas/cidade/{slug}` (+ redirect antigo) |
| — | Nova rota `/vagas/estado/rj` (agregação por UF) |
| `/admin/jobs` | `/admin/vagas` (aliases/redirects) |
| Sitemap dinâmico PHP | Endpoint Astro ou build + regen no admin |
| Variáveis `.env` | `DATABASE_URL`, `SESSION_SECRET`, etc. |

**Não migrar como código:** `app/controllers` (pasta vazia/inexistente no glob), lógica Apache `.htaccess` (substituída por Node ou proxy Coolify).

---

## 5. Banco recomendado

| Ambiente | Recomendação |
|----------|----------------|
| **Produção (Coolify/VPS)** | **PostgreSQL 15+** — concorrência, backups, volume persistente |
| **Desenvolvimento local** | PostgreSQL via Docker **ou** SQLite via Prisma (opcional) |
| **ORM** | **Prisma 5** — schema claro, migrations, seed, bom com Astro SSR |

**Motivo:** o projeto já evoluiu de arquivo único (`portal.sqlite`) para portal com admin, importação e múltiplos editores; PostgreSQL alinha com Coolify (serviço DB separado) e Hostinger VPS.

**Migração:** script `scripts/export-sqlite-for-astro.py` → JSON → `prisma/seed.ts`.

---

## 6. Estrutura final do projeto Astro

```
Portal_Vagas_UF/
├── [legado PHP intacto]
├── MIGRACAO_ASTRO_DIAGNOSTICO.md
├── scripts/
│   └── export-sqlite-for-astro.py
└── astro-portal/
    ├── astro.config.mjs
    ├── package.json
    ├── prisma/
    │   ├── schema.prisma
    │   └── seed.ts
    ├── public/
    │   ├── assets/          # CSS, img copiados
    │   ├── uploads/         # logos/imagens enviadas
    │   ├── ads.txt
    │   └── ...
    └── src/
        ├── env.d.ts
        ├── middleware.ts      # auth /admin
        ├── lib/
        │   ├── db.ts
        │   ├── auth.ts
        │   ├── slug.ts
        │   ├── job-posting.ts
        │   ├── sitemap.ts
        │   └── import-jobs.ts
        ├── layouts/
        │   ├── BaseLayout.astro
        │   └── AdminLayout.astro
        ├── components/
        ├── pages/
        │   ├── index.astro
        │   ├── vagas/
        │   ├── empresas/
        │   ├── blog/
        │   ├── sobre.astro, contato.astro, ...
        │   ├── 404.astro
        │   ├── sitemap.xml.ts
        │   ├── robots.txt.ts
        │   └── admin/
        └── pages/api/         # CRUD, login, import, upload
```

---

## 7. Plano de migração em etapas

### Etapa 0 — Backup (obrigatório)

```powershell
$src = "C:\Users\Joelson\Documents\Portal_Vagas_UF"
$dst = "C:\Users\Joelson\Documents\Portal_Vagas_UF_backup_2026-06-03"
Copy-Item -Path $src -Destination $dst -Recurse -Force
```

Copie **a pasta inteira** `Portal_Vagas_UF`, especialmente `database/portal.sqlite` e `storage/`.

### Etapa 1 — Diagnóstico e export

- Rodar `python scripts/export-sqlite-for-astro.py`
- Revisar `astro-portal/prisma/seed-data.json`

### Etapa 2 — Scaffold Astro + Prisma

- `astro-portal/` com adapter Node, PostgreSQL, painel admin

### Etapa 3 — Importar dados

- `npx prisma migrate dev` + `npx prisma db seed`

### Etapa 4 — Paridade de URLs

- Redirects 301: `/cidades/x` → `/vagas/cidade/x`, `/vaga/x` → `/vagas/x`, etc.

### Etapa 5 — Validação SEO

- JobPosting, sitemap, robots, Rich Results Test

### Etapa 6 — Deploy paralelo

- Coolify: app Node na porta 4321 (ou 3000) + serviço PostgreSQL + volume `public/uploads`

### Etapa 7 — Cutover DNS

- Apontar domínio para Astro; manter PHP offline apenas após validação

---

## 8. Lista de arquivos que serão criados (Astro)

Ver pasta `astro-portal/` após scaffold. Principais grupos:

- Config: `package.json`, `astro.config.mjs`, `tsconfig.json`, `.env.example`
- Prisma: `schema.prisma`, migrations, `seed.ts`
- Lib: auth, db, slug, job-posting, sitemap, import-xlsx
- Páginas públicas: 15+ rotas `.astro`
- Admin: login, dashboard, vagas, posts, importar, configurações
- API: `api/auth/*`, `api/admin/jobs/*`, `api/admin/posts/*`, `api/admin/import`, `api/admin/upload`
- Scripts: export SQLite, seed

**Nenhum arquivo do legado PHP será apagado.**

---

## 9. Importação do conteúdo local

1. **Automática (recomendada):**  
   `python scripts/export-sqlite-for-astro.py` lê `database/portal.sqlite` e gera `astro-portal/prisma/seed-data.json`.  
   Depois: `cd astro-portal && npx prisma db seed`.

2. **Planilhas novas:**  
   Admin `/admin/importar-vagas` aceita `.csv`/`.xlsx` com colunas:  
   `title`, `company`, `city`, `state`, `description`, `applyUrl` (+ opcionais).

3. **Institucional:**  
   Conteúdo copiado para páginas `.astro` estáticas (mesmos slugs).

4. **Imagens:**  
   Copiar `public/assets/img/*` e favicons; uploads futuros em `public/uploads/`.

---

## 10. Deploy no Coolify / Hostinger VPS

### Coolify (recomendado para Astro)

1. **PostgreSQL** — serviço gerenciado no Coolify; anotar `DATABASE_URL`.
2. **Application** — build Dockerfile em `astro-portal/Dockerfile` ou Nixpacks Node.
3. **Porta:** 4321 (Astro Node standalone).
4. **Variáveis:**
   - `DATABASE_URL`
   - `SESSION_SECRET` (32+ bytes aleatórios)
   - `SITE_BASE_URL=https://vagasrj.rio.br`
   - `ADMIN_USERNAME` / `ADMIN_PASSWORD_HASH`
5. **Volume persistente:** `/app/public/uploads` (logos/imagens).
6. **Proxy:** HTTPS no Coolify; manter `ads.txt` e verificação Google em `public/`.

### Hostinger VPS (alternativa)

- Node 20+ + PM2 ou Docker Compose (app + postgres).
- Nginx reverse proxy → container Astro.
- Backup diário do volume PostgreSQL.

### Coexistência com PHP atual

- Subdomínio `beta.vagasrj.rio.br` → Astro  
- Produção PHP até validação → troca DNS

---

## Diferenças de URL (preservação SEO)

| URL desejada (nova) | URL atual PHP | Ação |
|---------------------|---------------|------|
| `/vagas/cidade/{slug}` | `/cidades/{slug}` | Página nova + **301** do antigo |
| `/vagas/estado/rj` | — | Nova listagem por UF |
| `/empresas/{slug}` | `/empresas/{slug}` | Igual |
| `/vagas/{slug}` | `/vagas/{slug}` | Igual |
| `/admin/vagas` | `/admin/jobs` | Nova rota admin |

---

*Relatório gerado antes de qualquer alteração no código legado. Projeto Astro em `astro-portal/`.*
