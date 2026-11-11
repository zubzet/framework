describe('CSRF protection', () => {
    const CROSS = 'cross site request';
    const BLOCKED = 'blocked origin';

    const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];
    const CHECKED_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];
    const METHODS = [...SAFE_METHODS, ...CHECKED_METHODS];

    // Sends a real request through the real check
    function send(method, url, headers = {}) {
        return cy.request({ method, url, headers, failOnStatusCode: false });
    }

    // A rejected request gets the 403 page of the error controller, a HEAD without its body
    function expectRejected(res, reason) {
        expect(res.status, reason).to.eq(403);
        if(res.body) expect(res.body).to.include('Page not for you');
    }

    // Real requests through the real check, the e2e app runs at localhost:8080 under the default settings
    describe('the check on real requests', () => {
        const crossSite = { 'Sec-Fetch-Site': 'cross-site', Origin: 'https://evil.test' };

        it('rejects a request from another site, for every checked method', () => {
            CHECKED_METHODS.forEach((method) => {
                send(method, '/CsrfProbe/plain', crossSite).then((res) => expectRejected(res, method));
            });
        });

        it('passes GET, HEAD and OPTIONS from another site', () => {
            SAFE_METHODS.forEach((method) => {
                send(method, '/CsrfProbe/plain', crossSite).its('status').should('eq', 200);
            });
        });

        it('passes a request from no page, for every method', () => {
            METHODS.forEach((method) => {
                send(method, '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'none' }).its('status').should('eq', 200);
            });
        });

        it('passes a request without the browser headers, for every method', () => {
            METHODS.forEach((method) => {
                send(method, '/CsrfProbe/plain').its('status').should('eq', 200);
            });
        });

        it('passes a link from another site', () => {
            send('GET', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'cross-site' }).its('status').should('eq', 200);
            send('GET', '/', { 'Sec-Fetch-Site': 'cross-site' }).its('status').should('eq', 200);
        });

        it('passes a request from the same origin', () => {
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-origin', Origin: 'http://localhost:8080' }).its('status').should('eq', 200);
        });

        it('passes a browser without Sec-Fetch-Site unless it hides its Origin', () => {
            send('POST', '/CsrfProbe/plain', { Origin: 'https://evil.test' }).its('status').should('eq', 200);
            send('POST', '/CsrfProbe/plain', { Origin: 'null' }).then((res) => expectRejected(res, CROSS));
        });

        it('rejects a form that hides its Origin, unless it comes from the same origin', () => {
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'cross-site', Origin: 'null' }).then((res) => expectRejected(res, CROSS));
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site', Origin: 'null' }).then((res) => expectRejected(res, CROSS));
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-origin', Origin: 'null' }).its('status').should('eq', 200);
        });

        it('checks an explicit route', () => {
            send('POST', '/csrf-probe-route', crossSite).then((res) => expectRejected(res, CROSS));
            send('GET', '/csrf-probe-route', { 'Sec-Fetch-Site': 'same-origin' }).its('status').should('eq', 200);
        });

        it('checks the framework routes', () => {
            send('POST', '/_zubzet/health', crossSite).then((res) => expectRejected(res, CROSS));
            send('GET', '/_zubzet/health', crossSite).its('status').should('eq', 200);
            send('POST', '/_zubzet/profile/clear-sessions', crossSite).then((res) => expectRejected(res, CROSS));
        });

        it('rejects before a missing page answers 404', () => {
            send('POST', '/CsrfProbe/missing-action-and-page', crossSite).then((res) => expectRejected(res, CROSS));
            send('POST', '/NoSuchControllerAnywhere', crossSite).then((res) => expectRejected(res, CROSS));
            send('POST', '/NoSuchControllerAnywhere', { 'Sec-Fetch-Site': 'none' }).its('status').should('eq', 404);
        });

        it('rejects before checkPermission() shows the login', () => {
            send('POST', '/CsrfProbe/guarded', crossSite).then((res) => {
                expectRejected(res, CROSS);
                expect(res.body).not.to.include('data-test="btn-login"');
            });
            send('GET', '/CsrfProbe/guarded', { 'Sec-Fetch-Site': 'same-origin' }).then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body).to.include('data-test="btn-login"');
            });
        });

        it('passes a reroute of a request that passed', () => {
            send('GET', '/CsrfProbe/reroute', { 'Sec-Fetch-Site': 'same-origin' }).then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body).to.include('"success"');
            });
            send('POST', '/CsrfProbe/reroute', crossSite).then((res) => expectRejected(res, CROSS));
        });

        it('checks an action that opts GET back in, by default only GET', () => {
            send('GET', '/CsrfProbe/optIn', crossSite).then((res) => expectRejected(res, CROSS));
            send('GET', '/CsrfProbe/optIn', { 'Sec-Fetch-Site': 'same-site' }).then((res) => expectRejected(res, CROSS));
            send('HEAD', '/CsrfProbe/optIn', crossSite).its('status').should('eq', 200);
            send('OPTIONS', '/CsrfProbe/optIn', crossSite).its('status').should('eq', 200);
            send('POST', '/CsrfProbe/optIn', crossSite).then((res) => expectRejected(res, CROSS));
            send('GET', '/CsrfProbe/optIn', { 'Sec-Fetch-Site': 'same-origin' }).its('status').should('eq', 200);
            send('GET', '/CsrfProbe/optIn', { 'Sec-Fetch-Site': 'none' }).its('status').should('eq', 200);
            send('GET', '/CsrfProbe/optIn').its('status').should('eq', 200);
        });

        it('checks the methods an action opts back in, written in any case', () => {
            send('GET', '/CsrfProbe/optInHead', crossSite).then((res) => expectRejected(res, CROSS));
            send('HEAD', '/CsrfProbe/optInHead', crossSite).then((res) => expectRejected(res, CROSS));
            send('OPTIONS', '/CsrfProbe/optInHead', crossSite).its('status').should('eq', 200);
            send('HEAD', '/CsrfProbe/optInHead', { 'Sec-Fetch-Site': 'same-origin' }).its('status').should('eq', 200);
        });

        it('passes pages a browser loads itself', () => {
            cy.visit('/CsrfProbe/plain');
            cy.contains('success');
        });
    });

    // The framework's logout is a GET that changes state, so it opts GET and HEAD back in
    describe('the logout', () => {
        // A fresh session per case, so the fixture tokens of the other specs stay valid
        function login() {
            cy.fixture('logins.json').then(({ customer }) => {
                cy.request({ method: 'POST', url: '/login', form: true, body: { name: customer.name, password: customer.password } });
            });
        }

        // The customer lacks admin.panel: logged in sees the 403 page, a guest the login
        function expectLoggedIn(loggedIn) {
            send('GET', '/CsrfProbe/guarded').its('body').should('include', loggedIn ? 'Page not for you' : 'data-test="btn-login"');
        }

        it('logs out from the own pages, a typed URL and without a browser', () => {
            [{ 'Sec-Fetch-Site': 'same-origin' }, { 'Sec-Fetch-Site': 'none' }, {}].forEach((headers) => {
                login();
                expectLoggedIn(true);
                send('GET', '/login/logout', headers);
                expectLoggedIn(false);
            });
        });

        it('rejects a logout from another site or subdomain, by GET and by HEAD', () => {
            login();
            [
                { 'Sec-Fetch-Site': 'cross-site' },
                { 'Sec-Fetch-Site': 'same-site' },
                // A subdomain with no-referrer
                { 'Sec-Fetch-Site': 'same-site', Origin: 'null' },
            ].forEach((headers) => {
                ['GET', 'HEAD'].forEach((method) => {
                    send(method, '/login/logout', headers).then((res) => expectRejected(res, `${method} ${JSON.stringify(headers)}`));
                });
            });
            expectLoggedIn(true);
        });
    });

    describe('the check under changed settings', () => {
        before(() => cy.saveConfigBackup());

        after(() => cy.restoreConfigBackup());

        afterEach(() => {
            cy.setConfigSetting('csrf_allow', 'same-origin');
            cy.setConfigSetting('csrf_trusted_origins', '');
            cy.setConfigSetting('csrf_blocked_origins', '');
        });

        it('passes a preflight, the request and a link from a trusted origin', () => {
            cy.setConfigSetting('csrf_trusted_origins', 'https://partner.test');

            const preflight = (Origin) => send('OPTIONS', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'cross-site', Origin, 'Access-Control-Request-Method': 'PUT' });
            preflight('https://partner.test').its('status').should('eq', 200);
            send('PUT', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'cross-site', Origin: 'https://partner.test' }).its('status').should('eq', 200);
            send('GET', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'cross-site', Origin: 'https://partner.test' }).its('status').should('eq', 200);
            // The preflight is an OPTIONS and passes, the request it announces is checked
            preflight('https://other.test').its('status').should('eq', 200);
            send('PUT', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'cross-site', Origin: 'https://other.test' }).then((res) => expectRejected(res, CROSS));
        });

        it('passes a sibling subdomain under same-site until it is blocked', () => {
            cy.setConfigSetting('csrf_allow', 'same-site');
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site', Origin: 'http://bucket.localhost:8080' }).its('status').should('eq', 200);
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site', Origin: 'null' }).then((res) => expectRejected(res, CROSS));

            cy.setConfigSetting('csrf_blocked_origins', 'http://bucket.localhost:8080');
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site', Origin: 'http://bucket.localhost:8080' }).then((res) => expectRejected(res, BLOCKED));
            // The bucket hiding its Origin does not get past the blocklist
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site', Origin: 'null' }).then((res) => expectRejected(res, BLOCKED));
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site' }).then((res) => expectRejected(res, BLOCKED));
            // Its links still pass
            send('GET', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site' }).its('status').should('eq', 200);
            send('POST', '/CsrfProbe/plain', { Origin: 'http://bucket.localhost:8080' }).then((res) => expectRejected(res, BLOCKED));
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site', Origin: 'http://www.localhost:8080' }).its('status').should('eq', 200);
        });

        it('throws for an unknown csrf_allow, but only for a checked request that carries a browser header', () => {
            cy.setConfigSetting('csrf_allow', 'same_site');
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site' }).its('status').should('eq', 500);
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site', Origin: 'null' }).its('status').should('eq', 500);
            send('POST', '/CsrfProbe/plain').its('status').should('eq', 200);
            send('GET', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site' }).its('status').should('eq', 200);
        });

        it('passes every site under any, a hidden Origin included', () => {
            cy.setConfigSetting('csrf_allow', 'any');
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'cross-site', Origin: 'https://evil.test' }).its('status').should('eq', 200);
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'cross-site', Origin: 'null' }).its('status').should('eq', 200);
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'cross-site' }).its('status').should('eq', 200);
        });
    });
});
