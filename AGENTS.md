# Agents Guide

This file is a discoverability shortcut for AI coding agents (Claude Code, Cursor, Codex, Aider, …). The canonical guide lives in the docs:

→ **[docs/contributing/agents/working-with-agents.md](docs/contributing/agents/working-with-agents.md)** — repo layout, bootstrap order, testing, commit conventions, working style.

For Git workflow and commit conventions, see **[docs/contributing/how-to-contribute.md](docs/contributing/how-to-contribute.md)**.

## Quickstart

```bash
# Bring up the dockerized e2e stack
cd tests/e2e && npm run start

# Run the full e2e suite (~3 min, 300+ tests)
env -u ELECTRON_RUN_AS_NODE npx cypress run --project tests

# Run one spec
env -u ELECTRON_RUN_AS_NODE npx cypress run --project tests \
  --spec 'tests/cypress/e2e/core/<name>.cy.js'
```

App is at `http://localhost:8080` (NOT `:4000`).

PRs target `main`. Use atomic conventional commits (`refactor(...)`, `feat(...)`, `test(...)`, `docs(...)` — one scope per commit, one-line message, no `Co-Authored-By` trailer).
