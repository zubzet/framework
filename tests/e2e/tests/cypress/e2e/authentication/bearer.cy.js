describe('Authentication - Bearer', () => {
    before(() => {
        cy.dbSeed();
    });

    const BEARER_TOKEN = '0450a00000000000000000000000000000000000';
    const COOKIE_TOKEN = '0451a00000000000000000000000000000000000';

    function whoami(headers) {
        return cy.request({ url: '/session/whoami', headers }).then((res) => JSON.parse(res.body));
    }

    // A fresh session and api key, each with its plaintext token and the value the row stores
    function issue() {
        return cy.request('/session/tokenAtRest').then((res) => JSON.parse(res.body));
    }

    // cy.session() ties the cookie to the app origin before cy.request(), like the cookie auth tests
    function sendCookie() {
        cy.session('bearer_cookie_451', () => {
            cy.setCookie('z_login_token', COOKIE_TOKEN);
        });
    }

    it('should authenticate a request that carries a valid bearer token', () => {
        whoami({ Authorization: `Bearer ${BEARER_TOKEN}` }).then((output) => {
            expect(output.isLoggedIn).to.be.true;
            expect(output.userId).to.equal(450);
        });
    });

    it('should prefer the bearer token over the session cookie', () => {
        sendCookie();
        whoami({ Authorization: `Bearer ${BEARER_TOKEN}` }).then((output) => {
            expect(output.isLoggedIn).to.be.true;
            expect(output.userId).to.equal(450);
        });
    });

    it('should reject an invalid bearer token instead of falling back to the cookie', () => {
        sendCookie();
        whoami({ Authorization: 'Bearer zub-invalid' }).then((output) => {
            expect(output.isLoggedIn).to.be.false;
            expect(output.userId).to.be.null;
        });
    });

    it('should ignore an Authorization header of another scheme and use the cookie', () => {
        sendCookie();
        whoami({ Authorization: 'Basic dXNlcjpwYXNzd29yZA==' }).then((output) => {
            expect(output.isLoggedIn).to.be.true;
            expect(output.userId).to.equal(451);
        });
    });

    it('should accept the header name and the scheme in any case', () => {
        whoami({ authorization: `bEaReR ${BEARER_TOKEN}` }).then((output) => {
            expect(output.isLoggedIn).to.be.true;
            expect(output.userId).to.equal(450);
        });
    });

    it('should reject an expired session sent as bearer token', () => {
        whoami({ Authorization: 'Bearer 0452a00000000000000000000000000000000000' }).then((output) => {
            expect(output.isLoggedIn).to.be.false;
            expect(output.userId).to.be.null;
        });
    });

    it('should authenticate a request that carries an api key as bearer token', () => {
        whoami({ Authorization: 'Bearer 0453a00000000000000000000000000000000000' }).then((output) => {
            expect(output.isLoggedIn).to.be.true;
            expect(output.userId).to.equal(453);
        });
    });

    it('should check permissions against the user of the bearer token', () => {
        cy.fixture('logins.json').then((logins) => {
            cy.request({
                url: '/z',
                headers: { Authorization: `Bearer ${logins.admin.token}` },
            }).its('body').should('include', 'data-test="dash-logout"');

            cy.request({
                url: '/z',
                headers: { Authorization: `Bearer ${logins.customer.token}` },
                failOnStatusCode: false,
            }).its('status').should('equal', 403);
        });
    });

    it('should authenticate a new session and api key by their bearer token', () => {
        issue().then((issued) => {
            whoami({ Authorization: `Bearer ${issued.session.token}` }).then((output) => {
                expect(output.isLoggedIn).to.be.true;
                expect(output.userId).to.equal(issued.session.userId);
            });

            whoami({ Authorization: `Bearer ${issued.apiKey.token}` }).then((output) => {
                expect(output.isLoggedIn).to.be.true;
                expect(output.userId).to.equal(issued.apiKey.userId);
            });
        });
    });

    it('should authenticate a new session and api key by their cookie', () => {
        issue().then((issued) => {
            cy.setCookie('z_login_token', issued.session.token);
            whoami({}).then((output) => {
                expect(output.isLoggedIn).to.be.true;
                expect(output.userId).to.equal(issued.session.userId);
            });

            cy.setCookie('z_login_token', issued.apiKey.token);
            whoami({}).then((output) => {
                expect(output.isLoggedIn).to.be.true;
                expect(output.userId).to.equal(issued.apiKey.userId);
            });
        });
    });

    it('should reject the stored hash of a new token sent as bearer token', () => {
        issue().then((issued) => {
            [issued.session, issued.apiKey].forEach((token) => {
                whoami({ Authorization: `Bearer ${token.stored}` }).then((output) => {
                    expect(output.isLoggedIn).to.be.false;
                    expect(output.userId).to.be.null;
                });
            });
        });
    });

    it('should end a new session on logout with its cookie', () => {
        issue().then((issued) => {
            const token = issued.session.token;

            cy.request(`/AuthProbe/tokenActive/${token}`).then((res) => {
                expect(JSON.parse(res.body)).to.deep.equal({ exists: true, active: true });
            });

            cy.setCookie('z_login_token', token);
            cy.request({ url: '/login/logout', followRedirect: false });

            cy.request(`/AuthProbe/tokenActive/${token}`).then((res) => {
                expect(JSON.parse(res.body)).to.deep.equal({ exists: true, active: false });
            });
        });
    });

    it('should end the session of the bearer token on logout', () => {
        const headers = { Authorization: 'Bearer 0454a00000000000000000000000000000000000' };

        whoami(headers).then((output) => {
            expect(output.userId).to.equal(454);
        });

        cy.request({ url: '/login/logout', headers, followRedirect: false });

        whoami(headers).then((output) => {
            expect(output.isLoggedIn).to.be.false;
            expect(output.userId).to.be.null;
        });
    });
});
