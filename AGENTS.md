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

## Render engine: Katana (Blade)

The view renderer is the [Katana](https://github.com/katanaphp/blade) Blade engine (`.blade.php`),
Blade only with no closure fallback. Legacy `return[...]` views are migrated to Blade by
version-migrator v1.3; framework-bundled views ship migrated in-repo.

- `src/Rendering/Katana/Engine.php` is the only Katana glue: an ordered userspace-then-framework
  finder chain, a fresh `Blade` per render, and the framework essentials registered under the
  `zubzet::` component namespace. `src/Rendering/Katana/Hooks.php` binds `@auth` / `@guest`.
- `src/Support/Helpers.php` delegates `e()` to `\Blade\e()`.
- See [Views](docs/core-features/views.md) for the templating reference and
  [Working With Agents](docs/contributing/agents/working-with-agents.md#render-engine-katana) for the
  adapter internals.

## Database cluster resilience (issue #80)

`Connection::exec()` in `src/Database/Connection.php` recovers two failure
classes before surfacing them, sharing the `db_max_retries` budget (default
`3`, `0` disables):

- **Transient contention** (deadlock `1213`, lock-wait timeout `1205`, Galera
  serialization `40001`): the server already discarded the statement; it is
  re-prepared and re-run on the kept connection after a randomized 10-50 ms
  backoff.
- **Connection loss** (`1047`, `2002`, `2003`, `2006`, `2013`: a node died,
  the mesh moved the endpoint, or a Galera node refuses service while
  desynced as an SST/IST donor): reconnect through the configured endpoint,
  re-prepare, re-run, with attempt-scaled backoff (400 ms steps, capped at
  2 s) spanning a realistic failover window. Documented caveat: if a write
  was applied but its acknowledgment was lost, the re-run applies it again;
  every `exec()` is auto-committed, so the exposure is one statement. For
  `1047` the refusing node never executed the statement, so that re-run is
  always safe; with `wsrep_sync_wait` enabled, donors answer reads with
  `1047` during every node rejoin, making this classification essential.
  `connect()` bounds each attempt with a 5 s connect timeout and normalizes
  the PHP 8.0 false-return into the same exception the 8.1+ path throws.
  The recovery loop lives in `execWithRecovery()` with a single
  classification point; `attemptStatement()` makes one self-contained
  attempt and folds PHP 8.0 false-returns and PHP 8.1+ exceptions into one
  failure descriptor, reading the SQLSTATE off the handle that recorded it.

Transport settings live in `src/Database/Endpoint.php`, a request-wide
singleton (`Endpoint::get()`) shared by the runtime connection and the
migrations' Doctrine DBAL connection (the DBAL parameter mapping stays in
the DbalConnection trait): `dbhost`, `dbport` and `db_ssl`. mysqli matches the **whole** `dbhost` string against the
TLS certificate, so with `db_ssl` a `host:port` value is rejected with an
error pointing to `dbport`. **mysqli verifies nothing unless it is handed
a certificate authority and never falls back to the system trust store on
its own** (measured on PHP 8.0 mysqlnd), so `db_ssl = true` resolves that
store via `openssl_get_cert_locations()` and throws rather than
downgrading when none is found; there is deliberately no CA setting, a
private authority goes into the system store or `openssl.cafile`.
`db_persistent` prefixes the host
with `p:`; safe next to the retry logic (mysqli resets reused connections
and replaces dead ones; the failover spec passes with it on), opt-in
because a worker then sticks to the node it first reached. **The pool key
excludes the transport** (measured), so changing `db_ssl` needs a worker
reload; the e2e suite runs with `db_persistent = true`, and the TLS cases
in `ConnectionProbeController` force it off for their own connections for
exactly this reason.

Operational contract, learned the hard way: a MySQL client waiting for the
server greeting has **no read timeout**, so the database endpoint (mesh,
haproxy) must close sessions to dead backends quickly; the e2e proxy config
(`tests/e2e/packaging/docker/haproxy-database.cfg`) shows the fail-fast
settings. Caller-managed raw-SQL transactions are not retried at statement
level and stay the caller's responsibility; `executeMultiQuery()` has no
retries on purpose.

**The e2e suite runs on a real three-node Galera cluster** (MariaDB 11.8,
settings mirroring the production nixops definition) behind an haproxy that
plays the production service mesh: single endpoint `database:3306`,
round-robin across all nodes (multi-writer, like production), sessions shut
on node death. `wsrep_sync_wait = 1` is an e2e-only addition for
deterministic cross-node read-your-writes; production does not set it.
`galera1` bootstraps only on first initialization and rejoins on restart
(see the compose command wrapper); the image's `healthcheck.sh` is unusable
on joiners because SST replaces the datadir, so the healthcheck asserts
`wsrep_ready` instead. Every existing spec therefore exercises Galera
implicitly; dedicated coverage lives in
`tests/cypress/e2e/database/{cluster,failover,retry}.cy.js` (membership,
replication to every node, a request surviving a mid-flight kill of the
exact node it landed on, and the two lock-conflict semantics: same-node
lock-wait retries and cross-node certification, where Galera row locks do
not span nodes).

The nodes also serve TLS, with committed test-only certificates
(`packaging/docker/tls/`, ~100-year validity; the certificate covers the
proxy endpoint and every node name, because the client dials the endpoint
while a node answers), and the application image trusts the suite's CA in
its system store (Dockerfile.apache-local), mirroring production where
the store knows the public issuer. The cluster only *offers* TLS, so the
suite keeps covering the plaintext default; the TLS cases in
`tests/cypress/e2e/database/connection.cy.js` flip `require_secure_transport`
on for their own duration to exercise the production rule, including
migrations, and the wrong-host negative case is what proves verification
is real.

**Open decisions for review:** retries default-on (current) vs opt-in;
retry logging/metrics (skipped so far to avoid the slow-query logger's
checkpoint reentrancy); multi-host `dbhost` lists as a later, additive
feature for infrastructures without a mesh. Unit tests for `isRetryable()`
are tracked in issue #180.
