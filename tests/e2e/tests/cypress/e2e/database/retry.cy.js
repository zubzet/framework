// Drives the transient-error retry loop in src/Database/Connection.php
// through DatabaseRetryProbeController. A second connection holds an
// exclusive row lock on z_test_retry (migrations/2026-07-02_DatabaseRetryProbe.sql),
// so the framework connection's UPDATE fails with a lock-wait timeout (1205),
// a retryable error. The probe proves exec() re-attempts before surfacing it.
describe('Database connection retry (cluster resilience)', () => {
    before(() => cy.dbSeed());

    it('retries a lock-wait timeout before giving up', () => {
        // db_max_retries=2 with innodb_lock_wait_timeout=1s means 3 attempts of
        // ~1s each. A lower-bound elapsed check proves at least one retry
        // happened (a single attempt could never exceed one timeout window),
        // while staying robust against CI timing jitter.
        cy.request({ url: '/DatabaseRetryProbe/lockWaitRetry', timeout: 20000 }).then((res) => {
            expect(res.body.maxRetries, 'retries are enabled').to.eq(2);
            expect(res.body.errored, 'contended query ultimately fails').to.eq(true);
            expect(res.body.elapsedMs, 'more than one attempt was made').to.be.greaterThan(1800);
        });
    });
});
