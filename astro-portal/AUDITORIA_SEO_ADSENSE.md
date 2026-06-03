# Auditoria SEO, AdSense e produção — Vagas RJ (Astro)

Data: 2026-06-03 · Projeto: `astro-portal`

## A. Status geral

| Critério | Status |
|----------|--------|
| Pronto para subir em produção | **Quase** — configurar `.env` de produção antes |
| Pronto para solicitar AdSense | **Sim**, após revisar conteúdo do blog e trocar `ads.txt`/Publisher ID reais |
| Pronto para Search Console | **Sim** (meta tag no painel + arquivo HTML em `/public`) |
| Pronto para Google Jobs | **Sim** (JobPosting com datas ISO BR, `directApply: false` como agregador) |

---

## B. SEO técnico

| Item | Status | Observação |
|------|--------|------------|
| title / meta / canonical | OK | `BaseLayout` + `SeoHead`; canonical absoluto |
| sitemap index | OK | 5 sub-sitemaps; máx. 1000 URLs/arquivo |
| robots.txt | OK | Bloqueia `/admin`, `/api`; aponta para `/sitemap.xml` |
| noindex admin | OK | `AdminLayout` + `/admin/login` |
| JobPosting | OK | Corrigido: `directApply: false`, datas ISO `-03:00` |
| BlogPosting | OK | Datas ISO BR; `image` se houver destaque |
| WebSite + Organization | OK | Home com ambos os schemas |
| Query string no sitemap | OK | Não incluídas |
| lastmod com hora | OK | `datetime-brazil.ts`; páginas fixas usam `SITE_STATIC_LASTMOD` |

---

## C. AdSense

| Item | Status |
|------|--------|
| Páginas institucionais | **Corrigido** — textos expandidos (sobre, contato, privacidade, cookies, termos) |
| Política menciona AdSense/Analytics | OK |
| Banner de cookies | **Corrigido** — HTML no `BaseLayout` |
| ads.txt | **Corrigido** — rota `/ads.txt` + painel |
| Script AdSense | **Corrigido** — só carrega se “Ativar AdSense” no painel |
| Blocos de anúncio no layout | OK — nenhum slot agressivo |
| Risco reprovação por baixo valor | **Médio** — muitos posts no blog (121); revisar qualidade antes de aplicar |

---

## D. Produção (checklist manual)

- [ ] `SITE_BASE_URL=https://seudominio.com.br`
- [ ] `ADMIN_PASSWORD_HASH` (bcrypt) — **não** usar `admin123`
- [ ] `SESSION_SECRET` forte (32+ caracteres)
- [ ] `DATABASE_URL` PostgreSQL no Coolify
- [ ] `ADSENSE_CLIENT_ID` e `ads.txt` com pub real
- [ ] Ativar AdSense no painel só após aprovação
- [ ] Volume `public/uploads` persistente
- [ ] HTTPS no domínio

Build: **OK** (`npx astro build`)

---

## E. Quantidades (banco local `prisma/dev.sqlite`)

| Métrica | Valor |
|---------|-------|
| Sitemaps no índice | **5** |
| Páginas fixas (`sitemap-pages.xml`) | **10** |
| Vagas no sitemap | **5** |
| Posts no sitemap | **121** |
| Empresas no sitemap | **6** |
| Cidades no sitemap | **5** |
| Algum sitemap > 1000 URLs | **Não** |

---

## F. Arquivos alterados nesta rodada

- `src/lib/institutional.ts`
- `src/lib/site-settings.ts`
- `src/lib/job-posting.ts`
- `src/lib/seo.ts`
- `src/components/SeoHead.astro`
- `src/layouts/BaseLayout.astro`
- `src/pages/ads.txt.ts` (novo)
- `src/pages/index.astro`
- `src/pages/admin/configuracoes.astro`
- `src/pages/api/admin/settings.ts`
- `src/pages/api/admin/upload.ts`
- `src/pages/sobre.astro`
- `.env.example`
- `AUDITORIA_SEO_ADSENSE.md` (este arquivo)

---

## G. Comandos de teste

```powershell
cd C:\Users\Joelson\Documents\Portal_Vagas_UF\astro-portal
npm run dev:sqlite
npm run build
$env:DATABASE_URL="file:./dev.sqlite"
node scripts/audit-sitemap-index.mjs
```
