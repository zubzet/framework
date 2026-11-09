describe('Health Endpoint', () => {

    before(() => cy.saveConfigBackup());

    after(() => cy.restoreConfigBackup());

    // The application cannot tell a stopped database from an endpoint that
    // refuses the connection, so the outage is a port nothing listens on.
    // Stopping the container instead changes the host's network interfaces,
    // which made the browser miss the next spec on GitHub runners.
    const CONFIG_PATH = '../z_config/z_settings.ini';
    const pointDatabaseAtClosedPort = () => {
        cy.readFile(CONFIG_PATH, 'utf8').then((content) => {
            cy.writeFile(CONFIG_PATH, `${content}\ndbport = 1\n`);
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
            pointDatabaseAtClosedPort();
        });

        after(() => cy.restoreConfigBackup());

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
            cy.restoreConfigBackup();

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
