// Drives the transient-error retry loop in src/Database/Connection.php
// through DatabaseRetryProbeController. Galera row locks are node-local, so
// the probe holds its lock RELATIVE to the node the framework connection
// landed on, making both conflict semantics deterministic on every run:
// same-node produces a lock-wait timeout (1205) that exec() must retry,
// cross-node certifies instantly and brute-force aborts the lock holder.
describe('Database connection retry (cluster resilience)', () => {
    before(() => cy.dbSeed());

    it('retries a lock-wait timeout before giving up', () => {
        // db_max_retries=2 with innodb_lock_wait_timeout=1s means 3 attempts
        // of ~1s each. A lower-bound elapsed check proves at least one retry
        // happened while staying robust against CI timing jitter.
        cy.request({ url: '/DatabaseRetryProbe/lockWaitRetry', timeout: 20000 }).then((res) => {
            expect(res.body.lockNode, 'lock held on the framework node').to.eq(res.body.frameworkNode);
            expect(res.body.maxRetries, 'retries are enabled').to.eq(2);
            expect(res.body.errored, 'contended query ultimately fails').to.eq(true);
            expect(res.body.elapsedMs, 'more than one attempt was made').to.be.greaterThan(1800);
        });
    });

    it('a conflicting write on another node certifies instead of waiting', () => {
        cy.request({ url: '/DatabaseRetryProbe/crossNodeConflict', timeout: 20000 }).then((res) => {
            expect(res.body.lockNode, 'lock held elsewhere').to.not.eq(res.body.frameworkNode);
            expect(res.body.errored, 'cross-node update sails through').to.eq(false);
            expect(res.body.elapsedMs, 'no lock wait occurred').to.be.lessThan(900);
        });
    });
});
