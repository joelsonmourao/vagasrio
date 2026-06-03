import type { APIRoute } from 'astro';
import { getSiteSettingsSafe, SETTING_KEYS } from '../lib/site-settings';

export const GET: APIRoute = async () => {
  const settings = await getSiteSettingsSafe();
  const body = (settings[SETTING_KEYS.adsTxt] || '').trim();
  const content = body || 'google.com, pub-0000000000000000, DIRECT, f08c47fec0942fa0';

  return new Response(`${content}\n`, {
    headers: {
      'Content-Type': 'text/plain; charset=UTF-8',
      'Cache-Control': 'public, max-age=3600',
    },
  });
};
