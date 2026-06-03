import type { APIRoute } from 'astro';
import { saveBlogPost, deleteBlogPost, toggleBlogPost } from '../../../lib/portal';

export const POST: APIRoute = async ({ request, redirect }) => {
  const form = await request.formData();
  const action = String(form.get('_action') || 'save');
  const id = Number(form.get('id') || 0);

  try {
    if (action === 'delete' && id) {
      await deleteBlogPost(id);
      return redirect('/admin/posts?ok=deleted');
    }
    if (action === 'toggle' && id) {
      await toggleBlogPost(id);
      return redirect('/admin/posts?ok=toggled');
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
