# LLM Markdown — Session Opener

## What this plugin does

LLM Markdown serves pre-generated markdown versions of WordPress content at `.md` URLs for consumption by LLMs and AI tools. It also generates a `/llms.txt` site index listing all available markdown content.

## Scope

Lightweight and focused. No custom tables, no cron jobs, no frontend JavaScript. The plugin:
- Converts HTML to markdown on post save and stores the result in post meta
- Serves stored markdown instantly on `.md` URL requests
- Generates a cached `/llms.txt` index
- Adds `<link rel="alternate" type="text/markdown">` for discoverability

## How to approach work

- The plugin is deployed on miriamschwab.me — test changes there
- Pre-generation on save is a core design choice (not on-demand conversion)
- Uses `league/html-to-markdown` bundled in vendor/ — no composer install needed
- All changes need version bumps in four places (see DEVELOPMENT.md checklist)
- The plugin conflicts with Yoast SEO's llms.txt — Yoast's version should be disabled when this plugin is active
- Read all five pillar docs before starting work
