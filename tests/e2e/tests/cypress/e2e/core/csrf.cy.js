describe('CSRF protection', () => {
    // A Z.Request post to a harmless action
    const marked = { action: 'add', number1: 5, number2: 6 };

    function post(url, body, headers = {}) {
        return cy.request({
            method: 'POST',
            url: url,
            form: true,
            failOnStatusCode: false,
            headers: headers,
            body: body,
        });
    }

    function send(options) {
        return cy.request({ failOnStatusCode: false, ...options });
    }

    // Every response issues the cookie, a cheap GET primes it
    function token() {
        return cy.request('/_zubzet/health').then(() => cy.getCookie('z_csrf')).its('value');
    }

    describe('a Z.js request', () => {
        it('is rejected without the header', () => {
            token();

            post('/Frontend/backendrequest', marked).its('status').should('eq', 403);
        });

        it('is rejected with a header that does not match the cookie', () => {
            token();

            post('/Frontend/backendrequest', marked, { 'X-CSRF-Token': 'f'.repeat(40) }).its('status').should('eq', 403);
        });

        it('is rejected as a form submit without the header', () => {
            token();

            post('/Form/interactions', { isFormData: 1, field_a: 'x' }).its('status').should('eq', 403);
        });

        it('passes with the header matching the cookie', () => {
            token().then((value) => {
                post('/Frontend/backendrequest', marked, { 'X-CSRF-Token': value }).then((res) => {
                    expect(res.status).to.eq(200);
                    const body = typeof res.body === 'string' ? JSON.parse(res.body) : res.body;
                    expect(body).to.include({ result: 'success', response: 11 });
                });
            });
        });
    });

    describe('any other request', () => {
        it('is rejected without the header', () => {
            token();

            post('/CsrfProbe/plain', {}).its('status').should('eq', 403);
        });

        it('passes with the header matching the cookie', () => {
            token().then((value) => {
                post('/CsrfProbe/plain', {}, { 'X-CSRF-Token': value }).its('status').should('eq', 200);
            });
        });

        // A raw HTML form cannot set a header and carries the token in a field
        it('passes with the _csrf field matching the cookie', () => {
            token().then((value) => {
                post('/CsrfProbe/plain', { _csrf: value }).its('status').should('eq', 200);
            });
        });

        it('is rejected with a _csrf field that does not match', () => {
            token();

            post('/CsrfProbe/plain', { _csrf: 'f'.repeat(40) }).its('status').should('eq', 403);
        });

        it('renders the cookie as a hidden _csrf input through CSRF::field()', () => {
            token().then((value) => {
                cy.request('/CsrfProbe/field').its('body')
                    .should('eq', `<input type="hidden" name="_csrf" value="${value}">`);
            });
        });

        it('is rejected with a text/plain body', () => {
            token();

            send({ method: 'POST', url: '/CsrfProbe/plain', body: 'x', headers: { 'Content-Type': 'text/plain' } })
                .its('status').should('eq', 403);
        });

        it('is rejected without a body', () => {
            token();

            send({ method: 'DELETE', url: '/CsrfProbe/plain' }).its('status').should('eq', 403);
        });

        it('passes on a safe method', () => {
            send({ method: 'GET', url: '/CsrfProbe/plain' }).its('status').should('eq', 200);
        });
    });

    // Neither leaves a browser cross-site without a CORS preflight
    describe('a request a browser cannot forge', () => {
        it('passes with a JSON body', () => {
            token();

            send({ method: 'POST', url: '/CsrfProbe/plain', body: { a: 1 } }).its('status').should('eq', 200);
        });

        it('passes with a bearer token', () => {
            token();

            send({ method: 'DELETE', url: '/CsrfProbe/plain', headers: { Authorization: 'Bearer abc' } })
                .its('status').should('eq', 200);
        });

        // The browser attaches cached Basic credentials by itself, cross-site included
        it('is still checked with Basic credentials', () => {
            token();

            send({ method: 'DELETE', url: '/CsrfProbe/plain', headers: { Authorization: 'Basic dXNlcjpwYXNz' } })
                .its('status').should('eq', 403);
        });
    });

    describe('withoutCsrf()', () => {
        it('exempts the routes of a group', () => {
            post('/csrf-exempt/group', {}).its('status').should('eq', 200);
        });

        it('exempts a single route', () => {
            post('/csrf-exempt-route', {}).its('status').should('eq', 200);
        });

        it('exempts convention paths under a group prefix', () => {
            post('/CsrfProbe/exempt', {}).its('status').should('eq', 200);
        });

        it('leaves other routes to the same action checked', () => {
            token();

            post('/csrf-checked-route', {}).its('status').should('eq', 403);
        });
    });

    describe('the bundled login', () => {
        // Login CSRF: a cross-site form posting valid credentials must not start a session
        it('does not log in a forged post without the header', () => {
            token();

            cy.fixture('logins.json').then((logins) => {
                post('/login', { action: 'login', name: logins.customer.name, password: logins.customer.password }).then((res) => {
                    expect(res.status).to.eq(403);
                    const cookies = [].concat(res.headers['set-cookie'] || []);
                    expect(cookies.some((cookie) => cookie.startsWith('z_login_token='))).to.eq(false);
                    const body = typeof res.body === 'string' ? res.body : JSON.stringify(res.body);
                    expect(body).not.to.contain('"result":"success"');
                });
            });
        });

        // checkPermission() runs the login internally, past the dispatch check of the opted-out route
        it('does not log in a forged post through an opted-out route', () => {
            token();

            cy.fixture('logins.json').then((logins) => {
                post('/CsrfProbe/guarded', { action: 'login', name: logins.customer.name, password: logins.customer.password }).then((res) => {
                    expect(res.status).to.eq(403);
                    const cookies = [].concat(res.headers['set-cookie'] || []);
                    expect(cookies.some((cookie) => cookie.startsWith('z_login_token='))).to.eq(false);
                });
            });
        });
    });

    describe('the browser side', () => {
        it('sends the cookie as header from Z.Request', () => {
            cy.intercept('POST', '/Frontend/backendrequest').as('request');
            cy.visit('/Frontend/backendrequest');

            cy.query('add').click();

            cy.wait('@request').then((interception) => {
                cy.getCookie('z_csrf').then((cookie) => {
                    expect(interception.request.headers['x-csrf-token']).to.eq(cookie.value);
                });
                expect(interception.response.statusCode).to.eq(200);
            });
        });

        it('sends the cookie as header from Z.Forms', () => {
            cy.intercept('POST', '/Form/interactions').as('submit');
            cy.visit('/Form/interactions');

            cy.query('form').find('button').click();

            cy.wait('@submit').then((interception) => {
                cy.getCookie('z_csrf').then((cookie) => {
                    expect(interception.request.headers['x-csrf-token']).to.eq(cookie.value);
                });
                expect(interception.response.statusCode).to.eq(200);
            });
        });
    });
});
