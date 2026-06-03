import { createHmac, timingSafeEqual } from 'node:crypto';
import type { AstroCookies } from 'astro';

export const ADMIN_SESSION_COOKIE = 'vagas_admin_session';
const MAX_AGE_SEC = 60 * 60 * 12;

const ENV_ADMIN_USERNAME = 'ADMIN_USERNAME';
const ENV_ADMIN_PASSWORD_HASH = 'ADMIN_PASSWORD_HASH';
const ENV_SESSION_SECRET = 'SESSION_SECRET';

export type SessionVerifyReason =
  | 'missing_cookie'
  | 'missing_session_secret'
  | 'invalid_format'
  | 'invalid_expires'
  | 'expired'
  | 'invalid_signature'
  | 'wrong_user'
  | 'ok';

export type SessionVerifyResult = {
  valid: boolean;
  user: string | null;
  reason: SessionVerifyReason;
};

function runtimeEnv(name: string): string | undefined {
  const raw = process.env[name];
  if (typeof raw !== 'string') return undefined;
  const trimmed = raw.trim();
  return trimmed.length > 0 ? trimmed : undefined;
}

function isProduction(): boolean {
  return runtimeEnv('NODE_ENV') === 'production';
}

function sessionSecret(): string | null {
  const secret = runtimeEnv(ENV_SESSION_SECRET);
  if (secret) return secret;
  if (isProduction()) return null;
  return 'dev-change-me';
}

function decodeCookieValue(raw: string): string {
  try {
    return decodeURIComponent(raw);
  } catch {
    return raw;
  }
}

/** HMAC SHA256 de `${username}:${expires}` com SESSION_SECRET. */
export function signAdminSession(username: string, expires: number): string | null {
  const secret = sessionSecret();
  if (!secret) return null;
  return createHmac('sha256', secret).update(`${username}:${expires}`).digest('hex');
}

/** Formato: username:expires.signature */
export function createAdminSession(username: string): string | null {
  const expires = Date.now() + MAX_AGE_SEC * 1000;
  const signature = signAdminSession(username, expires);
  if (!signature) return null;
  return `${username}:${expires}.${signature}`;
}

export function verifyAdminSession(cookieValue: string | null | undefined): SessionVerifyResult {
  if (!cookieValue?.trim()) {
    return { valid: false, user: null, reason: 'missing_cookie' };
  }

  const secret = sessionSecret();
  if (!secret) {
    return { valid: false, user: null, reason: 'missing_session_secret' };
  }

  const raw = decodeCookieValue(cookieValue.trim());
  const dot = raw.lastIndexOf('.');
  if (dot < 1) {
    return { valid: false, user: null, reason: 'invalid_format' };
  }

  const left = raw.slice(0, dot);
  const signature = raw.slice(dot + 1);
  if (!left || !signature) {
    return { valid: false, user: null, reason: 'invalid_format' };
  }

  const colon = left.lastIndexOf(':');
  if (colon < 1) {
    return { valid: false, user: null, reason: 'invalid_format' };
  }

  const username = left.slice(0, colon);
  const expires = Number(left.slice(colon + 1));

  if (!username) {
    return { valid: false, user: null, reason: 'invalid_format' };
  }
  if (!Number.isFinite(expires)) {
    return { valid: false, user: null, reason: 'invalid_expires' };
  }
  if (expires <= Date.now()) {
    return { valid: false, user: null, reason: 'expired' };
  }

  const expected = createHmac('sha256', secret).update(`${username}:${expires}`).digest('hex');
  try {
    const sigBuf = Buffer.from(signature, 'utf8');
    const expBuf = Buffer.from(expected, 'utf8');
    if (sigBuf.length !== expBuf.length || !timingSafeEqual(sigBuf, expBuf)) {
      return { valid: false, user: null, reason: 'invalid_signature' };
    }
  } catch {
    return { valid: false, user: null, reason: 'invalid_signature' };
  }

  const expectedUser = runtimeEnv(ENV_ADMIN_USERNAME);
  if (expectedUser && username !== expectedUser) {
    return { valid: false, user: null, reason: 'wrong_user' };
  }

  return { valid: true, user: username, reason: 'ok' };
}

export function getAdminCookieValue(cookies: AstroCookies): string | undefined {
  return cookies.get(ADMIN_SESSION_COOKIE)?.value;
}

export function setAdminSession(cookies: AstroCookies, username: string): boolean {
  const token = createAdminSession(username);
  if (!token) return false;
  cookies.set(ADMIN_SESSION_COOKIE, token, {
    path: '/',
    httpOnly: true,
    secure: isProduction(),
    sameSite: 'lax',
    maxAge: MAX_AGE_SEC,
  });
  return true;
}

export function clearAdminSession(cookies: AstroCookies): void {
  cookies.set(ADMIN_SESSION_COOKIE, '', {
    path: '/',
    maxAge: 0,
    expires: new Date(0),
    httpOnly: true,
    secure: isProduction(),
    sameSite: 'lax',
  });
}

export function isAdminLoggedIn(cookies: AstroCookies): boolean {
  const result = verifyAdminSession(getAdminCookieValue(cookies));
  console.info(`[auth/session] reason=${result.reason}`);
  return result.valid;
}
