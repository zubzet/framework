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

    it('keeps the admin login-as button and its redirect below the root', () => {
        cy.loginAs('admin');

        cy.request('/sub/z/edit_user/5').its('body').should('include', '"/sub/z/login_as/5"');
        cy.request({ url: '/sub/z/login_as/5', followRedirect: false }).then((res) => {
            expect(res.status).to.eq(302);
            expect(res.headers.location).to.match(/^\/sub\//);
        });
    });
});
