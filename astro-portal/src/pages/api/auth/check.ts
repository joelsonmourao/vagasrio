import type { APIRoute } from 'astro';
import { getAdminSessionUser, isAdminLoggedIn } from '../../../lib/auth';

export const GET: APIRoute = async ({ cookies }) => {
  const cookiePresent = Boolean(cookies.get('vagas_admin_session')?.value);
  const loggedIn = isAdminLoggedIn(cookies);
  const user = loggedIn ? getAdminSessionUser(cookies) : null;

  return new Response(
    JSON.stringify({
      loggedIn,
      cookiePresent,
      user,
    }),
    {
      status: 200,
      headers: {
        'Content-Type': 'application/json; charset=utf-8',
        'Cache-Control': 'no-store',
      },
    },
  );
};
