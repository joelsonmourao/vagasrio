#!/usr/bin/env python3
"""Exporta database/portal.sqlite para astro-portal/prisma/seed-data.json."""

from __future__ import annotations

import json
import sqlite3
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
DB = ROOT / "database" / "portal.sqlite"
OUT = ROOT / "astro-portal" / "prisma" / "seed-data.json"


def rows(cur, sql: str) -> list[dict]:
    cur.execute(sql)
    cols = [d[0] for d in cur.description]
    return [dict(zip(cols, row)) for row in cur.fetchall()]


def main() -> None:
    if not DB.exists():
        raise SystemExit(f"Banco nao encontrado: {DB}")

    conn = sqlite3.connect(DB)
    conn.row_factory = sqlite3.Row
    cur = conn.cursor()

    payload = {
        "exported_at": __import__("datetime").datetime.utcnow().isoformat() + "Z",
        "source": str(DB),
        "companies": rows(cur, "SELECT * FROM companies ORDER BY id"),
        "categories": rows(cur, "SELECT * FROM categories ORDER BY id"),
        "cities": rows(cur, "SELECT * FROM cities ORDER BY id"),
        "jobs": rows(cur, "SELECT * FROM jobs ORDER BY id"),
        "blog_categories": rows(cur, "SELECT * FROM blog_categories ORDER BY id"),
        "blog_posts": rows(cur, "SELECT * FROM blog_posts ORDER BY id"),
    }

    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"OK: {OUT}")
    print(
        f"  jobs={len(payload['jobs'])} posts={len(payload['blog_posts'])} "
        f"cities={len(payload['cities'])} companies={len(payload['companies'])}"
    )


if __name__ == "__main__":
    main()
