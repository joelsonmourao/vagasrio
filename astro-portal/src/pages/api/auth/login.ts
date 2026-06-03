import type { APIRoute } from 'astro';
import { getAdminAuthRuntime, isAdminAuthConfigured, setAdminSession, verifyAdminLogin } from '../../../lib/auth';

export const POST: APIRoute = async ({ request, cookies, redirect }) => {
  const adminUsername = process.env['ADMIN_USERNAME'];
  const adminPasswordHash = process.env['ADMIN_PASSWORD_HASH'];

  if (!isAdminAuthConfigured()) {
    console.error(
      '[auth/login] Admin nao configurado:',
      !adminUsername?.trim() ? 'ADMIN_USERNAME ausente' : null,
      !adminPasswordHash?.trim() ? 'ADMIN_PASSWORD_HASH ausente' : null,
    );
    return redirect('/admin/login?error=1');
  }

  const form = await request.formData();
  const username = String(form.get('username') ?? '').trim();
  const password = String(form.get('password') ?? '');

  if (!username || !password) {
    return redirect('/admin/login?error=1');
  }

  const runtime = getAdminAuthRuntime();
  if (!runtime) {
    return redirect('/admin/login?error=1');
  }

  if (await verifyAdminLogin(username, password)) {
    setAdminSession(cookies);
    return redirect('/admin');
  }

  return redirect('/admin/login?error=1');
};
