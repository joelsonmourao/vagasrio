import bcrypt from 'bcryptjs';
import { createHmac, timingSafeEqual } from 'node:crypto';
import type { AstroCookies } from 'astro';

const COOKIE = 'vagas_admin_session';
const MAX_AGE = 60 * 60 * 12;

function secret(): string {
  const s = process.env.SESSION_SECRET || process.env.ADMIN_PASSWORD_HASH || 'dev-change-me';
  if (process.env.NODE_ENV === 'production' && s === 'dev-change-me') {
    throw new Error('SESSION_SECRET obrigatorio em producao');
  }
  return s;
}

function sign(payload: string): string {
  return createHmac('sha256', secret()).update(payload).digest('hex');
}

export function adminCredentials() {
  return {
    username: process.env.ADMIN_USERNAME || 'admin',
    password: process.env.ADMIN_PASSWORD || 'admin123',
    passwordHash: process.env.ADMIN_PASSWORD_HASH || '',
  };
}

export async function verifyAdminLogin(username: string, password: string): Promise<boolean> {
  const creds = adminCredentials();
  if (username !== creds.username) return false;
  if (creds.passwordHash) {
    return bcrypt.compare(password, creds.passwordHash);
  }
  return password === creds.password;
}

export function setAdminSession(cookies: AstroCookies): void {
  const exp = Date.now() + MAX_AGE * 1000;
  const payload = `${credsUsername()}:${exp}`;
  const token = `${payload}.${sign(payload)}`;
  cookies.set(COOKIE, token, {
    httpOnly: true,
    secure: process.env.NODE_ENV === 'production',
    sameSite: 'lax',
    path: '/',
    maxAge: MAX_AGE,
  });
}

function credsUsername(): string {
  return adminCredentials().username;
}

export function clearAdminSession(cookies: AstroCookies): void {
  cookies.delete(COOKIE, { path: '/' });
}

export function isAdminLoggedIn(cookies: AstroCookies): boolean {
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
  if (user !== credsUsername()) return false;
  const exp = Number(expStr);
  return Number.isFinite(exp) && exp > Date.now();
}
