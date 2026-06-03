import type { APIRoute } from 'astro';
import { setAdminSession, verifyAdminLogin } from '../../../lib/auth';

export const POST: APIRoute = async ({ request, cookies, redirect }) => {
  const form = await request.formData();
  const username = String(form.get('username') || '');
  const password = String(form.get('password') || '');

  if (await verifyAdminLogin(username, password)) {
    setAdminSession(cookies);
    return redirect('/admin');
  }

  return redirect('/admin/login?error=1');
};
