describe('CSRF protection', () => {
    const CONFIG_PATH = '../z_config/z_settings.ini';

    const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];
    const CHECKED_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];
    const METHODS = [...SAFE_METHODS, ...CHECKED_METHODS];

    const crossSite = { 'Sec-Fetch-Site': 'cross-site', Origin: 'https://evil.test' };

    // Sends a real request through the real check
    function send(method, url, headers = {}) {
        return cy.request({ method, url, headers, failOnStatusCode: false });
    }

    // A rejected request gets the 403 page of the error controller, a HEAD without its body
    function expectRejected(res, reason) {
        expect(res.status, reason).to.eq(403);
        if(res.body) expect(res.body).to.include('Page not for you');
    }

    // Writes the csrf settings an application would add. The e2e app itself sets none of them
    function configure(settings) {
        cy.readFile(CONFIG_PATH, 'utf8').then((content) => {
            const kept = content.split('\n').filter((line) => !line.startsWith('csrf_'));
            const added = Object.entries(settings).map(([key, value]) => `${key} = ${value}`);
            cy.writeFile(CONFIG_PATH, [...kept, ...added].join('\n'));
        });
    }

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

    before(() => cy.saveConfigBackup());

    after(() => cy.restoreConfigBackup());

    // On the 1.2 line the check is opt-in, an application that sets nothing behaves as before
    describe('without a setting', () => {
        before(() => configure({}));

        it('passes a request from another site, for every method', () => {
            METHODS.forEach((method) => {
                send(method, '/CsrfProbe/plain', crossSite).its('status').should('eq', 200);
            });
        });

        it('passes a request that hides its Origin', () => {
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'cross-site', Origin: 'null' }).its('status').should('eq', 200);
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site' }).its('status').should('eq', 200);
            send('POST', '/CsrfProbe/plain', { Origin: 'null' }).its('status').should('eq', 200);
        });

        it('passes an action that opts GET back in', () => {
            send('GET', '/CsrfProbe/optIn', crossSite).its('status').should('eq', 200);
            send('HEAD', '/CsrfProbe/optInHead', crossSite).its('status').should('eq', 200);
        });

        it('still logs out from a link on another site', () => {
            login();
            expectLoggedIn(true);
            send('GET', '/login/logout', { 'Sec-Fetch-Site': 'cross-site' });
            expectLoggedIn(false);
        });

        it('only rejects an origin that is blocked', () => {
            configure({ csrf_blocked_origins: 'https://evil.test' });
            send('POST', '/CsrfProbe/plain', crossSite).then((res) => expectRejected(res, 'blocked origin'));
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'cross-site', Origin: 'https://partner.test' }).its('status').should('eq', 200);
            configure({});
        });
    });

    describe('switched on with csrf_allow = same-origin', () => {
        beforeEach(() => configure({ csrf_allow: 'same-origin' }));

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

        it('passes a request from no page or without the browser headers, for every method', () => {
            METHODS.forEach((method) => {
                send(method, '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'none' }).its('status').should('eq', 200);
                send(method, '/CsrfProbe/plain').its('status').should('eq', 200);
            });
        });

        it('passes a request from the same origin', () => {
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-origin', Origin: 'http://localhost:8080' }).its('status').should('eq', 200);
        });

        it('rejects a request from a subdomain', () => {
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site', Origin: 'http://www.localhost:8080' }).then((res) => expectRejected(res, 'same-site'));
        });

        it('passes a browser without Sec-Fetch-Site unless it hides its Origin', () => {
            send('POST', '/CsrfProbe/plain', { Origin: 'https://evil.test' }).its('status').should('eq', 200);
            send('POST', '/CsrfProbe/plain', { Origin: 'null' }).then((res) => expectRejected(res, 'hidden origin'));
        });

        it('rejects a form that hides its Origin, unless it comes from the same origin', () => {
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'cross-site', Origin: 'null' }).then((res) => expectRejected(res, 'cross-site'));
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-origin', Origin: 'null' }).its('status').should('eq', 200);
        });

        it('rejects before a missing page answers 404', () => {
            send('POST', '/NoSuchControllerAnywhere', crossSite).then((res) => expectRejected(res, 'missing page'));
            send('POST', '/NoSuchControllerAnywhere', { 'Sec-Fetch-Site': 'none' }).its('status').should('eq', 404);
        });

        it('rejects before checkPermission() shows the login', () => {
            send('POST', '/CsrfProbe/guarded', crossSite).then((res) => {
                expectRejected(res, 'guarded');
                expect(res.body).not.to.include('data-test="btn-login"');
            });
        });

        it('passes a reroute of a request that passed', () => {
            send('GET', '/CsrfProbe/reroute', { 'Sec-Fetch-Site': 'same-origin' }).then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body).to.include('"success"');
            });
            send('POST', '/CsrfProbe/reroute', crossSite).then((res) => expectRejected(res, 'reroute'));
        });

        it('checks an action that opts GET back in, by default only GET', () => {
            send('GET', '/CsrfProbe/optIn', crossSite).then((res) => expectRejected(res, 'GET'));
            send('HEAD', '/CsrfProbe/optIn', crossSite).its('status').should('eq', 200);
            send('GET', '/CsrfProbe/optIn', { 'Sec-Fetch-Site': 'same-origin' }).its('status').should('eq', 200);
            send('GET', '/CsrfProbe/optIn', { 'Sec-Fetch-Site': 'none' }).its('status').should('eq', 200);
            send('GET', '/CsrfProbe/optIn').its('status').should('eq', 200);
        });

        it('checks the methods an action opts back in, written in any case', () => {
            send('GET', '/CsrfProbe/optInHead', crossSite).then((res) => expectRejected(res, 'GET'));
            send('HEAD', '/CsrfProbe/optInHead', crossSite).then((res) => expectRejected(res, 'HEAD'));
            send('OPTIONS', '/CsrfProbe/optInHead', crossSite).its('status').should('eq', 200);
        });

        it('passes pages a browser loads itself', () => {
            cy.visit('/');
            cy.query('dashboard-controller').should('exist');
        });

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
            [{ 'Sec-Fetch-Site': 'cross-site' }, { 'Sec-Fetch-Site': 'same-site' }].forEach((headers) => {
                ['GET', 'HEAD'].forEach((method) => {
                    send(method, '/login/logout', headers).then((res) => expectRejected(res, `${method} ${JSON.stringify(headers)}`));
                });
            });
            expectLoggedIn(true);
        });

        it('passes the request of a trusted origin', () => {
            configure({ csrf_allow: 'same-origin', csrf_trusted_origins: 'https://partner.test' });
            send('PUT', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'cross-site', Origin: 'https://partner.test' }).its('status').should('eq', 200);
            send('PUT', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'cross-site', Origin: 'https://other.test' }).then((res) => expectRejected(res, 'not trusted'));
        });

        it('passes a sibling subdomain under same-site until it is blocked', () => {
            configure({ csrf_allow: 'same-site' });
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site', Origin: 'http://bucket.localhost:8080' }).its('status').should('eq', 200);

            configure({ csrf_allow: 'same-site', csrf_blocked_origins: 'http://bucket.localhost:8080' });
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site', Origin: 'http://bucket.localhost:8080' }).then((res) => expectRejected(res, 'blocked'));
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site', Origin: 'http://www.localhost:8080' }).its('status').should('eq', 200);
        });

        it('throws for an unknown csrf_allow, but only for a checked request that carries a browser header', () => {
            configure({ csrf_allow: 'same_site' });
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site' }).its('status').should('eq', 500);
            send('POST', '/CsrfProbe/plain').its('status').should('eq', 200);
            send('GET', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site' }).its('status').should('eq', 200);
        });
    });
});
