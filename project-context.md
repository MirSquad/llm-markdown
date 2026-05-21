# LLM Markdown — Project Context

## Background

Built to make miriamschwab.me content accessible to AI language models. Eight existing plugins were analyzed before building this one — the key takeaway was that most were overengineered (custom converters, content negotiation, user-agent sniffing) when a simpler approach works better.

## Architecture

### Components

- **llm-markdown.php** — main plugin file, hooks, constants, helpers
- **includes/class-llmmd-converter.php** — HTML→markdown conversion using league/html-to-markdown
- **includes/class-llmmd-server.php** — `.md` URL routing and serving via rewrite rules
- **includes/class-llmmd-llmstxt.php** — `/llms.txt` generation with transient caching
- **includes/class-llmmd-admin.php** — settings page (post types, CSS selector, regenerate button)
- **uninstall.php** — clean removal of all plugin data
- **vendor/** — bundled league/html-to-markdown (no composer install needed)

### Key design choices

- **Pre-generation on save** — `.md` requests serve from post meta with zero processing
- **Single rewrite rule** (`^(.+)\.md/?$`) — front page "index" case handled in `resolve_post_id()`
- **Transient caching** for llms.txt — invalidated on publish/unpublish/settings change/version upgrade
- **Bundled vendor** — no build step, works as a simple folder upload

### Hooks and filters

- `save_post` (priority 20): generates markdown, stores in post meta
- `transition_post_status`: invalidates llms.txt cache
- `wp_head`: outputs `<link rel="alternate" type="text/markdown">` tag
- `llmmd_rendered_content` filter: modify rendered HTML before markdown conversion (receives `$content` and `$post`)

## Data stored

| What | Where | Lifecycle |
|---|---|---|
| Markdown content | `_llmmd_content` post meta | Written on save, deleted on unpublish/uninstall |
| Plugin settings | `llmmd_settings` option | Keys: `post_types` (array), `root_selector` (string) |
| Plugin version | `llmmd_version` option | Used for upgrade detection, deleted on uninstall |
| llms.txt cache | `llmmd_llms_txt` transient | 24h TTL, invalidated on content/settings/version changes |

## Testing checklist

1. Basic `.md` URLs — visit `your-site.com/sample-page.md`
2. Front page — visit `your-site.com/index.md`
3. llms.txt — visit `your-site.com/llms.txt`
4. Post save — edit and save a post, check `.md` URL for updated content
5. Settings — change enabled post types, verify disabled types return 404
6. Password-protected — set a post as password-protected, verify `.md` returns 403
7. View source — check for `<link rel="alternate" type="text/markdown">` tag
8. Regenerate — use "Regenerate All" on the settings page
9. Headers — `curl -I` should show: `Content-Type: text/markdown; charset=UTF-8`, `X-Content-Type-Options: nosniff`, `X-Robots-Tag: noindex`, `Link: <...>; rel="canonical"`

## Version bump checklist

Update in all four locations:
1. `Version:` in plugin header (`llm-markdown.php`)
2. `LLMMD_VERSION` constant (`llm-markdown.php`)
3. `Stable tag:` in `readme.txt`
4. `CHANGELOG.md`

## Dependencies

- **league/html-to-markdown** (bundled in vendor/) — zero sub-dependencies
- WordPress 6.0+, PHP 7.4+

## Deployment

Plugin is installed on miriamschwab.me (Elementor Hosting). Yoast SEO's llms.txt feature is disabled to avoid conflicts.
