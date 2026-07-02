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

## Work in progress: database cluster retry (issue #80)

`Connection::exec()` in `src/Database/Connection.php` retries queries that
fail with transient, cluster-related errors (deadlock `1213`, lock-wait
timeout `1205`, Galera serialization `40001`) before surfacing them. Notes
for whoever continues this WIP:

- The retry loop wraps only `execute()`, re-running the still-valid prepared
  statement; `prepare()` failures are deterministic and never retried. Both
  the PHP 8.1+ exception path and the 8.0 `false`-return path funnel through
  `shouldRetry()` / `isRetryable()`.
- Tunable via the `db_max_retries` config key (default `3`, `0` disables).
  Backoff is a randomized 10-50 ms sleep between attempts.
- Safe because every `exec()` is auto-committed individually. This assumption
  breaks for caller-managed transactions issued as raw SQL; those are not
  retried at the statement level and stay the caller's responsibility.
  `executeMultiQuery()` is intentionally left without retries.
- **Open decisions for review:** whether retries should default on (current)
  or opt-in; and adding retry logging/metrics (skipped for now to avoid the
  slow-query logger's checkpoint reentrancy).
- **Test coverage:** e2e covers only the lock-wait path via a single-node
  lock-contention probe (`DatabaseRetryProbeController` +
  `tests/cypress/e2e/database/retry.cy.js`). The Galera `40001` path and the
  retry-then-succeed branch need a multi-node/concurrent harness and are not
  yet covered. Unit tests for `isRetryable()` are tracked in issue #180.
