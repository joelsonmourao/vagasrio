/**
 * Preview com SQLite (mesmo DATABASE_URL do dev:sqlite).
 */
import { spawn } from 'node:child_process';
import { copyFileSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const prismaDir = join(root, 'prisma');

copyFileSync(join(prismaDir, 'schema.sqlite.prisma'), join(prismaDir, 'schema.prisma'));

const env = {
  ...process.env,
  PRISMA_PROVIDER: 'sqlite',
  DATABASE_URL: 'file:./dev.sqlite',
  SITE_BASE_URL: process.env.SITE_BASE_URL || 'http://localhost:4321',
};

console.log('[preview:sqlite] http://localhost:4321');
const child = spawn('npx', ['astro', 'preview', '--host', '127.0.0.1', '--port', '4321'], {
  cwd: root,
  env,
  stdio: 'inherit',
  shell: true,
});
child.on('exit', (code) => process.exit(code ?? 0));
