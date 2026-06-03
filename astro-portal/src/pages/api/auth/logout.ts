import type { APIRoute } from 'astro';
import { clearAdminSession } from '../../../lib/auth';

export const POST: APIRoute = async ({ cookies, redirect }) => {
  clearAdminSession(cookies);
  return redirect('/admin/login');
};
