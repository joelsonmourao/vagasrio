import bcrypt from 'bcryptjs';
import { createHmac, timingSafeEqual } from 'node:crypto';
import type { AstroCookies } from 'astro';

const COOKIE = 'vagas_admin_session';
const MAX_AGE = 60 * 60 * 12;

const ENV_ADMIN_USERNAME = 'ADMIN_USERNAME';
const ENV_ADMIN_PASSWORD_HASH = 'ADMIN_PASSWORD_HASH';
const ENV_ADMIN_PASSWORD = 'ADMIN_PASSWORD';
const ENV_SESSION_SECRET = 'SESSION_SECRET';

/** Lê process.env em runtime (acesso dinâmico — não fixar no build). */
function runtimeEnv(name: string): string | undefined {
  const raw = process.env[name];
  if (typeof raw !== 'string') return undefined;
  const trimmed = raw.trim();
  return trimmed.length > 0 ? trimmed : undefined;
}

function isProduction(): boolean {
  return runtimeEnv('NODE_ENV') === 'production';
}

/** Converte hash PHP ($2y$) para formato aceito pelo bcryptjs ($2b$). */
function normalizePasswordHash(hash: string): string {
  const h = hash.trim();
  if (h.startsWith('$2y$')) return `$2b$${h.slice(4)}`;
  return h;
}

export type AdminAuthRuntime = {
  username: string;
};

/** Usuário admin esperado (runtime). */
export function getAdminAuthRuntime(): AdminAuthRuntime | null {
  const adminUsername = runtimeEnv(ENV_ADMIN_USERNAME);

  if (isProduction()) {
    const adminPasswordHash = runtimeEnv(ENV_ADMIN_PASSWORD_HASH);
    if (!adminUsername || !adminPasswordHash) return null;
    return { username: adminUsername };
  }

  if (adminUsername) return { username: adminUsername };

  const hasHash = Boolean(runtimeEnv(ENV_ADMIN_PASSWORD_HASH));
  const hasPlain = Boolean(runtimeEnv(ENV_ADMIN_PASSWORD));
  if (hasHash || hasPlain) return { username: 'admin' };

  return { username: 'admin' };
}

export function isAdminAuthConfigured(): boolean {
  if (isProduction()) {
    return Boolean(runtimeEnv(ENV_ADMIN_USERNAME) && runtimeEnv(ENV_ADMIN_PASSWORD_HASH));
  }
  return Boolean(
    runtimeEnv(ENV_ADMIN_PASSWORD_HASH) ||
      runtimeEnv(ENV_ADMIN_PASSWORD) ||
      !isProduction(),
  );
}

function secret(): string {
  const sessionSecret = runtimeEnv(ENV_SESSION_SECRET);
  const passwordHash = runtimeEnv(ENV_ADMIN_PASSWORD_HASH);
  const s = sessionSecret || passwordHash || 'dev-change-me';
  if (isProduction() && s === 'dev-change-me') {
    throw new Error('SESSION_SECRET obrigatorio em producao');
  }
  return s;
}

function sign(payload: string): string {
  return createHmac('sha256', secret()).update(payload).digest('hex');
}

export async function verifyAdminLogin(username: string, password: string): Promise<boolean> {
  const config = getAdminAuthRuntime();
  if (!config) return false;

  const submittedUser = username.trim();
  const submittedPassword = password;
  if (!submittedUser || !submittedPassword) return false;
  if (submittedUser !== config.username) return false;

  const adminPasswordHash = runtimeEnv(ENV_ADMIN_PASSWORD_HASH);

  if (adminPasswordHash) {
    return bcrypt.compare(submittedPassword, normalizePasswordHash(adminPasswordHash));
  }

  if (isProduction()) return false;

  const plain = runtimeEnv(ENV_ADMIN_PASSWORD) || 'admin123';
  return submittedPassword === plain;
}

export function setAdminSession(cookies: AstroCookies): void {
  const config = getAdminAuthRuntime();
  if (!config) {
    throw new Error('Admin auth nao configurado');
  }
  const exp = Date.now() + MAX_AGE * 1000;
  const payload = `${config.username}:${exp}`;
  const token = `${payload}.${sign(payload)}`;
  cookies.set(COOKIE, token, {
    httpOnly: true,
    secure: isProduction(),
    sameSite: 'lax',
    path: '/',
    maxAge: MAX_AGE,
  });
}

/** Remove o cookie de sessão admin (HttpOnly exige resposta do servidor). */
export function clearAdminSession(cookies: AstroCookies): void {
  cookies.set(COOKIE, '', {
    path: '/',
    maxAge: 0,
    expires: new Date(0),
    httpOnly: true,
    secure: isProduction(),
    sameSite: 'lax',
  });
}

export function isAdminLoggedIn(cookies: AstroCookies): boolean {
  const config = getAdminAuthRuntime();
  if (!config) return false;

  const raw = cookies.get(COOKIE)?.value;
  if (!raw) return false;
  const [payload, sig] = raw.split('.');
  if (!payload || !sig) return false;
  const expected = sign(payload);
  try {
    if (!timingSafeEqual(Buffer.from(sig), Buffer.from(expected))) return false;
  } catch {
    return false;
  }
  const [user, expStr] = payload.split(':');
  if (user !== config.username) return false;
  const exp = Number(expStr);
  return Number.isFinite(exp) && exp > Date.now();
}
