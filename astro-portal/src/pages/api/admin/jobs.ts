import type { APIRoute } from 'astro';
import { saveJob, deleteJob, toggleJob } from '../../../lib/portal';

export const POST: APIRoute = async ({ request, redirect }) => {
  const form = await request.formData();
  const action = String(form.get('_action') || 'save');
  const id = Number(form.get('id') || 0);

  try {
    if (action === 'delete' && id) {
      await deleteJob(id);
      return redirect('/admin/vagas?ok=deleted');
    }
    if (action === 'toggle' && id) {
      await toggleJob(id);
      return redirect('/admin/vagas?ok=toggled');
    }

    const data: Record<string, string> = {};
    form.forEach((v, k) => {
      if (typeof v === 'string') data[k] = v;
    });
    const job = await saveJob(data, id || undefined);
    return redirect(id ? `/admin/vagas/editar/${job.id}?ok=1` : '/admin/vagas?ok=created');
  } catch (e) {
    const msg = encodeURIComponent(e instanceof Error ? e.message : 'Erro');
    const back = id ? `/admin/vagas/editar/${id}?error=${msg}` : `/admin/vagas/nova?error=${msg}`;
    return redirect(back);
  }
};
