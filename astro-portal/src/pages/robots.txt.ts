import type { APIRoute } from 'astro';
import { getSiteSettingsSafe, isIndexingEnabled, isSitemapEnabled, SETTING_KEYS } from '../lib/site-settings';
import { baseUrl } from '../lib/config';

export const GET: APIRoute = async () => {
  const settings = await getSiteSettingsSafe();
  const indexing = isIndexingEnabled(settings);
  const extra = (settings[SETTING_KEYS.robotsExtra] || '').trim();

  const lines = [
    'User-agent: *',
    indexing ? 'Allow: /' : 'Disallow: /',
    'Disallow: /admin',
    'Disallow: /admin/',
    'Disallow: /api',
    'Disallow: /api/',
    'Disallow: /api/admin',
  ];

  if (extra) lines.push(extra);
  if (isSitemapEnabled(settings)) {
    lines.push(`Sitemap: ${baseUrl('/sitemap.xml', settings)}`);
  }

  return new Response(`${lines.join('\n')}\n`, {
    headers: { 'Content-Type': 'text/plain; charset=UTF-8' },
  });
};
