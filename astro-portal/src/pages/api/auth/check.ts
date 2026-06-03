import type { APIRoute } from 'astro';
import { getAdminCookieValue, verifyAdminSession } from '../../../lib/auth';

export const GET: APIRoute = async ({ cookies }) => {
  const raw = getAdminCookieValue(cookies);
  const cookiePresent = Boolean(raw);
  const session = verifyAdminSession(raw);

  console.info(`[auth/check] cookiePresent=${cookiePresent} reason=${session.reason}`);

  return new Response(
    JSON.stringify({
      loggedIn: session.valid,
      cookiePresent,
      user: session.user,
      reason: session.reason,
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
