import type { APIRoute } from 'astro';
import { writeFile, mkdir } from 'node:fs/promises';
import { join } from 'node:path';

export const POST: APIRoute = async ({ request, redirect }) => {
  const form = await request.formData();
  const file = form.get('file');
  if (!(file instanceof File) || file.size < 1) {
    return redirect('/admin/configuracoes?error=upload');
  }

  const maxBytes = 5 * 1024 * 1024;
  if (file.size > maxBytes) {
    return redirect('/admin/configuracoes?error=tamanho');
  }

  const ext = file.name.split('.').pop()?.toLowerCase() || 'bin';
  if (!['png', 'jpg', 'jpeg', 'webp', 'svg', 'gif'].includes(ext)) {
    return redirect('/admin/configuracoes?error=formato');
  }

  const uploadsDir = join(process.cwd(), 'public', 'uploads');
  await mkdir(uploadsDir, { recursive: true });
  const name = `${Date.now()}-${file.name.replace(/[^a-zA-Z0-9._-]/g, '')}`;
  const buffer = Buffer.from(await file.arrayBuffer());
  await writeFile(join(uploadsDir, name), buffer);

  return redirect(`/admin/configuracoes?uploaded=/uploads/${name}`);
};
