describe('Maintenance System', () => {

    before(() => {
        cy.dbSeed();
        cy.saveConfigBackup();
    });

    after(() => cy.restoreConfigBackup());

    describe("Mode: Disabled", () => {

        before(() => cy.setConfigSetting('maintenance_mode', 'disabled'));

        it("should allow access to the application when maintenance mode is disabled", () => {
            cy.request({
                method: 'GET',
                url: '/',
                failOnStatusCode: false
            }).then((res) => {
                expect(res.status).to.equal(200);
            });
        });

        it("should display the dashboard without maintenance page", () => {
            cy.visit("/");
            cy.query("dashboard-controller").should("exist");
        });

        it("should show 'Normal' status in the admin panel", () => {
            cy.loginAs("admin");
            cy.visit("/z/maintenance");

            cy.query("maintenance-status").should("contain", "Normal");
            cy.query("maintenance-mode").should("contain", "disabled");
        });

    });

    describe("Mode: Soft", () => {

        before(() => cy.setConfigSetting('maintenance_mode', 'soft'));

        it("should block access without bypass cookie", () => {
            cy.request({
                method: 'GET',
                url: '/',
                failOnStatusCode: false
            }).then((res) => {
                expect(res.status).to.equal(503);
            });
        });

        it("should allow access with bypass cookie", () => {
            cy.setCookie('maintenance', 'true');

            cy.request({
                method: 'GET',
                url: '/',
                failOnStatusCode: false
            }).then((res) => {
                expect(res.status).to.equal(200);
            });

            cy.clearCookie('maintenance');
        });

        it("should show 'Active' status in the admin panel when using bypass cookie", () => {
            cy.loginAs("admin");
            cy.setCookie('maintenance', 'true');

            cy.visit("/z/maintenance");

            cy.query("maintenance-status").should("contain", "Maintenance");
            cy.query("maintenance-mode").should("contain", "soft");

            cy.clearCookie('maintenance');
        });

    });

    describe("Mode: Enabled", () => {

        before(() => cy.setConfigSetting('maintenance_mode', 'enabled'));

        it("should block all access including with bypass cookie", () => {
            cy.request({
                method: 'GET',
                url: '/',
                failOnStatusCode: false
            }).then((res) => {
                expect(res.status).to.equal(503);
            });
        });

        it("should still block access even when bypass cookie is set", () => {
            cy.setCookie('maintenance', 'true');
            
            cy.request({
                method: 'GET',
                url: '/',
                failOnStatusCode: false
            }).then((res) => {
                expect(res.status).to.equal(503);
            });

            cy.clearCookie('maintenance');
        });

        it("should return 503 status code and proper headers for maintenance page", () => {
            cy.request({
                url: '/',
                failOnStatusCode: false,
                headers: {
                    'Accept': 'text/html'
                }
            }).then((response) => {
                expect(response.status).to.equal(503);
                expect(response.headers['content-type']).to.include('text/html');
                expect(response.headers['retry-after']).to.equal('300');
            });
        });

    });

    describe("Admin Panel Integration", () => {

        it("should be accessible via the admin panel menu", () => {
            cy.setConfigSetting('maintenance_mode', 'disabled');
            
            cy.loginAs("admin");
            cy.visit("/z");

            cy.query("btn-maintenance").should("exist");
        });

        it("should display maintenance button in admin layout", () => {
            cy.setConfigSetting('maintenance_mode', 'disabled');

            cy.loginAs("admin");
            cy.visit("/z");

            cy.query("btn-maintenance").click();

            cy.query("maintenance-heading").should("exist");
        });

        it("should show correct status for each mode", () => {
            const modes = [
                { mode: 'disabled', display: 'Normal' },
                { mode: 'soft', display: 'Maintenance' },
            ];

            modes.forEach(({ mode, display }) => {
                cy.setConfigSetting('maintenance_mode', mode);
                cy.loginAs("admin");
                cy.setCookie('maintenance', 'true');

                cy.visit("/z/maintenance");

                cy.query("maintenance-status").should("contain", display);
                cy.query("maintenance-mode").should("contain", mode);

                cy.clearCookie('maintenance');
            });
        });

    });

    describe("Maintenance Page", () => {

        before(() => cy.setConfigSetting('maintenance_mode', 'enabled'));

        it("should display the maintenance page with proper content", () => {
            cy.visit("/", { failOnStatusCode: false });

            cy.query("maintenance-page").should("exist").and("contain", "currently undergoing maintenance");
        });

        it("should return proper HTTP headers", () => {
            cy.request({
                url: "/",
                failOnStatusCode: false,
                headers: {
                    'Accept': 'text/html'
                }
            }).then((response) => {
                expect(response.status).to.equal(503);

                expect(response.headers['content-type']).to.include('text/html');
                expect(response.headers['content-type']).to.include('charset=UTF-8');
                expect(response.headers['retry-after']).to.equal('300');
            });
        });

    });

    describe("Mode Transitions", () => {

        it("should transition from disabled to soft mode correctly", () => {
            cy.setConfigSetting('maintenance_mode', 'disabled');
            cy.request({
                method: 'GET',
                url: '/',
                failOnStatusCode: false
            }).then((res) => {
                expect(res.status).to.equal(200);
            });

            cy.setConfigSetting('maintenance_mode', 'soft');
            cy.request({
                method: 'GET',
                url: '/',
                failOnStatusCode: false
            }).then((res) => {
                expect(res.status).to.equal(503);
            });

            cy.clearCookie('maintenance');
        });

        it("should transition from soft to enabled mode correctly", () => {
            cy.setConfigSetting('maintenance_mode', 'soft');
            cy.setCookie('maintenance', 'true');
            cy.request({
                method: 'GET',
                url: '/',
                failOnStatusCode: false
            }).then((res) => {
                expect(res.status).to.equal(200);
            });

            cy.setConfigSetting('maintenance_mode', 'enabled');
            cy.request({
                method: 'GET',
                url: '/',
                failOnStatusCode: false
            }).then((res) => {
                expect(res.status).to.equal(503);
            });

            cy.clearCookie('maintenance');
        });

        it("should transition from enabled to disabled mode correctly", () => {
            cy.setConfigSetting('maintenance_mode', 'enabled');
            cy.request({
                method: 'GET',
                url: '/',
                failOnStatusCode: false
            }).then((res) => {
                expect(res.status).to.equal(503);
            });

            cy.setConfigSetting('maintenance_mode', 'disabled');
            cy.request({
                method: 'GET',
                url: '/',
                failOnStatusCode: false
            }).then((res) => {
                expect(res.status).to.equal(200);
            });
        });

    });

    describe("Mode Parsing", () => {

        it("should accept uppercase values via strtolower", () => {
            cy.setConfigSetting('maintenance_mode', 'ENABLED');

            cy.request({ url: '/', failOnStatusCode: false }).then((res) => {
                expect(res.status).to.equal(503);
            });
        });

        it("should fall through to disabled when value is unknown", () => {
            cy.setConfigSetting('maintenance_mode', 'badvalue');

            cy.request({ url: '/', failOnStatusCode: false }).then((res) => {
                expect(res.status).to.equal(200);
            });
        });

    });

    describe("Mode: Full", () => {

        before(() => cy.setConfigSetting('maintenance_mode', 'full'));

        it("should block HTTP with the styled template", () => {
            cy.request({ url: '/', failOnStatusCode: false }).then((res) => {
                expect(res.status).to.equal(503);
                expect(res.body).to.include('currently undergoing maintenance');
            });
        });

        it("should still emit the Retry-After header", () => {
            cy.request({ url: '/', failOnStatusCode: false }).then((res) => {
                expect(res.headers['retry-after']).to.equal('300');
            });
        });

    });

    describe("CLI Bypass", () => {

        afterEach(() => cy.setConfigSetting('maintenance_mode', 'disabled'));

        it("should allow CLI commands when mode is enabled", () => {
            cy.setConfigSetting('maintenance_mode', 'enabled');

            cy.exec('docker exec application php index.php info:startup', {
                failOnNonZeroExit: false
            }).then((result) => {
                expect(result.exitCode).to.equal(0);
            });
        });

        it("should block CLI commands when mode is full", () => {
            cy.setConfigSetting('maintenance_mode', 'full');

            cy.exec('docker exec application php index.php info:startup', {
                failOnNonZeroExit: false
            }).then((result) => {
                expect(result.exitCode).to.not.equal(0);
                expect(result.stderr).to.include('Service Unavailable');
            });
        });

    });

    describe("Template Loading", () => {

        const OVERRIDE_PATH = '../app/Views/maintenance.html';

        before(() => cy.setConfigSetting('maintenance_mode', 'enabled'));

        afterEach(() => cy.exec(`rm -f ${OVERRIDE_PATH}`, { failOnNonZeroExit: false }));

        it("should reject project templates that contain <?php and fall through to the framework default", () => {
            cy.writeFile(OVERRIDE_PATH, '<?php echo "EVIL"; ?>');

            cy.request({ url: '/', failOnStatusCode: false }).then((res) => {
                expect(res.status).to.equal(503);
                expect(res.body).to.not.include('EVIL');
                expect(res.body).to.include('currently undergoing maintenance');
            });
        });

        it("should fall through to the framework default when the override is empty", () => {
            cy.writeFile(OVERRIDE_PATH, '');

            cy.request({ url: '/', failOnStatusCode: false }).then((res) => {
                expect(res.status).to.equal(503);
                expect(res.body).to.include('currently undergoing maintenance');
            });
        });

    });

    describe("Cookie Bypass Mechanism", () => {

        before(() => cy.setConfigSetting('maintenance_mode', 'soft'));

        it("should not bypass maintenance with wrong cookie name", () => {
            cy.setCookie('wrong_key', 'true');

            cy.request({
                method: 'GET',
                url: '/',
                failOnStatusCode: false
            }).then((res) => {
                expect(res.status).to.equal(503);
            });

            cy.clearCookie('wrong_key');
        });

        it("should bypass maintenance with correct cookie name regardless of value", () => {
            cy.setCookie('maintenance', 'any_value');

            cy.request({
                method: 'GET',
                url: '/',
                failOnStatusCode: false
            }).then((res) => {
                expect(res.status).to.equal(200);
            });

            cy.clearCookie('maintenance');
        });

        it("should bypass maintenance for all routes when cookie is present", () => {
            const routes = ['/', '/admin/loginas', '/z'];

            cy.setCookie('maintenance', 'true');

            routes.forEach((route) => {
                cy.request({
                    method: 'GET',
                    url: route,
                    failOnStatusCode: false
                }).then((res) => {
                    expect(res.status).to.equal(200);
                });
            });

            cy.clearCookie('maintenance');
        });

        it("should block maintenance for all routes when cookie is not present", () => {
            const routes = ['/', '/admin/loginas', '/z'];

            routes.forEach((route) => {
                cy.request({
                    method: 'GET',
                    url: route,
                    failOnStatusCode: false
                }).then((res) => {
                    expect(res.status).to.equal(503);
                });
            });
        });

    });

    // Self-contained soft-mode tests with their own setup/teardown — placed
    // last so a failure here does not cascade through the rest of the spec.
    describe('Mode: Soft (self-contained)', () => {
        beforeEach(() => cy.setConfigSetting('maintenance_mode', 'soft'));
        afterEach(() => cy.setConfigSetting('maintenance_mode', 'disabled'));

        it("should allow setting bypass cookie via the admin panel", () => {
            cy.setConfigSetting('maintenance_mode', 'disabled'); // panel reachable
            cy.loginAs("admin");
            cy.clearCookie('maintenance');

            cy.visit("/z/maintenance");

            // The click fires Z.Request.action("bypass-maintenance") which
            // POSTs to the current URL. Intercept and wait for the response
            // before checking the cookie — under xdebug coverage the
            // round-trip can exceed cypress's default 4s timeout.
            cy.intercept('POST', '/z/maintenance').as('bypass');
            cy.query('btn-bypass-maintenance').should('not.be.disabled').click();
            cy.wait('@bypass', { timeout: 30000 }).its('response.statusCode').should('eq', 200);
            cy.getCookie('maintenance').should('exist');

            cy.clearCookie('maintenance');
        });

        it("should display maintenance page with retry-after header in soft mode without cookie", () => {
            cy.request({
                url: '/',
                failOnStatusCode: false,
                headers: { 'Accept': 'text/html' },
            }).then((response) => {
                expect(response.status).to.equal(503);
                expect(response.headers['retry-after']).to.equal('300');
                expect(response.body).to.include('currently undergoing maintenance');
            });
        });
    });

});
