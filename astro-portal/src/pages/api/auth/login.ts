import type { APIRoute } from 'astro';
import bcrypt from 'bcryptjs';
import { setAdminSession } from '../../../lib/auth';

function readEnv(key: string): string {
  const raw = process.env[key];
  return typeof raw === 'string' ? raw.trim() : '';
}

function normalizeHash(hash: string): string {
  if (hash.startsWith('$2y$')) return `$2b$${hash.slice(4)}`;
  return hash;
}

function hashInicioLog(hash: string): string {
  if (!hash) return '(vazio)';
  return hash.slice(0, 7);
}

export const POST: APIRoute = async ({ request, cookies, redirect }) => {
  const form = await request.formData();
  const usernameRecebido = String(form.get('username') ?? '').trim();
  const password = String(form.get('password') ?? '');

  const usernameEsperado = readEnv('ADMIN_USERNAME');
  const passwordHash = readEnv('ADMIN_PASSWORD_HASH');

  const temPassword = password.length > 0;
  const usernameConfere = Boolean(usernameEsperado) && usernameRecebido === usernameEsperado;

  let senhaConfere = false;
  if (usernameConfere && passwordHash) {
    senhaConfere = await bcrypt.compare(password, normalizeHash(passwordHash));
  }

  const loginOK = usernameConfere && senhaConfere;

  console.info(`[auth/login] usernameRecebido=${usernameRecebido}`);
  console.info(`[auth/login] usernameEsperado=${usernameEsperado}`);
  console.info(`[auth/login] temPassword=${temPassword}`);
  console.info(`[auth/login] usernameConfere=${usernameConfere}`);
  console.info(`[auth/login] senhaConfere=${senhaConfere}`);
  console.info(`[auth/login] hashInicio=${hashInicioLog(passwordHash)}`);
  console.info(`[auth/login] loginOK=${loginOK}`);

  if (!usernameEsperado || !passwordHash) {
    console.error('[auth/login] ADMIN_USERNAME ou ADMIN_PASSWORD_HASH ausente no runtime');
    return redirect('/admin/login?error=1', 303);
  }

  if (loginOK) {
    setAdminSession(cookies, usernameEsperado);
    return redirect('/admin', 303);
  }

  return redirect('/admin/login?error=1', 303);
};
