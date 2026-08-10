# The Database Connection

## What is this?
Every model query runs through one `Connection` object, reachable as `db()`.
It is opened lazily on the first query, kept alive across the request, and
re-opened by itself when the server drops it. This page covers the settings
that shape it. All of them live in `z_config/z_settings.ini` (see
[Booter Settings](configuration.md)) and all of them are optional apart from
the credentials.

## Endpoint and credentials
```ini
dbhost = database.example.com
dbport = 3306
dbname = app
dbusername = app
dbpassword = secret
```

`dbhost` may also carry the port (`host:3306`), except with `db_ssl`
turned on: the server certificate is matched against `dbhost` as a whole,
and no certificate names a port, so with TLS the port belongs in `dbport`
and a combined value is rejected with an error saying exactly that.

Migrations connect separately (through Doctrine DBAL) but read the same
settings, so there is nothing to configure twice.

## Timeouts and retries
```ini
db_connection_timeout = 900
db_max_retries = 3
```

`db_connection_timeout` is the number of seconds after which an idle
connection is checked before being reused.

`db_max_retries` is how many extra attempts a statement gets when it fails
for a reason that is worth retrying: a deadlock, a lock-wait timeout, a
Galera certification conflict, or a dropped connection (which is recovered
by reconnecting through the endpoint and re-running the statement). Set it
to `0` to disable retries entirely.

!!! warning "One statement may be applied twice"
    If a write reached the server but the acknowledgment was lost with the
    connection, the retry applies it again. Every `exec()` auto-commits, so
    the exposure is a single statement.

## Encrypted transport (TLS)
Clusters that reject unencrypted clients (MariaDB's
`require_secure_transport`, or anything reachable over a public network)
need this turned on:

```ini
db_ssl = true
```

That is the whole configuration: the server certificate is verified
against the trust store your system already uses, which knows every
publicly issued certificate (for example Let's Encrypt). A private or
self-signed authority is added to that same store (on Debian: drop it
into `/usr/local/share/ca-certificates/` and run
`update-ca-certificates`), or handed to PHP via `openssl.cafile`.

The certificate is always verified. That is worth a sentence, because
mysqli itself is lax here: given no authority it encrypts without
verifying, and it never consults the system trust store on its own. The
framework resolves that store for you so that `db_ssl = true` means
verified, and raises an error rather than connecting unverified when no
trust store can be found.

## Persistent connections
```ini
db_persistent = true
```

A persistent connection is kept open by the PHP worker after the request
ends and handed to the next request, which skips the TCP, TLS and
authentication handshake (measured against the e2e cluster: about 2.2 ms
per encrypted connect versus 0.2 ms per reuse). It is safe to reuse: mysqli
resets the session before handing it over and replaces a connection that no
longer answers, so a node that died does not poison the pool.

It is off by default because of placement: a worker stays on whichever node
it first reached, so a load-balanced cluster only rebalances as workers
recycle. Turn it on where the handshake actually costs you, an encrypted or
remote database being the typical case.

!!! warning "Restart your PHP workers after changing `db_ssl`"
    mysqli pools connections by host, port, user, password and database,
    not by transport, so a worker still holding a plaintext connection
    keeps using it after you turn `db_ssl` on. Reload PHP-FPM (or your web
    server) when you change the transport.
