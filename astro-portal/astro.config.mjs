// @ts-check
import { defineConfig } from 'astro/config';
import node from '@astrojs/node';

export default defineConfig({
  output: 'server',
  adapter: node({ mode: 'standalone' }),
  site: process.env.SITE_BASE_URL || 'http://localhost:4321',
  trailingSlash: 'never',
  // Barra flutuante preta no rodapé em dev = Astro Dev Toolbar (não é do site)
  devToolbar: { enabled: false },
  redirects: {
    '/cidade/[slug]': '/vagas/cidade/[slug]',
    '/cidades/[slug]': '/vagas/cidade/[slug]',
    '/vaga/[slug]': '/vagas/[slug]',
    '/empresa/[slug]': '/empresas/[slug]',
    '/admin/jobs': '/admin/vagas',
    '/admin/import': '/admin/importar-vagas',
    '/admin/blog/posts': '/admin/posts',
    '/admin/importar': '/admin/importar-vagas',
  },
});
