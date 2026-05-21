# LLM Markdown — Decisions Log

## Pre-generate markdown on save (not on-demand)

**Decision:** Convert HTML to markdown when posts are saved and store in post meta, rather than converting on each `.md` request.

**Why:** On-demand conversion adds latency to every request and runs the full rendering pipeline (do_blocks, do_shortcode, wpautop, wptexturize, DOMDocument, HtmlConverter). Pre-generation makes `.md` requests a single post meta read — essentially free. The tradeoff is stale content if the save hook is bypassed, but the server falls back to live conversion when post meta is empty.

## Single rewrite rule with path-based resolution

**Decision:** Use one rewrite rule `^(.+)\.md/?$` and handle the front page ("index") case in `resolve_post_id()` rather than separate rewrite rules.

**Why:** A separate `^index\.md/?$` rule caused 404s because `add_rewrite_rule('top')` prepends rules, making ordering unpredictable. Moving the front page logic into the resolver eliminated the rewrite conflict entirely.

## Bundle vendor/ in the repo

**Decision:** Commit `league/html-to-markdown` in vendor/ rather than requiring composer install.

**Why:** The plugin needs to work as a simple folder upload to wp-content/plugins/. No build step. The library was sourced from one of the analyzed plugins (cleaned of all references to the source plugin's autoloader).

## html_entity_decode in YAML frontmatter and llms.txt

**Decision:** Apply `html_entity_decode()` to all text output in frontmatter and llms.txt (titles, excerpts, site description, category names).

**Why:** WordPress stores smart quotes and special characters as HTML entities (`&#8217;` etc.). These look broken in plain-text markdown output. Decoding is applied at the output layer — the stored content in post meta remains unchanged.

## X-Content-Type-Options: nosniff instead of wp_kses_post on markdown output

**Decision:** Serve raw markdown with the `nosniff` header rather than running it through `wp_kses_post()`.

**Why:** `wp_kses_post()` strips angle brackets and breaks valid markdown content (e.g., inline HTML, code blocks with HTML examples). The `nosniff` header prevents browsers from interpreting the text/markdown response as HTML, which is the actual security concern. The content is served as a separate document with its own Content-Type, not embedded in an HTML page.

## Disable Yoast SEO llms.txt

**Decision:** Recommend disabling Yoast's llms.txt feature when this plugin is active.

**Why:** Both generate `/llms.txt` but Yoast's version doesn't link to `.md` URLs — it just lists post titles and regular URLs. The plugin's version organizes content by category and links to the actual markdown endpoints, which is the whole point.
