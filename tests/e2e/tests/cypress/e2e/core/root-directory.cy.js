// The app also answers below /sub with rootDirectory = sub, see
// packaging/docker/site-000-default.conf. Every link the framework builds
// there has to keep the /sub/ prefix with a slash before the path, and has
// to lead somewhere.
describe('Root directory', () => {
    before(() => cy.dbSeed());

    function expectWorkingLink(url) {
        expect(url).to.match(/^\/sub\/[^/]/);
        cy.request(url).its('status').should('eq', 200);
    }

    function fetchLatestMailHtml() {
        return cy.request('http://localhost:3300/api/messages').then((res) => {
            const body = typeof res.body === 'string' ? JSON.parse(res.body) : res.body;
            return cy.request(`http://localhost:3300/api/messages/${body.results[0].id}/html`)
                .its('body');
        });
    }

    it('hands the root to the frontend with a trailing slash', () => {
        cy.visit('/sub/login');
        cy.window().then((win) => {
            expect(win.Z.Request.rootPath).to.eq('/sub/');
            expect(win.Z.Request.absRoot).to.eq('http://localhost:8080/sub/');
        });
    });

    // A missing slash after the root ("/subz/...") or a doubled one
    // ("/sub//z/...") in any framework built attribute
    function expectNoBrokenRootLinks(body) {
        expect(body).to.not.match(/(href|src)="\/sub(?!\/)/);
        expect(body).to.not.match(/(href|src)="\/sub\/\//);
    }

    it('builds every link of the framework pages below the root', () => {
        ['/sub/login', '/sub/login/signup', '/sub/login/forgot_password'].forEach((url) => {
            cy.request(url).its('body').then(expectNoBrokenRootLinks);
        });
        cy.request({ url: '/sub/doesnotexist', failOnStatusCode: false }).then((res) => {
            expect(res.status).to.eq(404);
            expectNoBrokenRootLinks(res.body);
            expect(res.body).to.include('<a href="/sub/">Take me back');
        });
    });

    it('loads every script and stylesheet of the page head, the debug bar included', () => {
        cy.request('/sub/login').its('body').then((body) => {
            const doc = new DOMParser().parseFromString(body, 'text/html');
            const urls = [
                ...[...doc.querySelectorAll('script[src]')].map((el) => el.getAttribute('src')),
                ...[...doc.querySelectorAll('link[rel=stylesheet]')].map((el) => el.getAttribute('href')),
            ];
            expect(urls.some((url) => url.includes('debugbar'))).to.eq(true);
            urls.forEach((url) => {
                expect(url).to.match(/^\/sub\/_zubzet\/asset-proxy\//);
                cy.request(url).its('status').should('eq', 200);
            });
        });
    });

    it('serves the index at the bare root, with and without a slash', () => {
        cy.request('/sub').its('body').should('include', 'dashboard-controller');
        cy.request('/sub/').its('body').should('include', 'dashboard-controller');
    });

    it('reroutes internally below the root', () => {
        cy.request('/sub/Response/rerouteNonAlias').its('body').should('eq', 'Controller Action');
        cy.request('/sub/Advanced/aliases').its('body').should('eq', 'Controller Action');
    });

    it('redirects to the root on logout', () => {
        cy.request({ url: '/sub/login/logout', followRedirect: false }).then((res) => {
            expect(res.status).to.eq(302);
            expect(res.headers.location).to.eq('/sub/');
        });
    });

    // A cookie path of "/sub/" would not be sent for the bare "/sub"
    it('scopes the maintenance bypass cookie to the root without a trailing slash', () => {
        cy.loginAs('admin');
        cy.request({
            method: 'POST',
            url: '/sub/z/maintenance',
            form: true,
            body: { action: 'bypass-maintenance' },
        }).then((res) => {
            const cookie = [].concat(res.headers['set-cookie'] || []).find((h) => h.startsWith('maintenance='));
            expect(cookie, 'Set-Cookie carrying maintenance').to.exist;
            expect(cookie).to.match(/;\s*path=\/sub(;|$)/i);
        });
        cy.clearCookie('maintenance');
    });

    it('builds working links on the login page', () => {
        cy.visit('/sub/login');
        cy.get('a[href*="login/"]').each(($a) => expectWorkingLink($a.attr('href')));

        // The test app ships no favicon, so only the prefix is checked
        cy.get('link[rel=icon]').should('have.attr', 'href').and('match', /^\/sub\/assets\//);
    });

    it('links the activation page for a not activated account', () => {
        cy.fixture('logins.json').then((logins) => {
            cy.visit('/sub/login');
            cy.query('username').type(logins.not_activated.name);
            cy.query('password').type(logins.not_activated.password);
            cy.query('btn-login').click();

            cy.contains('not activated yet');
            cy.get("a[href*='login/verify']").then(($a) => expectWorkingLink($a.attr('href')));
        });
    });

    it('mails a working password reset link', () => {
        cy.request({ method: 'DELETE', url: 'http://localhost:3300/api/v1/messages', failOnStatusCode: false });
        cy.request({
            method: 'POST',
            url: '/sub/login/forgot-password/check',
            form: true,
            body: { unameemail: 'auth_forgot@cypress.test' },
        });

        cy.request('/AuthProbe/lastResetCode/601').then((res) => {
            const { code } = JSON.parse(res.body);
            const link = `http://localhost:8080/sub/login/reset/${code}/`;

            fetchLatestMailHtml().should('include', link);
            cy.request(link).its('status').should('eq', 200);
        });
    });

    // The requests above reach controllers through the autorouter; these go
    // through Route:: definitions, which match relative to the root directory.
    describe('Route:: definitions', () => {
        it('matches a plain route and its parameters', () => {
            cy.request('/sub/test').its('body').should('include', 'TestRoute Executed');
            cy.request('/sub/test/').its('body').should('include', 'TestRoute Executed');
            cy.request('/sub/test?x=1').its('body').should('include', 'TestRoute Executed');
            cy.request('/sub/abc/7/9').its('body').should('match', /\[userId\] => 7\s+\[postId\] => 9/);
        });

        it('serves the framework routes', () => {
            cy.request('/sub/_zubzet/health').its('body').should('deep.equal', { status: 'healthy' });
            cy.request('/sub/_zubzet/asset-proxy/Z.js').its('headers.content-type')
                .should('include', 'application/javascript');
        });

        it('still runs the route middleware', () => {
            cy.request('/sub/middleware-block').its('body').then((body) => {
                expect(body).to.include('Route Middleware Blocked Executed');
                expect(body).to.not.include('TestRoute Executed');
            });
            cy.request('/sub/RouteDeny/check').its('body')
                .should('include', 'Route Middleware Blocked Executed').and('not.include', 'Route Afterware');
        });

        // Root folders no vhost of the suite serves, dispatched in-process
        function dispatch(root, uri) {
            return cy.request({
                url: '/Routing/dispatchBelowRoot',
                qs: { root, uri },
                failOnStatusCode: false,
            }).its('body');
        }

        it('strips only a whole root directory segment', () => {
            dispatch('/sub/', '/sub/test').should('include', 'TestRoute Executed');
            dispatch('/a/b/', '/a/b/abc/7/9').should('match', /\[userId\] => 7\s+\[postId\] => 9/);

            // "/subtest" only shares the characters, it is not below "/sub/"
            dispatch('/sub/', '/subtest').should('not.include', 'TestRoute Executed');
        });

        it('leaves the default root and paths outside the root as they were', () => {
            dispatch('/', '/test').should('include', 'TestRoute Executed');
            dispatch('/', '/sub/test').should('not.include', 'TestRoute Executed');

            // Not stripped, so matched as before; the web server decides
            // whether such a request reaches the application at all
            dispatch('/sub/', '/test').should('include', 'TestRoute Executed');
        });
    });

    it('keeps the admin login-as button and its redirect below the root', () => {
        cy.loginAs('admin');

        cy.request('/sub/z/edit_user/5').its('body').should('include', '"/sub/z/login_as/5"');
        cy.request({ url: '/sub/z/login_as/5', followRedirect: false }).then((res) => {
            expect(res.status).to.eq(302);
            expect(res.headers.location).to.match(/^\/sub\//);
        });
    });
});
