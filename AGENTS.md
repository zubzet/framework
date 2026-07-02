# Agents Guide

This file is a discoverability shortcut for AI coding agents (Claude Code, Cursor, Codex, Aider, …). The canonical guide lives in the docs:

→ **[docs/contributing/agents/working-with-agents.md](docs/contributing/agents/working-with-agents.md)** — repo layout, bootstrap order, testing, commit conventions, working style.

For Git workflow and commit conventions, see **[docs/contributing/how-to-contribute.md](docs/contributing/how-to-contribute.md)**.

## Quickstart

```bash
# Bring up the dockerized e2e stack
cd tests/e2e && npm run start

# Run the full e2e suite (~6 min, 590+ tests)
npm run tests

# Run one spec
npm run tests -- --spec 'tests/cypress/e2e/core/<name>.cy.js'
```

App is at `http://localhost:8080` (NOT `:4000`).

Feature PRs target `develop` (promoted to `main` separately) — not `main`. Use atomic conventional commits (`refactor(...)`, `feat(...)`, `test(...)`, `docs(...)` — one scope per commit, one-line message, no `Co-Authored-By` trailer).

## Work in progress: Katana render engine (issue #145)

The return-type view renderer is being replaced by the [Katana](https://github.com/soysudhanshu/katana)
Blade engine (`.blade.php`), Blade-only (no closure fallback). Views are migrated by
version-migrator v1.3; framework-bundled views are migrated in-repo. The main goal of this WIP is to
surface which hooks Katana still needs — see
[docs/contributing/katana-integration-findings.md](docs/contributing/katana-integration-findings.md).

- `src/Rendering/KatanaRenderer.php` — the only Katana glue; every workaround in it maps to a Katana
  change-point in the findings doc (data not forwarded to `@extends` parent, section state leaking,
  single view root, no render-from-string, PHP 8.0 gaps).
- `patches/*.patch` — minimal Katana fixes applied locally until upstreamed (katana#53). Katana is
  patched in the container's `vendor/` after `composer install` (`patch -p1` in
  `vendor/soysudhanshu/katana`).
- `src/Support/Helpers.php` — `e()` delegates to `\Blade\e()`.
