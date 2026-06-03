/**
 * Desenvolvimento local com SQLite (sem Docker/PostgreSQL).
 */
import { spawn } from 'node:child_process';
import { copyFileSync, existsSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { spawnSync } from 'node:child_process';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const prismaDir = join(root, 'prisma');
const dbFile = join(prismaDir, 'dev.sqlite');

copyFileSync(join(prismaDir, 'schema.sqlite.prisma'), join(prismaDir, 'schema.prisma'));

const env = {
  ...process.env,
  PRISMA_PROVIDER: 'sqlite',
  DATABASE_URL: 'file:./dev.sqlite',
};

console.log('[dev:sqlite] Gerando Prisma Client...');
const gen = spawnSync('npx', ['prisma', 'generate'], { cwd: root, env, stdio: 'inherit', shell: true });
if (gen.status !== 0) process.exit(gen.status ?? 1);

if (!existsSync(dbFile)) {
  console.log('[dev:sqlite] Criando banco dev.sqlite (db push)...');
  const push = spawnSync('npx', ['prisma', 'db', 'push'], { cwd: root, env, stdio: 'inherit', shell: true });
  if (push.status !== 0) process.exit(push.status ?? 1);
}

console.log('[dev:sqlite] Iniciando Astro em http://localhost:4321');
const child = spawn('npx', ['astro', 'dev'], { cwd: root, env, stdio: 'inherit', shell: true });
child.on('exit', (code) => process.exit(code ?? 0));
