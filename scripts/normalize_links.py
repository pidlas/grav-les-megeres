#!/usr/bin/env python3
"""Normalize hard-coded internal links in Grav page content to Twig-based URLs.

By default, the script runs in dry-run mode and only prints the files it would
update. Use --apply to write the changes.

It is intentionally conservative:
- it rewrites only HTML anchor links whose href is a root-relative internal URL
  (for example /contact or /infos/compagnie);
- it leaves external URLs, anchors, mailto/tel links, and links already using
  Twig untouched;
- when a page contains Twig expressions and does not already define page-level
  Twig processing, it adds `process: twig: true` to the frontmatter.
"""

from __future__ import annotations

import argparse
import re
import sys
from pathlib import Path
from typing import List, Optional, Tuple

ROOT = Path(__file__).resolve().parent.parent
PAGES_ROOT = ROOT / "user" / "pages"

HTML_LINK_RE = re.compile(r'(<a\b[^>]*\bhref\s*=\s*)(["\'])([^"\']*)(\2)', re.IGNORECASE)
TWIG_RE = re.compile(r"\{\{.*?\}\}|\{%.*?%\}")
FRONTMATTER_RE = re.compile(r"^---\s*\n(.*?)\n---\s*(?:\n|$)", re.DOTALL)
PROCESS_TWIG_RE = re.compile(r"(?m)^\s*process:\s*$|^\s*twig:\s*(true|false)\s*$")


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Normalize hard-coded internal links in Grav pages")
    parser.add_argument("--apply", action="store_true", help="write changes to disk")
    parser.add_argument("--path", type=Path, default=PAGES_ROOT, help="directory to scan (default: user/pages)")
    return parser.parse_args()


def split_frontmatter(text: str) -> Tuple[Optional[str], str, Optional[str]]:
    match = FRONTMATTER_RE.match(text)
    if not match:
        return None, text, None

    frontmatter = match.group(1)
    body = text[match.end():]
    return frontmatter, body, match.group(0)


def merge_frontmatter(frontmatter: Optional[str], body: str, new_frontmatter: str) -> str:
    if frontmatter is None:
        return f"---\n{new_frontmatter}\n---\n{body}"
    return f"---\n{new_frontmatter}\n---\n{body}"


def ensure_twig_processing(content: str) -> Tuple[str, bool]:
    if not TWIG_RE.search(content):
        return content, False

    frontmatter_text, body, fm_block = split_frontmatter(content)
    if frontmatter_text is None:
        new_frontmatter = "process:\n  twig: true"
        return merge_frontmatter(None, body, new_frontmatter), True

    if PROCESS_TWIG_RE.search(frontmatter_text):
        return content, False

    if frontmatter_text.strip():
        new_frontmatter = frontmatter_text.rstrip() + "\nprocess:\n  twig: true"
    else:
        new_frontmatter = "process:\n  twig: true"

    return merge_frontmatter(frontmatter_text, body, new_frontmatter), True


def normalize_href(value: str) -> Optional[str]:
    if not value:
        return None

    if value.startswith(("http://", "https://", "mailto:", "tel:", "javascript:", "data:", "//")):
        return None
    if value.startswith(("#", "?")):
        return None
    if "{{" in value or "{%" in value:
        return None

    if value.startswith("/"):
        return f"{{{{ base_url_relative }}}}{value}"

    return None


def normalize_links(content: str) -> Tuple[str, int, bool]:
    changed = False
    replacements = 0

    def repl(match: re.Match[str]) -> str:
        nonlocal changed, replacements
        prefix, quote, href, quote2 = match.groups()
        normalized = normalize_href(href)
        if normalized is None:
            return match.group(0)

        changed = True
        replacements += 1
        return f"{prefix}{quote}{normalized}{quote2}"

    new_content = HTML_LINK_RE.sub(repl, content)
    return new_content, replacements, changed


def process_file(path: Path, apply: bool) -> Tuple[bool, int, bool]:
    original = path.read_text(encoding="utf-8")
    updated, frontmatter_changed = ensure_twig_processing(original)
    updated, replacements, link_changed = normalize_links(updated)

    changed = frontmatter_changed or link_changed
    if not changed:
        return False, 0, False

    if apply:
        path.write_text(updated, encoding="utf-8")

    return True, replacements, frontmatter_changed


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
    changed_links = 0

    print(f"Scanning {len(files)} Markdown files under {root}")
    for path in files:
        changed, replacements, frontmatter_changed = process_file(path, args.apply)
        if changed:
            changed_files += 1
            changed_links += replacements
            action = "updated" if args.apply else "would update"
            rel = path.relative_to(ROOT)
            print(f"- {action}: {rel} ({replacements} link replacement(s))")

    print(f"Summary: {changed_files} file(s) {'updated' if args.apply else 'would be updated'}, {changed_links} link replacement(s)")
    return 0


if __name__ == "__main__":
    sys.exit(main())
