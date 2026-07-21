import type { APIRoute } from 'astro';
import {
  saveJob,
  deleteJob,
  toggleJob,
  bulkToggleJobs,
  bulkDeleteJobs,
  parseIdList,
} from '../../../lib/portal';

function redirectBack(request: Request, fallback: string, ok: string) {
  const referer = request.headers.get('referer');
  if (referer) {
    try {
      const url = new URL(referer);
      url.searchParams.set('ok', ok);
      url.searchParams.delete('error');
      return url.pathname + url.search;
    } catch {
      /* ignore */
    }
  }
  return `${fallback}?ok=${ok}`;
}

export const POST: APIRoute = async ({ request, redirect }) => {
  const form = await request.formData();
  const action = String(form.get('_action') || 'save');
  const id = Number(form.get('id') || 0);
  const ids = parseIdList(form.getAll('ids').length ? form.getAll('ids') : form.get('ids'));

  try {
    if (action === 'bulk_activate') {
      const { count } = await bulkToggleJobs(ids, true);
      return redirect(redirectBack(request, '/admin/vagas', `bulk_on_${count}`));
    }
    if (action === 'bulk_deactivate') {
      const { count } = await bulkToggleJobs(ids, false);
      return redirect(redirectBack(request, '/admin/vagas', `bulk_off_${count}`));
    }
    if (action === 'bulk_delete') {
      const { count } = await bulkDeleteJobs(ids);
      return redirect(redirectBack(request, '/admin/vagas', `bulk_del_${count}`));
    }
    if (action === 'delete' && id) {
      await deleteJob(id);
      return redirect(redirectBack(request, '/admin/vagas', 'deleted'));
    }
    if (action === 'toggle' && id) {
      await toggleJob(id);
      return redirect(redirectBack(request, '/admin/vagas', 'toggled'));
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
