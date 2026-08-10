// The e2e stack runs on a real three-node Galera cluster behind one endpoint
// (haproxy playing the production service mesh). These specs prove the suite
// is actually exercising that cluster: full membership, and writes through
// the application replicating to every node.
describe('Galera cluster', () => {
    before(() => cy.dbSeed());

    it('runs against a synced three-node cluster', () => {
        cy.request('/DatabaseClusterProbe/status').then((res) => {
            expect(res.body.clusterSize, 'wsrep_cluster_size').to.eq(3);
            expect(res.body.ready, 'wsrep_ready').to.eq('ON');
            expect(res.body.state, 'wsrep_local_state_comment').to.eq('Synced');
        });
    });

    it('replicates an application write to every node', () => {
        cy.request('/DatabaseClusterProbe/write').then((res) => {
            const marker = res.body.marker;
            expect(marker).to.be.a('string');

            // The write landed on a round-robin node; every node must have it.
            ['galera1', 'galera2', 'galera3'].forEach((node) => {
                cy.dbQueryNode(node, `SELECT COUNT(*) FROM app.test_cluster WHERE marker = '${marker}'`)
                    .then((count) => {
                        expect(count, `row visible on ${node}`).to.eq('1');
                    });
            });
        });
    });
});
