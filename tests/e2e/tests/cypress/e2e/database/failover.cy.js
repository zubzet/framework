// Kills the primary Galera node while a request is in flight and proves the
// framework's reconnect-retry (Connection::exec) carries the request through
// the failover: the mesh (haproxy) promotes a backup node, the dead
// connection is re-established, and the interrupted statement re-runs.
//
// Destructive by design: the cluster is degraded during this file and fully
// restored (node restarted, membership back to three, Synced) in after(),
// so spec order does not matter to the rest of the suite.
describe('Galera failover', () => {
    before(() => {
        cy.dbSeed();
        cy.saveConfigBackup();
        // The proxy needs a moment to mark the dead node down; a slightly
        // higher retry budget lets the request ride the whole window.
        cy.setConfigSetting('db_max_retries', '5');
    });

    after(() => {
        cy.restoreConfigBackup();

        // Bring the killed node back (whichever it was; start is a no-op on
        // running nodes) and wait until the cluster is whole again, so
        // everything after this file sees a healthy stack. Each node is asked
        // itself: the cluster size reaches three while the rejoining node still
        // receives its state transfer, and a node that is not synced answers
        // with "WSREP has not yet prepared node", which db:seed does not retry.
        cy.exec('docker start galera1 galera2 galera3', { timeout: 30000 });
        const NODES = ['galera1', 'galera2', 'galera3'];
        const waitForSynced = (retriesLeft) => {
            const states = [];
            NODES.forEach((node) => {
                cy.exec(
                    `docker exec ${node} mariadb -uroot -proot_password --silent -e ` +
                    `"SELECT CONCAT(s.VARIABLE_VALUE, '/', r.VARIABLE_VALUE) FROM information_schema.GLOBAL_STATUS s, information_schema.GLOBAL_STATUS r ` +
                    `WHERE s.VARIABLE_NAME = 'wsrep_local_state_comment' AND r.VARIABLE_NAME = 'wsrep_ready'"`,
                    { failOnNonZeroExit: false, timeout: 15000 }
                ).then(({ stdout }) => states.push(`${node}=${stdout.trim() || 'down'}`));
            });
            cy.then(() => {
                if (states.every((state) => state.endsWith('=Synced/ON'))) return;
                expect(retriesLeft, `cluster rejoin attempts left (${states.join(', ')})`).to.be.greaterThan(0);
                cy.wait(2000).then(() => waitForSynced(retriesLeft - 1));
            });
        };
        waitForSynced(90);
    });

    it('a request survives its database node dying mid-flight', () => {
        // slowread: query, 3s sleep, query. Connections round-robin, so the
        // probe publishes which node it landed on (../galera-target-node.txt
        // on the shared mount); the subshell waits for that file and kills
        // exactly that node while the request sleeps. The second query then
        // hits a dead connection and must be recovered transparently.
        // docker kill, not stop: a crash kills the port instantly, which is
        // the failure being modeled (a graceful stop drains for seconds).
        // curl instead of cy.request because the kill has to happen while the
        // request is in flight; the base URL still comes from the Cypress
        // config instead of being hardcoded.
        const TARGET_FILE = '../galera-target-node.txt';
        cy.exec(`rm -f ${TARGET_FILE}`);

        const url = `${Cypress.config('baseUrl')}/DatabaseClusterProbe/slowread`;
        const killWhenPublished =
            `(for i in $(seq 1 50); do [ -f ${TARGET_FILE} ] && break; sleep 0.1; done; ` +
            `docker kill $(cat ${TARGET_FILE})) >/dev/null 2>&1 &`;
        const cmd = `${killWhenPublished} curl -s -m 60 ${url}`;

        cy.exec(cmd, { timeout: 90000 }).then(({ stdout }) => {
            const body = JSON.parse(stdout);
            expect(body.survived, 'request completed after node death').to.eq(true);
            expect(body.rows, 'replicated data readable on the failover node').to.be.gte(0);
            expect(body.recoveredOn, 'recovery landed on a surviving node').to.not.eq(body.diedOn);
        });
    });

    it('the cluster stays available while the killed node recovers', () => {
        // The compose restart policy brings the killed node back on its own
        // and the join-aware bootstrap re-admits it, so membership may
        // already be back at three here; availability is the contract.
        cy.request('/DatabaseClusterProbe/status').then((res) => {
            expect(res.body.clusterSize, 'cluster keeps quorum').to.be.within(2, 3);
            expect(res.body.state).to.eq('Synced');
        });
    });
});
