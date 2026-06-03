/**
 * Desenvolvimento com PostgreSQL (requer DATABASE_URL valida).
 */
import { spawn, spawnSync } from 'node:child_process';
import { copyFileSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const prismaDir = join(root, 'prisma');

if (!process.env.DATABASE_URL?.startsWith('postgresql')) {
  console.error('Defina DATABASE_URL com URL PostgreSQL no .env');
  process.exit(1);
}

copyFileSync(join(prismaDir, 'schema.postgres.prisma'), join(prismaDir, 'schema.prisma'));

const env = { ...process.env, PRISMA_PROVIDER: 'postgres' };

spawnSync('npx', ['prisma', 'generate'], { cwd: root, env, stdio: 'inherit', shell: true });

const child = spawn('npx', ['astro', 'dev'], { cwd: root, env, stdio: 'inherit', shell: true });
child.on('exit', (code) => process.exit(code ?? 0));
