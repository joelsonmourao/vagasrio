import type { APIRoute } from 'astro';
import { saveSiteSettings, SETTING_KEYS } from '../../../lib/site-settings';

export const POST: APIRoute = async ({ request, redirect }) => {
  const form = await request.formData();
  const data: Record<string, string> = {};

  for (const key of Object.values(SETTING_KEYS)) {
    if (!form.has(key)) continue;
    const values = form.getAll(key).map(String);
    data[key] = values[values.length - 1] ?? '';
  }

  if (!data[SETTING_KEYS.indexingEnabled]) {
    data[SETTING_KEYS.indexingEnabled] = '0';
  }
  if (!data[SETTING_KEYS.sitemapNote]) {
    data[SETTING_KEYS.sitemapNote] = '0';
  }
  if (!data[SETTING_KEYS.adsenseEnabled]) {
    data[SETTING_KEYS.adsenseEnabled] = '0';
  }

  try {
    await saveSiteSettings(data);
    return redirect('/admin/configuracoes?ok=1');
  } catch (e) {
    const msg = encodeURIComponent(e instanceof Error ? e.message : 'Erro ao salvar');
    return redirect(`/admin/configuracoes?error=${msg}`);
  }
};
