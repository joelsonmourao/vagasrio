# Auditoria pré-produção — Vagas RJ (Astro)

## Pronto ou implementado

| Item | Status |
|------|--------|
| Sitemap dinâmico (`/sitemap.xml`) | OK — vagas, posts, empresas, cidades, páginas institucionais |
| Robots dinâmico (`/robots.txt`) | OK — bloqueia `/admin`, configurável no painel |
| Schema JobPosting (vagas) | OK |
| Schema BlogPosting (blog) | OK |
| Schema WebSite (home) | OK |
| Schema Organization (empresas) | OK |
| Canonical + title + description (páginas públicas principais) | OK |
| Admin com `noindex` | OK (`AdminLayout`) |
| Filtros sem termo "slug" | OK |
| Busca parcial de cidade (acentos/maiúsculas) | OK (`search-normalize.ts`) |
| Painel SEO em `/admin/configuracoes` | OK |
| SEO por vaga/post no admin | OK |
| Importação com resumo e erros por linha | OK |
| Redirects 301 (legado) | OK (`astro.config.mjs`) |
| Dev Toolbar Astro desativada | OK (menu preto flutuante em dev) |
| Mobile: filtros em coluna, sem overflow lateral | OK (CSS v2) |

## Ação obrigatória antes de publicar

1. **Senha admin** — Remover `admin` / `admin123`. Definir `ADMIN_PASSWORD_HASH` (bcrypt) no `.env` de produção.
2. **SESSION_SECRET** — String longa e aleatória no `.env` de produção.
3. **DATABASE_URL** — PostgreSQL em produção (não usar `dev.sqlite`).
4. **SITE_BASE_URL** — URL real (ex.: `https://vagasrj.com.br`) no `.env` e no painel SEO.
5. **Indexação** — Só ativar "Permitir indexação" quando o site estiver validado.
6. **Google** — Preencher Search Console, Analytics e AdSense no painel quando tiver os códigos.
7. **Uploads** — Garantir volume persistente em `public/uploads` no Coolify.
8. **Teste manual** — Login, criar vaga, importar CSV, sitemap, robots, página 404, formulário de contato.

## Verificar manualmente

- Performance mobile (Lighthouse em `/`, `/vagas`, `/vagas/[slug]`).
- Páginas institucionais: política de privacidade, cookies, termos, contato.
- Redirecionamentos antigos do PHP (lista em `astro.config.mjs`).
- AdSense: placeholder no layout até inserir `ca-pub-` no painel.
- Acessibilidade: contraste, foco no menu mobile, labels dos filtros.

## Variáveis `.env` recomendadas

```
SITE_NAME=
SITE_BASE_URL=
DATABASE_URL=
SESSION_SECRET=
ADMIN_USERNAME=
ADMIN_PASSWORD_HASH=
ADSENSE_CLIENT_ID=
```

## Comandos de teste (PowerShell)

```powershell
cd C:\Users\Joelson\Documents\Portal_Vagas_UF\astro-portal
npm run dev:sqlite
```

URLs: `/`, `/vagas`, `/empresas`, `/blog`, `/admin`, `/admin/configuracoes`, `/sitemap.xml`, `/robots.txt`

Build:

```powershell
cd C:\Users\Joelson\Documents\Portal_Vagas_UF\astro-portal
npx astro build
```
