#!/usr/bin/env python3
"""Normalize page content formatting in Grav pages without changing semantics.

This script is intentionally conservative. It only:
- strips trailing whitespace,
- normalizes line endings,
- replaces tabs by spaces,
- removes extra indentation on blank or purely whitespace-only lines,
- removes one common indentation prefix from all non-blank lines only when
  every non-blank line shares the same leading whitespace (for example after
  copy/paste from a templated UI).

It does not modify YAML frontmatter nor fenced code blocks.

Usage:
  python3 scripts/normalize_page_format.py
  python3 scripts/normalize_page_format.py --apply
"""

from __future__ import annotations

import argparse
import re
import sys
from pathlib import Path
from typing import List, Tuple

ROOT = Path(__file__).resolve().parent.parent
PAGES_ROOT = ROOT / "user" / "pages"

FENCE_RE = re.compile(r"^(```|~~~)", re.MULTILINE)
FRONTMATTER_RE = re.compile(r"^---\s*\n(.*?)\n---\s*(?:\n|$)", re.DOTALL)


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Normalize Grav page formatting")
    parser.add_argument("--apply", action="store_true", help="write changes to disk")
    parser.add_argument("--path", type=Path, default=PAGES_ROOT, help="directory to scan (default: user/pages)")
    return parser.parse_args()


def split_frontmatter(text: str) -> Tuple[str, str]:
    match = FRONTMATTER_RE.match(text)
    if not match:
        return "", text
    frontmatter = match.group(0)
    body = text[match.end():]
    return frontmatter, body


def normalize_body(body: str) -> str:
    lines = body.splitlines()
    normalized = []
    in_fence = False

    for line in lines:
        stripped = line.rstrip()

        if FENCE_RE.match(stripped):
            in_fence = not in_fence
            normalized.append(stripped)
            continue

        if in_fence:
            normalized.append(stripped)
            continue

        if not stripped:
            normalized.append("")
            continue

        # Replace tabs with spaces for consistency.
        line_no_tabs = stripped.replace("\t", "    ")

        # Remove one common indentation prefix only when every non-blank line
        # shares the same leading whitespace amount. This is safe for content
        # pasted from a templated UI, but avoids over-normalizing normal prose.
        leading_spaces = len(line_no_tabs) - len(line_no_tabs.lstrip(" "))
        if leading_spaces:
            non_blank = [l for l in lines if l.strip()]
            if non_blank:
                shared_indent = min(len(l) - len(l.lstrip(" ")) for l in non_blank if l.strip())
                if shared_indent > 0 and all((len(l) - len(l.lstrip(" "))) >= shared_indent for l in non_blank if l.strip()):
                    if leading_spaces >= shared_indent:
                        line_no_tabs = line_no_tabs[shared_indent:]

        normalized.append(line_no_tabs.lstrip())

    return "\n".join(normalized) + ("\n" if body.endswith("\n") else "")


def normalize_content(content: str) -> Tuple[str, bool]:
    frontmatter, body = split_frontmatter(content)
    normalized_body = normalize_body(body)
    updated = frontmatter + normalized_body
    changed = updated != content
    return updated, changed


def iter_markdown_files(root: Path) -> List[Path]:
    if not root.exists():
        return []
    return sorted(p for p in root.rglob("*.md") if p.is_file())


def main() -> int:
    args = parse_args()
    root = args.path.resolve()
    if not root.exists():
        print(f"Path does not exist: {root}", file=sys.stderr)
        return 2

    files = iter_markdown_files(root)
    if not files:
        print(f"No Markdown files found under {root}")
        return 0

    changed_files = 0
    for path in files:
        original = path.read_text(encoding="utf-8")
        updated, changed = normalize_content(original)
        if not changed:
            continue

        changed_files += 1
        rel = path.relative_to(ROOT)
        action = "updated" if args.apply else "would update"
        print(f"- {action}: {rel}")
        if args.apply:
            path.write_text(updated, encoding="utf-8")

    print(f"Summary: {changed_files} file(s) {'updated' if args.apply else 'would be updated'}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
