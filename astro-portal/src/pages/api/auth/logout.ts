import type { APIRoute } from 'astro';
import { clearAdminSession } from '../../../lib/auth';

const logout: APIRoute = async ({ cookies, redirect }) => {
  clearAdminSession(cookies);
  return redirect('/admin/login', 303);
};

export const GET = logout;
export const POST = logout;
