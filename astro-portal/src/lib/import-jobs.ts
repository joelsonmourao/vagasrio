import * as XLSX from 'xlsx';
import { prisma } from './db';
import { siteConfig } from './config';
import { saveJob } from './portal';

type Row = Record<string, string>;

function normKey(k: string): string {
  return k.trim().toLowerCase().replace(/\s+/g, '');
}

function pick(row: Row, ...keys: string[]): string {
  const map = new Map<string, string>();
  for (const [k, v] of Object.entries(row)) {
    map.set(normKey(k), String(v ?? '').trim());
  }
  for (const key of keys) {
    const val = map.get(normKey(key));
    if (val) return val;
  }
  return '';
}

export function parseSpreadsheetBuffer(buffer: Buffer, filename: string): Row[] {
  const ext = filename.split('.').pop()?.toLowerCase();
  if (ext === 'csv') {
    const text = buffer.toString('utf-8');
    const wb = XLSX.read(text, { type: 'string' });
    const sheet = wb.Sheets[wb.SheetNames[0]];
    return XLSX.utils.sheet_to_json<Row>(sheet, { defval: '' });
  }
  const wb = XLSX.read(buffer, { type: 'buffer' });
  const sheet = wb.Sheets[wb.SheetNames[0]];
  return XLSX.utils.sheet_to_json<Row>(sheet, { defval: '' });
}

export async function importJobsFromSpreadsheet(buffer: Buffer, filename: string) {
  const rows = parseSpreadsheetBuffer(buffer, filename);
  let imported = 0;
  let ignored = 0;
  let errors = 0;
  const errorDetails: { row: number; reason: string }[] = [];

  const imp = await prisma.import.create({
    data: { filename, totalRows: rows.length },
  });

  for (let i = 0; i < rows.length; i++) {
    const row = rows[i];
    const rowNum = i + 2;
    const state = pick(row, 'state', 'uf', 'estado').toUpperCase() || siteConfig.mainUf;
    if (state !== siteConfig.mainUf) {
      ignored++;
      continue;
    }
    try {
      await saveJob({
        title: pick(row, 'title', 'titulo', 'cargo'),
        company: pick(row, 'company', 'empresa'),
        city: pick(row, 'city', 'cidade'),
        state,
        description: pick(row, 'description', 'descricao'),
        apply_url: pick(row, 'applyUrl', 'applyurl', 'link', 'url'),
        category: pick(row, 'category', 'categoria'),
        salary: pick(row, 'salary', 'salario'),
        employmentType: pick(row, 'employmentType', 'employmenttype', 'tipo'),
        publishedAt: pick(row, 'publishedAt', 'publishedat'),
        validThrough: pick(row, 'validThrough', 'validthrough'),
      });
      imported++;
    } catch (e) {
      errors++;
      const reason = e instanceof Error ? e.message : 'Erro desconhecido';
      errorDetails.push({ row: rowNum, reason });
      await prisma.importError.create({
        data: {
          importId: imp.id,
          rowNumber: rowNum,
          reason,
          rawData: JSON.stringify(row),
        },
      });
    }
  }

  await prisma.import.update({
    where: { id: imp.id },
    data: {
      importedRows: imported,
      ignoredRows: ignored,
      errorRows: errors,
      summaryJson: JSON.stringify({ imported, ignored, errors }),
    },
  });

  return { importId: imp.id, total: rows.length, imported, ignored, errors, errorDetails };
}
