=== LLM Markdown ===
Contributors: illuminea
Tags: markdown, llm, ai, llms-txt, content
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Serves markdown versions of your site content at .md URLs for LLMs, with a llms.txt site index.

== Description ==

LLM Markdown makes your WordPress content accessible to AI language models by serving clean markdown versions at `.md` URLs. Every post and page gets a markdown endpoint automatically — just append `.md` to any URL.

**Features:**

* **`.md` URLs** — Append `.md` to any post or page URL to get a clean markdown version
* **llms.txt** — Auto-generated site index at `/llms.txt` listing all available markdown content
* **YAML frontmatter** — Title, date, author, URL, excerpt, categories, and tags
* **Pre-generated** — Markdown is generated when posts are saved, so `.md` requests are instant
* **Discoverable** — Adds `<link rel="alternate" type="text/markdown">` to page headers
* **Lightweight** — No custom database tables, no cron jobs, no frontend JavaScript

**How it works:**

1. When you save a post, the plugin converts it to markdown and stores it in post meta
2. When someone requests `your-post.md`, the pre-generated markdown is served instantly
3. The `/llms.txt` file lists all available markdown URLs organized by category

== Installation ==

1. Upload the `llm-markdown` folder to `/wp-content/plugins/`.
2. Activate from Plugins > Installed Plugins.
3. Configure which post types to enable under Settings > LLM Markdown.
4. Visit `/llms.txt` on your site to verify the index.

== Frequently Asked Questions ==

= Does this slow down my site? =

No. The only impact on normal page loads is a single `<link>` tag in the HTML head. Markdown is pre-generated on post save, so `.md` requests serve directly from the database with no runtime conversion.

= What URL format does it use? =

Append `.md` to any post or page URL. For example: `example.com/my-post.md`. The front page is available at `example.com/index.md`.

= What is llms.txt? =

It's an emerging convention (similar to robots.txt) that helps AI models discover available content on your site. The file at `/llms.txt` lists all your markdown-enabled content.

= Can I control which post types get markdown? =

Yes. Go to Settings > LLM Markdown and check the post types you want to enable.

== Changelog ==

= 1.1.2 =
* Fix: YAML frontmatter url and markdown_url fields now quoted for spec compliance
* Fix: Markdown link titles in llms.txt now escape ] characters to prevent broken links
* Fix: Version check moved into plugins_loaded hook
* Add: llmmd_bulk_generate_limit filter for large-site memory control
* Internal docs removed from repository

= 1.1.1 =
* Replace "View details" plugin row link with "Visit plugin site" pointing to miriamschwab.me

= 1.1.0 =
* Security: sanitize CSS selectors to prevent XPath injection
* Security: add X-Content-Type-Options: nosniff header on .md responses
* Security: use $wpdb->prepare() in uninstall.php
* Fix YAML escape order (backslashes before quotes)
* Auto-clear llms.txt transient on plugin version upgrade

= 1.0.5 =
* Decode HTML entities in llms.txt (titles, excerpts, site description, category names)
* Fix homepage URL in llms.txt showing domain.md instead of domain/index.md

= 1.0.4 =
* Decode HTML entities in YAML frontmatter
* Fix front page markdown_url showing domain.md instead of domain/index.md

= 1.0.3 =
* Fix front page /index.md by handling "index" path in resolver
* Add alternate link tag to homepage

= 1.0.2 =
* Fix front page /index.md returning 404 (rewrite rule ordering)

= 1.0.1 =
* Add post excerpts/descriptions to llms.txt entries

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.1.0 =
Security hardening: XPath injection prevention, nosniff header, prepared SQL in uninstall.

= 1.0.0 =
Initial release.
