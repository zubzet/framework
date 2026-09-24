describe('CSRF protection', () => {
    // A marked Z.Request post to a harmless action
    const marked = { action: 'add', _zReq: 1, number1: 5, number2: 6 };

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

    // Every response issues the cookie, a cheap GET primes it
    function token() {
        return cy.request('/_zubzet/health').then(() => cy.getCookie('z_csrf')).its('value');
    }

    describe('a marked request', () => {
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

    describe('an unmarked request', () => {
        it('is not checked by the router', () => {
            token();

            post('/Frontend/backendrequest', { number1: 5, number2: 6 }).its('status').should('eq', 200);
        });

        it('is rejected without the header where the action enforces it', () => {
            token();

            post('/CsrfProbe/enforced', {}).its('status').should('eq', 403);
        });

        it('passes where the action enforces it and the header matches', () => {
            token().then((value) => {
                post('/CsrfProbe/enforced', {}, { 'X-CSRF-Token': value }).its('status').should('eq', 200);
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
