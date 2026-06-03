import type { APIRoute } from 'astro';
import { clearSitemapCache, getSitemapBundleEntries } from '../lib/sitemap-data';
import {
  buildUrlsetXml,
  emptyUrlsetXml,
  parseSitemapBundle,
  xmlResponse,
} from '../lib/sitemap-xml';
import { getSiteSettingsSafe, isSitemapEnabled } from '../lib/site-settings';

export const GET: APIRoute = async ({ params }) => {
  const settings = await getSiteSettingsSafe();

  if (!isSitemapEnabled(settings)) {
    return xmlResponse(emptyUrlsetXml());
  }

  const name = params.name ?? '';
  const parsed = parseSitemapBundle(name);
  if (!parsed) {
    return new Response('Not Found', { status: 404 });
  }

  clearSitemapCache();
  const entries = await getSitemapBundleEntries(name);
  if (entries === null) {
    return new Response('Not Found', { status: 404 });
  }

  if (entries.length === 0) {
    return xmlResponse(emptyUrlsetXml());
  }

  return xmlResponse(buildUrlsetXml(entries, settings), {
    'X-Sitemap-Bundle': name,
    'X-Sitemap-Url-Count': String(entries.length),
  });
};
