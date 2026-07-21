#!/usr/bin/env node
/**
 * Gera capas SVG em public/assets/img/covers/rj-local/
 */
import { mkdirSync, writeFileSync } from "node:fs";
import { dirname, resolve } from "node:path";
import { fileURLToPath } from "node:url";
import { catalog, slugFor } from "./data/rj-local-editorial-catalog.mjs";

const root = resolve(dirname(fileURLToPath(import.meta.url)), "..");
const outDir = resolve(root, "public/assets/img/covers/rj-local");
mkdirSync(outDir, { recursive: true });

function escapeXml(s) {
  return String(s)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

function wrapTitle(title, max = 42) {
  const words = title.split(/\s+/);
  const lines = [];
  let line = "";
  for (const w of words) {
    const next = line ? `${line} ${w}` : w;
    if (next.length > max && line) {
      lines.push(line);
      line = w;
    } else {
      line = next;
    }
  }
  if (line) lines.push(line);
  return lines.slice(0, 3);
}

for (let i = 0; i < catalog.length; i += 1) {
  const item = catalog[i];
  const slug = slugFor(item, i);
  const lines = wrapTitle(item.title);
  const hue = item.coverHue ?? (i * 17) % 360;
  const tspans = lines
    .map((ln, idx) => `<tspan x="64" dy="${idx === 0 ? 0 : 44}">${escapeXml(ln)}</tspan>`)
    .join("");
  const svg = `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="675" viewBox="0 0 1200 675" role="img" aria-label="${escapeXml(item.title)}">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="hsl(${hue}, 45%, 18%)"/>
      <stop offset="100%" stop-color="hsl(${(hue + 28) % 360}, 55%, 28%)"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="675" fill="url(#g)"/>
  <rect x="0" y="0" width="12" height="675" fill="#2563eb"/>
  <text x="64" y="72" fill="#93c5fd" font-family="Segoe UI, Arial, sans-serif" font-size="22" font-weight="700" letter-spacing="2">VAGAS RJ</text>
  <text x="64" y="280" fill="#ffffff" font-family="Segoe UI, Arial, sans-serif" font-size="40" font-weight="700">${tspans}</text>
  <text x="64" y="620" fill="#bfdbfe" font-family="Segoe UI, Arial, sans-serif" font-size="20">Emprego no Rio de Janeiro</text>
</svg>`;
  writeFileSync(resolve(outDir, `${slug}.svg`), svg, "utf8");
}

console.log(JSON.stringify({ ok: true, covers: catalog.length, dir: outDir }));
