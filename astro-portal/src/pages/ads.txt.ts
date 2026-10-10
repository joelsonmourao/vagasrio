import type { APIRoute } from 'astro';
import { sanitizeAdsTxt } from '../lib/ads-txt';
import { getSiteSettingsSafe, SETTING_KEYS } from '../lib/site-settings';

export const GET: APIRoute = async () => {
  const settings = await getSiteSettingsSafe();
  const content = sanitizeAdsTxt(settings[SETTING_KEYS.adsTxt]);

  if (!content) {
    return new Response('', {
      status: 404,
      headers: {
        'Content-Type': 'text/plain; charset=UTF-8',
        'Cache-Control': 'no-store',
      },
    });
  }

  return new Response(`${content}\n`, {
    headers: {
      'Content-Type': 'text/plain; charset=UTF-8',
      'Cache-Control': 'public, max-age=3600',
    },
  });
};
