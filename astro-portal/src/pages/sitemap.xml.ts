import type { APIRoute } from 'astro';
import { buildSitemapManifest, clearSitemapCache } from '../lib/sitemap-data';
import { buildSitemapIndexXml, emptySitemapIndexXml, xmlResponse } from '../lib/sitemap-xml';
import { getSiteSettingsSafe, isSitemapEnabled } from '../lib/site-settings';

export const GET: APIRoute = async () => {
  const settings = await getSiteSettingsSafe();

  if (!isSitemapEnabled(settings)) {
    return xmlResponse(emptySitemapIndexXml());
  }

  clearSitemapCache();

  try {
    const manifest = await buildSitemapManifest();
    const body = buildSitemapIndexXml(manifest.files, settings);
    return xmlResponse(body, {
      'X-Sitemap-Index-Count': String(manifest.stats.indexCount),
      'X-Sitemap-Jobs': String(manifest.stats.jobs),
      'X-Sitemap-Posts': String(manifest.stats.posts),
    });
  } catch (err) {
    console.error('[sitemap-index]', err);
    const body = buildSitemapIndexXml(
      [{ filename: 'sitemap-pages.xml', lastmod: new Date(), urlCount: 0 }],
      settings,
    );
    return xmlResponse(body);
  }
};
