describe('Health Endpoint', () => {

    before(() => cy.saveConfigBackup());

    after(() => cy.restoreConfigBackup());

    // "database" is the cluster endpoint (haproxy in front of Galera), so
    // readiness is probed the way a client experiences it: through the
    // health endpoint itself.
    const waitForDatabase = (attempt = 0) => {
        cy.request({
            method: 'GET',
            url: '/_zubzet/health',
            failOnStatusCode: false,
        }).then((res) => {
            if(res.status === 200) return;
            expect(attempt, 'database did not come back in time').to.be.lessThan(60);
            cy.wait(1000);
            waitForDatabase(attempt + 1);
        });
    };

    describe('Enabled', () => {

        before(() => cy.setConfigSetting('health_endpoint_enabled', 'true'));

        it('should report a healthy application as JSON', () => {
            cy.request({
                method: 'GET',
                url: '/_zubzet/health',
                failOnStatusCode: false,
            }).then((res) => {
                expect(res.status).to.equal(200);
                expect(res.body).to.deep.equal({ status: 'healthy' });
            });
        });
    });

    describe('Database Unavailable', () => {

        before(() => {
            cy.setConfigSetting('health_endpoint_enabled', 'true');
            cy.exec('docker stop database');
        });

        after(() => {
            cy.exec('docker start database');
            waitForDatabase();
        });

        it('should report unhealthy without exposing error details', () => {
            cy.request({
                method: 'GET',
                url: '/_zubzet/health',
                failOnStatusCode: false,
            }).then((res) => {
                expect(res.status).to.equal(503);
                expect(res.body).to.deep.equal({ status: 'unhealthy' });
            });
        });

        it('should recover once the database is back', () => {
            cy.exec('docker start database');
            waitForDatabase();

            cy.request({
                method: 'GET',
                url: '/_zubzet/health',
            }).then((res) => {
                expect(res.status).to.equal(200);
                expect(res.body).to.deep.equal({ status: 'healthy' });
            });
        });

    });

    describe('Disabled', () => {

        before(() => cy.setConfigSetting('health_endpoint_enabled', 'false'));

        after(() => cy.setConfigSetting('health_endpoint_enabled', 'true'));

        it('should return 404 when turned off', () => {
            cy.request({
                method: 'GET',
                url: '/_zubzet/health',
                failOnStatusCode: false,
            }).then((res) => {
                expect(res.status).to.equal(404);
            });
        });
    });

});
