import bcrypt from 'bcryptjs';
import { createHmac, timingSafeEqual } from 'node:crypto';
import type { AstroCookies } from 'astro';

export const ADMIN_SESSION_COOKIE = 'vagas_admin_session';
const COOKIE = ADMIN_SESSION_COOKIE;
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

function parseSessionToken(raw: string): { user: string; exp: number } | null {
  const [payload, sig] = raw.split('.');
  if (!payload || !sig) return null;
  const expected = sign(payload);
  try {
    if (!timingSafeEqual(Buffer.from(sig), Buffer.from(expected))) return null;
  } catch {
    return null;
  }
  const colon = payload.indexOf(':');
  if (colon < 1) return null;
  const user = payload.slice(0, colon);
  const exp = Number(payload.slice(colon + 1));
  if (!user || !Number.isFinite(exp) || exp <= Date.now()) return null;
  return { user, exp };
}

export function getAdminAuthRuntime(): { username: string } | null {
  const adminUsername = runtimeEnv(ENV_ADMIN_USERNAME);
  if (isProduction()) {
    if (!adminUsername || !runtimeEnv(ENV_ADMIN_PASSWORD_HASH)) return null;
    return { username: adminUsername };
  }
  if (adminUsername) return { username: adminUsername };
  if (runtimeEnv(ENV_ADMIN_PASSWORD_HASH) || runtimeEnv(ENV_ADMIN_PASSWORD)) {
    return { username: 'admin' };
  }
  return { username: 'admin' };
}

export async function verifyAdminLogin(username: string, password: string): Promise<boolean> {
  const expected = runtimeEnv(ENV_ADMIN_USERNAME);
  const hash = runtimeEnv(ENV_ADMIN_PASSWORD_HASH);
  if (!expected || !hash) return false;
  if (username.trim() !== expected) return false;
  if (!password) return false;
  return bcrypt.compare(password, normalizePasswordHash(hash));
}

export function setAdminSession(cookies: AstroCookies, username?: string): void {
  const user = username?.trim() || getAdminAuthRuntime()?.username;
  if (!user) {
    throw new Error('Admin auth nao configurado');
  }
  const exp = Date.now() + MAX_AGE * 1000;
  const payload = `${user}:${exp}`;
  const token = `${payload}.${sign(payload)}`;
  cookies.set(COOKIE, token, {
    httpOnly: true,
    secure: isProduction(),
    sameSite: 'lax',
    path: '/',
    maxAge: MAX_AGE,
  });
}

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

export function getAdminSessionUser(cookies: AstroCookies): string | null {
  const raw = cookies.get(COOKIE)?.value;
  if (!raw) return null;
  const parsed = parseSessionToken(raw);
  return parsed?.user ?? null;
}

/** Valida cookie vagas_admin_session (assinatura, expiração e usuário esperado). */
export function isAdminLoggedIn(cookies: AstroCookies): boolean {
  const raw = cookies.get(COOKIE)?.value;
  if (!raw) return false;

  const parsed = parseSessionToken(raw);
  if (!parsed) return false;

  const expected = runtimeEnv(ENV_ADMIN_USERNAME);
  if (expected && parsed.user !== expected) return false;

  return true;
}
