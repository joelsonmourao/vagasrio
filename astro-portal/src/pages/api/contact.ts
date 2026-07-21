import type { APIRoute } from 'astro';
import { siteConfig } from '../../lib/config';
import { getSiteSettingsSafe, SETTING_KEYS } from '../../lib/site-settings';

function subjectLabel(raw: string): string {
  const map: Record<string, string> = {
    duvida: 'Dúvida geral',
    correcao: 'Correção de vaga',
    privacidade: 'Privacidade / LGPD',
    publicidade: 'Publicidade / AdSense',
    outro: 'Outro',
  };
  return map[raw] || 'Contato';
}

export const POST: APIRoute = async ({ request, redirect }) => {
  const form = await request.formData();
  const honeypot = String(form.get('website') || '').trim();
  if (honeypot) {
    return redirect('/contato?sent=1');
  }

  const name = String(form.get('name') || '').trim();
  const email = String(form.get('email') || '').trim();
  const subject = String(form.get('subject') || 'outro').trim();
  const message = String(form.get('message') || '').trim();

  if (name.length < 2 || message.length < 10 || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    return redirect(`/contato?error=${encodeURIComponent('Preencha nome, e-mail válido e mensagem (mín. 10 caracteres).')}`);
  }

  const settings = await getSiteSettingsSafe();
  const to = settings[SETTING_KEYS.contactEmail] || siteConfig.contactEmail;
  const label = subjectLabel(subject);
  const payload = {
    name,
    email,
    message,
    _subject: `[${siteConfig.name}] ${label}`,
    _replyto: email,
    _template: 'table',
  };

  try {
    const res = await fetch(`https://formsubmit.co/ajax/${encodeURIComponent(to)}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify(payload),
    });
    if (!res.ok) {
      const text = await res.text().catch(() => '');
      console.error('[contact] formsubmit failed', res.status, text.slice(0, 200));
      return redirect(
        `/contato?error=${encodeURIComponent(`Não foi possível enviar agora. Escreva para ${to}.`)}`,
      );
    }
  } catch (err) {
    console.error('[contact]', err);
    return redirect(
      `/contato?error=${encodeURIComponent(`Falha de envio. Use o e-mail ${to}.`)}`,
    );
  }

  return redirect('/contato?sent=1');
};
