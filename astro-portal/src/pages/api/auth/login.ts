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

export const POST: APIRoute = async ({ request, cookies, redirect }) => {
  const form = await request.formData();
  const usernameRecebido = String(form.get('username') ?? '').trim();
  const password = String(form.get('password') ?? '');

  const usernameEsperado = readEnv('ADMIN_USERNAME');
  const passwordHash = readEnv('ADMIN_PASSWORD_HASH');

  const usernameConfere = Boolean(usernameEsperado) && usernameRecebido === usernameEsperado;
  let senhaConfere = false;
  if (usernameConfere && passwordHash) {
    senhaConfere = await bcrypt.compare(password, normalizeHash(passwordHash));
  }
  const loginOK = usernameConfere && senhaConfere;

  console.info(`[auth/login] usernameConfere=${usernameConfere} senhaConfere=${senhaConfere} loginOK=${loginOK}`);

  if (!usernameEsperado || !passwordHash) {
    return redirect('/admin/login?error=1', 303);
  }

  if (loginOK) {
    const cookieSet = setAdminSession(cookies, usernameEsperado);
    if (!cookieSet) {
      console.error('[auth/login] falha ao criar sessao (SESSION_SECRET ausente?)');
      return redirect('/admin/login?error=1', 303);
    }
    return redirect('/admin', 303);
  }

  return redirect('/admin/login?error=1', 303);
};
