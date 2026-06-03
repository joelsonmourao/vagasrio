# Vagas RJ — Portal Astro

Versão Node.js + Astro. **Desenvolvimento local:** SQLite em `prisma/dev.sqlite` (sem Docker/PostgreSQL). **Produção (Coolify):** PostgreSQL.

O projeto PHP legado permanece em `../` (intacto).

## Requisitos locais

- Node.js 22+
- Python 3 (opcional, só para reexportar `seed-data.json` do SQLite antigo)

**Não precisa:** Docker, PostgreSQL, `psql`.

## Primeira vez no Windows (PowerShell)

```powershell
cd C:\Users\Joelson\Documents\Portal_Vagas_UF\astro-portal

# .env ja deve usar: DATABASE_URL=file:./dev.sqlite
Copy-Item .env.example .env -ErrorAction SilentlyContinue

# Exportar dados do portal PHP (opcional se seed-data.json ja existir)
cd C:\Users\Joelson\Documents\Portal_Vagas_UF
python scripts\export-sqlite-for-astro.py

cd C:\Users\Joelson\Documents\Portal_Vagas_UF\astro-portal
npm install
npm run db:migrate:sqlite
npm run db:seed:sqlite
npm run dev:sqlite
```

Acesse:

- http://localhost:4321
- http://localhost:4321/vagas
- http://localhost:4321/blog
- http://localhost:4321/sitemap.xml
- http://localhost:4321/admin/login (admin / admin123)

## Scripts npm

| Script | Uso |
|--------|-----|
| `npm run dev:sqlite` | Astro dev + SQLite |
| `npm run db:migrate:sqlite` | Cria/atualiza `prisma/dev.sqlite` |
| `npm run db:seed:sqlite` | Importa `prisma/seed-data.json` |
| `npm run dev:postgres` | Dev com PostgreSQL (precisa `DATABASE_URL`) |
| `npm run db:migrate:postgres` | Migrations PostgreSQL |
| `npm run db:seed:postgres` | Seed no PostgreSQL |
| `npm run build:postgres` | Build para deploy Coolify |

## Schemas Prisma

- `prisma/schema.sqlite.prisma` — local
- `prisma/schema.postgres.prisma` — producao
- `prisma/schema.prisma` — copia ativa (gerada pelos scripts; nao editar manualmente)

## Producao (Coolify)

1. Servico PostgreSQL + `DATABASE_URL=postgresql://...`
2. `PRISMA_PROVIDER=postgres`
3. Build: `npm run build:postgres`
4. Start: `node ./dist/server/entry.mjs`
5. Antes do primeiro deploy: `npm run db:migrate:postgres` (ou `db:deploy:postgres` no CI)

## Banco PHP original

Nao e alterado: `../database/portal.sqlite`

Documentacao da migracao: `../MIGRACAO_ASTRO_DIAGNOSTICO.md`
