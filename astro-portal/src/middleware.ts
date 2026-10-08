import { defineMiddleware } from 'astro:middleware';
import { getAdminCookieValue, verifyAdminSession } from './lib/auth';
import { resolveLegacySlugRedirect } from './lib/legacy-slug-redirects';

function withSecurityHeaders(response: Response): Response {
  response.headers.set('X-Content-Type-Options', 'nosniff');
  response.headers.set('X-Frame-Options', 'SAMEORIGIN');
  response.headers.set('Referrer-Policy', 'strict-origin-when-cross-origin');
  response.headers.set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
  return response;
}

export const onRequest = defineMiddleware(async (context, next) => {
  const path = context.url.pathname;

  const legacyTarget = resolveLegacySlugRedirect(path);
  if (legacyTarget) {
    return withSecurityHeaders(context.redirect(legacyTarget, 301));
  }

  const needsAuth =
    (path.startsWith('/admin') && path !== '/admin/login') || path.startsWith('/api/admin');

  if (needsAuth) {
    const session = verifyAdminSession(getAdminCookieValue(context.cookies));
    console.info(`[auth/session] reason=${session.reason}`);
    if (!session.valid) {
      if (path.startsWith('/api/')) {
        return withSecurityHeaders(new Response('Unauthorized', { status: 401 }));
      }
      return withSecurityHeaders(context.redirect('/admin/login'));
    }
  }

  return withSecurityHeaders(await next());
});
