import type { APIRoute } from 'astro';
import {
  saveBlogPost,
  deleteBlogPost,
  toggleBlogPost,
  bulkToggleBlogPosts,
  bulkDeleteBlogPosts,
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
      const { count } = await bulkToggleBlogPosts(ids, true);
      return redirect(redirectBack(request, '/admin/posts', `bulk_on_${count}`));
    }
    if (action === 'bulk_deactivate') {
      const { count } = await bulkToggleBlogPosts(ids, false);
      return redirect(redirectBack(request, '/admin/posts', `bulk_off_${count}`));
    }
    if (action === 'bulk_delete') {
      const { count } = await bulkDeleteBlogPosts(ids);
      return redirect(redirectBack(request, '/admin/posts', `bulk_del_${count}`));
    }
    if (action === 'delete' && id) {
      await deleteBlogPost(id);
      return redirect(redirectBack(request, '/admin/posts', 'deleted'));
    }
    if (action === 'toggle' && id) {
      await toggleBlogPost(id);
      return redirect(redirectBack(request, '/admin/posts', 'toggled'));
    }

    const data: Record<string, string> = {};
    form.forEach((v, k) => {
      if (typeof v === 'string') data[k] = v;
    });
    const post = await saveBlogPost(data, id || undefined);
    return redirect(id ? `/admin/posts/editar/${post.id}?ok=1` : '/admin/posts?ok=created');
  } catch (e) {
    const msg = encodeURIComponent(e instanceof Error ? e.message : 'Erro');
    const back = id ? `/admin/posts/editar/${id}?error=${msg}` : `/admin/posts/novo?error=${msg}`;
    return redirect(back);
  }
};
