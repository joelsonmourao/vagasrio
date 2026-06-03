import type { APIRoute } from 'astro';
import { importJobsFromSpreadsheet } from '../../../lib/import-jobs';

export const POST: APIRoute = async ({ request, redirect }) => {
  const form = await request.formData();
  const file = form.get('sheet');
  if (!(file instanceof File) || file.size < 1) {
    return redirect('/admin/importar-vagas?error=arquivo');
  }
  const buffer = Buffer.from(await file.arrayBuffer());
  try {
    const summary = await importJobsFromSpreadsheet(buffer, file.name);
    const q = new URLSearchParams({
      ok: '1',
      total: String(summary.total),
      imported: String(summary.imported),
      ignored: String(summary.ignored),
      errors: String(summary.errors),
    });
    return redirect(`/admin/importar-vagas?${q}`);
  } catch (e) {
    const msg = encodeURIComponent(e instanceof Error ? e.message : 'Erro');
    return redirect(`/admin/importar-vagas?error=${msg}`);
  }
};
