/**
 * Uso: node scripts/prisma-cli.mjs <sqlite|postgres> <generate|push|migrate|seed> [args...]
 * Copia schema.*.prisma -> schema.prisma e executa o Prisma com DATABASE_URL adequado.
 */
import { copyFileSync, existsSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { spawnSync } from 'node:child_process';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const prismaDir = join(root, 'prisma');

const target = process.argv[2];
const command = process.argv[3];
const extraArgs = process.argv.slice(4);

if (!target || !['sqlite', 'postgres'].includes(target)) {
  console.error('Informe o alvo: sqlite ou postgres');
  process.exit(1);
}

const schemaFile = join(prismaDir, `schema.${target}.prisma`);
const activeSchema = join(prismaDir, 'schema.prisma');

if (!existsSync(schemaFile)) {
  console.error(`Schema nao encontrado: ${schemaFile}`);
  process.exit(1);
}

copyFileSync(schemaFile, activeSchema);
console.log(`[prisma-cli] schema.${target}.prisma -> schema.prisma`);

const sqliteUrl = 'file:./dev.sqlite';
const env = {
  ...process.env,
  PRISMA_PROVIDER: target,
  DATABASE_URL:
    target === 'sqlite'
      ? sqliteUrl
      : process.env.DATABASE_URL || process.env.DATABASE_URL_POSTGRES,
};

if (target === 'postgres' && !env.DATABASE_URL) {
  console.error(
    'DATABASE_URL nao definida. Use URL PostgreSQL no .env ou variavel de ambiente.',
  );
  process.exit(1);
}

let prismaArgs;
switch (command) {
  case 'generate':
    prismaArgs = ['generate'];
    break;
  case 'push':
    prismaArgs = ['db', 'push'];
    break;
  case 'migrate':
    prismaArgs = ['migrate', 'dev', ...extraArgs];
    break;
  case 'deploy':
    prismaArgs = ['migrate', 'deploy'];
    break;
  case 'seed':
    prismaArgs = ['db', 'seed'];
    break;
  default:
    console.error(`Comando desconhecido: ${command}. Use: generate, push, migrate, seed, deploy`);
    process.exit(1);
}

const result = spawnSync('npx', ['prisma', ...prismaArgs], {
  cwd: root,
  env,
  stdio: 'inherit',
  shell: true,
});

process.exit(result.status ?? 1);
