# Changelog

## 1.1.1 — 2026-05-20

- Replace "View details" plugin row link with "Visit plugin site" pointing to miriamschwab.me

## 1.1.0 — 2026-05-20

- Security: sanitize CSS selectors to prevent XPath injection
- Security: add X-Content-Type-Options: nosniff header on .md responses
- Security: use $wpdb->prepare() in uninstall.php
- Fix YAML escape order (backslashes before quotes)
- Auto-clear llms.txt transient on plugin version upgrade

## 1.0.5 — 2026-05-20

- Decode HTML entities in llms.txt (titles, excerpts, site description, category names)
- Fix homepage URL in llms.txt showing domain.md instead of domain/index.md

## 1.0.4 — 2026-05-20

- Decode HTML entities in YAML frontmatter (&#8217; → ')
- Fix front page markdown_url showing domain.md instead of domain/index.md

## 1.0.3 — 2026-05-20

- Fix front page /index.md by handling "index" path in resolver instead of separate rewrite rule
- Add alternate link tag to homepage

## 1.0.2 — 2026-05-20

- Fix front page /index.md returning 404 (rewrite rule ordering — did not fully resolve)

## 1.0.1 — 2026-05-20

- Add post excerpts/descriptions to llms.txt entries

## 1.0.0 — 2026-05-20

Initial release.

- `.md` URL suffix serves markdown version of any post or page
- YAML frontmatter with title, date, author, URL, excerpt, categories, tags
- Pre-generated markdown stored in post meta on save (instant serving)
- Bulk generation on activation for existing content
- `/llms.txt` site index listing all available markdown URLs by category
- `<link rel="alternate" type="text/markdown">` in page headers
- Settings page for post type selection and content root CSS selector
- Proper HTTP headers: Content-Type, X-Robots-Tag noindex, canonical link
- Regenerate All button for bulk re-generation
- Clean uninstall removes all plugin data
