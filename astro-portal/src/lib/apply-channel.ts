export type ApplyChannelType = 'email' | 'url' | 'whatsapp' | 'phone' | 'invalid' | 'empty';

function isExampleUrl(url: string): boolean {
  const raw = url.trim();
  try {
    const parsed = new URL(raw.includes('://') ? raw : `https://${raw}`);
    const host = parsed.hostname.replace(/^www\./i, '').toLowerCase();
    return host === 'example.com' || host === 'example.org';
  } catch {
    return /example\.(com|org)/i.test(raw);
  }
}

export type ApplyChannel = {
  type: ApplyChannelType;
  raw: string;
  /** E-mail limpo (sem mailto). */
  email?: string;
  /** URL externa https ou link wa.me. */
  href?: string;
  /** mailto: para botão secundário. */
  mailto?: string;
  /** E.164 para schema (ex.: +5521999999999). */
  telephone?: string;
};

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/i;

function digitsOnly(value: string): string {
  return value.replace(/\D/g, '');
}

function looksLikeEmail(value: string): boolean {
  const s = value.trim().replace(/^mailto:/i, '');
  if (EMAIL_RE.test(s)) return true;
  return s.includes('@') && !/\s/.test(s) && !/^https?:\/\//i.test(s) && !s.startsWith('/');
}

function extractEmail(raw: string): string | null {
  const s = raw.trim().replace(/^mailto:/i, '');
  return looksLikeEmail(s) ? s : null;
}

function parseBrazilPhone(raw: string): { e164: string; waDigits: string } | null {
  const d = digitsOnly(raw);
  if (d.length < 10 || d.length > 13) return null;
  let national = d;
  if (national.startsWith('55') && national.length >= 12) {
    return { e164: `+${national}`, waDigits: national };
  }
  if (national.length === 10 || national.length === 11) {
    return { e164: `+55${national}`, waDigits: `55${national}` };
  }
  return null;
}

function isWhatsAppContext(raw: string): boolean {
  return /whatsapp|wa\.me|api\.whatsapp/i.test(raw);
}

function parseWhatsApp(raw: string): ApplyChannel | null {
  const trimmed = raw.trim();
  const waMe = trimmed.match(/wa\.me\/(\d+)/i);
  if (waMe) {
    const phone = parseBrazilPhone(waMe[1]);
    if (phone) {
      return {
        type: 'whatsapp',
        raw: trimmed,
        href: `https://wa.me/${phone.waDigits}`,
        telephone: phone.e164,
      };
    }
  }

  const phoneParam = trimmed.match(/[?&]phone=(\d+)/i);
  if (phoneParam && isWhatsAppContext(trimmed)) {
    const phone = parseBrazilPhone(phoneParam[1]);
    if (phone) {
      return {
        type: 'whatsapp',
        raw: trimmed,
        href: `https://wa.me/${phone.waDigits}`,
        telephone: phone.e164,
      };
    }
  }

  if (isWhatsAppContext(trimmed)) {
    const phone = parseBrazilPhone(trimmed);
    if (phone) {
      return {
        type: 'whatsapp',
        raw: trimmed,
        href: `https://wa.me/${phone.waDigits}`,
        telephone: phone.e164,
      };
    }
  }

  return null;
}

function parseExternalUrl(raw: string): ApplyChannel | null {
  let href = raw.trim();
  if (!/^https?:\/\//i.test(href)) {
    if (/^[a-z0-9][-a-z0-9.]*\.[a-z]{2,}(\/\S*)?$/i.test(href)) {
      href = `https://${href}`;
    } else {
      return null;
    }
  }
  try {
    const u = new URL(href);
    if (isExampleUrl(u.href)) return null;
    if (/whatsapp|wa\.me/i.test(u.hostname + u.pathname)) {
      return parseWhatsApp(u.href);
    }
    return { type: 'url', raw, href: u.href };
  } catch {
    return null;
  }
}

function parsePhone(raw: string): ApplyChannel | null {
  const trimmed = raw.trim();
  if (/^tel:/i.test(trimmed)) {
    const phone = parseBrazilPhone(trimmed.replace(/^tel:/i, ''));
    if (phone) {
      return { type: 'phone', raw: trimmed, href: `tel:${phone.e164}`, telephone: phone.e164 };
    }
    return null;
  }
  if (/^https?:\/\//i.test(trimmed) || trimmed.includes('@')) return null;
  const phone = parseBrazilPhone(trimmed);
  if (phone && !isWhatsAppContext(trimmed)) {
    return { type: 'phone', raw: trimmed, href: `tel:${phone.e164}`, telephone: phone.e164 };
  }
  return null;
}

/** Identifica o canal de candidatura sem gerar URL interna do portal. */
export function parseApplyChannel(raw: string | null | undefined): ApplyChannel {
  if (!raw?.trim()) {
    return { type: 'empty', raw: '' };
  }

  const trimmed = raw.trim();

  if (isExampleUrl(trimmed)) {
    return { type: 'invalid', raw: trimmed };
  }

  const email = extractEmail(trimmed);
  if (email) {
    return {
      type: 'email',
      raw: trimmed,
      email,
      mailto: `mailto:${email}`,
    };
  }

  const wa = parseWhatsApp(trimmed);
  if (wa) return wa;

  if (/^https?:\/\//i.test(trimmed) || /^[a-z0-9][-a-z0-9.]*\.[a-z]{2,}/i.test(trimmed)) {
    const ext = parseExternalUrl(trimmed);
    if (ext) return ext;
  }

  const phone = parsePhone(trimmed);
  if (phone) return phone;

  return { type: 'invalid', raw: trimmed };
}

export function isValidApplyChannel(channel: ApplyChannel): boolean {
  return channel.type !== 'empty' && channel.type !== 'invalid';
}

/** Compat: href seguro para uso legado (mailto, https, tel, wa.me). */
export function resolveApplyHref(channel: ApplyChannel): string | null {
  if (!isValidApplyChannel(channel)) return null;
  if (channel.type === 'email') return channel.mailto ?? null;
  return channel.href ?? null;
}

export type ApplyPrimaryCta = {
  href: string;
  label: string;
  external: boolean;
  ariaLabel: string;
};

/** CTA principal (agregador — candidatura no canal da empresa). */
export function getApplyPrimaryCta(channel: ApplyChannel): ApplyPrimaryCta | null {
  if (channel.type === 'url' && channel.href) {
    return {
      href: channel.href,
      label: 'Candidatar-se no site da empresa',
      external: true,
      ariaLabel: 'Candidatar-se no site da empresa (abre em nova aba)',
    };
  }
  if (channel.type === 'email' && channel.mailto) {
    return {
      href: channel.mailto,
      label: 'Candidatar-se por e-mail',
      external: false,
      ariaLabel: 'Candidatar-se por e-mail',
    };
  }
  if (channel.type === 'whatsapp' && channel.href) {
    return {
      href: channel.href,
      label: 'Candidatar-se no WhatsApp da empresa',
      external: true,
      ariaLabel: 'Candidatar-se no WhatsApp da empresa (abre em nova aba)',
    };
  }
  if (channel.type === 'phone' && channel.href) {
    return {
      href: channel.href,
      label: 'Ligar para candidatura',
      external: false,
      ariaLabel: 'Ligar para candidatura',
    };
  }
  return null;
}
