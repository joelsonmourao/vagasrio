import { defineMiddleware } from 'astro:middleware';
import { isAdminLoggedIn } from './lib/auth';
import { resolveLegacySlugRedirect } from './lib/legacy-slug-redirects';

export const onRequest = defineMiddleware(async (context, next) => {
  const path = context.url.pathname;

  const legacyTarget = resolveLegacySlugRedirect(path);
  if (legacyTarget) {
    return context.redirect(legacyTarget, 301);
  }

  const needsAuth =
    (path.startsWith('/admin') && path !== '/admin/login') || path.startsWith('/api/admin');

  if (needsAuth && !isAdminLoggedIn(context.cookies)) {
    if (path.startsWith('/api/')) {
      return new Response('Unauthorized', { status: 401 });
    }
    return context.redirect('/admin/login');
  }

  return next();
});
