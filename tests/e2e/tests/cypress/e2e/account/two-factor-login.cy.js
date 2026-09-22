describe('Two factor login', () => {
    before(() => {
        cy.dbSeed();
    });

    const PASSWORD = 'password';
    const WITH_2FA = 'twofactor_login@cypress.test';
    const WITHOUT_2FA = 'twofactor_enroll@cypress.test';

    const probe = (path) => cy.request(path).then((res) => JSON.parse(res.body));
    const code = (userId, offset = 0) =>
        probe(`/TwoFactorProbe/code/${userId}?offset=${offset}`).then((out) => out.code);

    function login(name, password = PASSWORD) {
        return cy.request({
            method: 'POST',
            url: '/login',
            form: true,
            failOnStatusCode: false,
            body: { action: 'login', name, password },
        }).then((res) => typeof res.body === 'string' ? JSON.parse(res.body) : res.body);
    }

    function redeem(body) {
        return cy.request({
            method: 'POST',
            url: '/_zubzet/two-factor/login',
            form: true,
            failOnStatusCode: false,
            body,
        }).then((res) => typeof res.body === 'string' ? JSON.parse(res.body) : res.body);
    }

    beforeEach(() => {
        cy.clearCookie('z_login_token');

        // The limit counts per account over three minutes
        cy.request('/TwoFactorProbe/clearLoginTries/852');
        cy.request('/TwoFactorProbe/setState/852?secret=JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP&confirmed=1');
        cy.request('/TwoFactorProbe/setState/850?secret=');
    });

    // ---------------------------------------------------------------------
    describe('the challenge', () => {
        it('answers a correct password with a challenge instead of a session', () => {
            login(WITH_2FA).then((out) => {
                expect(out.result).to.eq('success');
                expect(out.twoFactor).to.be.true;
                expect(out.challenge).to.match(/^zub-[0-9a-f]{64}$/);
            });

            cy.getCookie('z_login_token').should('be.null');
        });

        it('logs an account without two factor straight in', () => {
            login(WITHOUT_2FA).then((out) => {
                expect(out.result).to.eq('success');
                expect(out.twoFactor).to.be.undefined;
                expect(out.challenge).to.be.undefined;
            });

            cy.getCookie('z_login_token').should('not.be.null');
        });

        it('asks for no code while an enrollment is unconfirmed', () => {
            cy.request('/TwoFactorProbe/setState/850?secret=JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP&confirmed=0');

            login(WITHOUT_2FA).then((out) => {
                expect(out.result).to.eq('success');
                expect(out.twoFactor).to.be.undefined;
            });

            cy.getCookie('z_login_token').should('not.be.null');
        });

        it('hands out no challenge for a wrong password', () => {
            login(WITH_2FA, 'not the password').then((out) => {
                expect(out).to.include({ result: 'error', message: 'Username or password is wrong' });
                expect(out.challenge).to.be.undefined;
            });
        });

        it('expires the challenge ten minutes out', () => {
            login(WITH_2FA).then(() => {
                probe('/TwoFactorProbe/challenge/852').then((challenge) => {
                    expect(challenge.found).to.be.true;
                    expect(challenge.active).to.be.true;

                    // Measured server side, the browser does not share php's clock
                    expect(challenge.expiresInSeconds).to.be.greaterThan(540);
                    expect(challenge.expiresInSeconds).to.be.at.most(600);
                });
            });
        });
    });

    // ---------------------------------------------------------------------
    describe('redeeming it', () => {
        it('starts the session on a correct code', () => {
            login(WITH_2FA).then((out) => {
                code(852).then((value) => {
                    redeem({ challenge: out.challenge, code: value }).then((redeemed) => {
                        expect(redeemed.result).to.eq('success');
                    });
                });
            });

            cy.getCookie('z_login_token').should('not.be.null');
        });

        it('stamps the fresh check on the new session', () => {
            login(WITH_2FA).then((out) => {
                code(852).then((value) => {
                    redeem({ challenge: out.challenge, code: value });
                });
            });

            cy.getCookie('z_login_token').then((cookie) => {
                probe(`/TwoFactorProbe/session/${cookie.value}`).then((session) => {
                    expect(session.lastTwoFactor).to.not.be.null;
                });
            });
        });

        it('rejects a wrong code without starting a session', () => {
            login(WITH_2FA).then((out) => {
                redeem({ challenge: out.challenge, code: '000000' }).then((redeemed) => {
                    expect(redeemed).to.include({ result: 'error', message: 'Invalid code' });
                });
            });

            cy.getCookie('z_login_token').should('be.null');
        });

        it('rejects a code that belongs to another account', () => {
            login(WITH_2FA).then((out) => {
                code(857).then((value) => {
                    redeem({ challenge: out.challenge, code: value }).then((redeemed) => {
                        expect(redeemed).to.include({ result: 'error', message: 'Invalid code' });
                    });
                });
            });

            cy.getCookie('z_login_token').should('be.null');
        });

        it('keeps the challenge usable after a wrong code', () => {
            login(WITH_2FA).then((out) => {
                redeem({ challenge: out.challenge, code: '000000' });

                code(852).then((value) => {
                    redeem({ challenge: out.challenge, code: value }).then((redeemed) => {
                        expect(redeemed.result).to.eq('success');
                    });
                });
            });
        });

        it('burns the challenge once it was redeemed', () => {
            login(WITH_2FA).then((out) => {
                code(852).then((value) => {
                    redeem({ challenge: out.challenge, code: value });

                    cy.clearCookie('z_login_token');

                    redeem({ challenge: out.challenge, code: value }).then((again) => {
                        expect(again).to.include({ result: 'error', message: 'Invalid challenge' });
                    });
                });

                probe('/TwoFactorProbe/challenge/852').then((challenge) => {
                    expect(challenge.active).to.be.false;
                });
            });

            cy.getCookie('z_login_token').should('be.null');
        });

        it('rejects a challenge that ran out of time', () => {
            login(WITH_2FA).then((out) => {
                cy.request('/TwoFactorProbe/expireChallenge/852');

                code(852).then((value) => {
                    redeem({ challenge: out.challenge, code: value }).then((redeemed) => {
                        expect(redeemed).to.include({ result: 'error', message: 'Invalid challenge' });
                    });
                });
            });

            cy.getCookie('z_login_token').should('be.null');
        });

        it('rejects a challenge nobody handed out', () => {
            redeem({ challenge: 'zub-' + 'f'.repeat(64), code: '000000' }).then((out) => {
                expect(out).to.include({ result: 'error', message: 'Invalid challenge' });
            });
        });

        it('requires a challenge', () => {
            redeem({ code: '000000' }).then((out) => {
                expect(out).to.include({ result: 'error', message: 'Missing challenge' });
            });
        });

        it('requires a code', () => {
            login(WITH_2FA).then((out) => {
                redeem({ challenge: out.challenge }).then((redeemed) => {
                    expect(redeemed).to.include({ result: 'error', message: 'Missing code' });
                });
            });
        });

        it('accepts a code one step either side', () => {
            login(WITH_2FA).then((out) => {
                code(852, -30).then((value) => {
                    redeem({ challenge: out.challenge, code: value }).then((redeemed) => {
                        expect(redeemed.result).to.eq('success');
                    });
                });
            });
        });
    });

    // ---------------------------------------------------------------------
    describe('the login limit', () => {
        // maxLoginTriesPerTimespan = 5 over maxLoginTriesTimespan = 3 minutes
        it('counts a wrong code against it and blocks the account', () => {
            login(WITH_2FA).then((out) => {
                for(let attempt = 0; attempt < 6; attempt++) {
                    redeem({ challenge: out.challenge, code: '000000' });
                }

                redeem({ challenge: out.challenge, code: '000000' }).then((blocked) => {
                    expect(blocked).to.include({
                        result: 'error',
                        message: 'Too many login tries. Try again later.',
                    });
                });
            });
        });

        it('turns the password step away too once the codes used the budget up', () => {
            login(WITH_2FA).then((out) => {
                for(let attempt = 0; attempt < 6; attempt++) {
                    redeem({ challenge: out.challenge, code: '000000' });
                }
            });

            login(WITH_2FA).then((out) => {
                expect(out).to.include({
                    result: 'error',
                    message: 'Too many login tries. Try again later.',
                });
            });
        });

        it('lets a correct code through while the budget lasts', () => {
            login(WITH_2FA).then((out) => {
                redeem({ challenge: out.challenge, code: '000000' });
                redeem({ challenge: out.challenge, code: '000000' });

                code(852).then((value) => {
                    redeem({ challenge: out.challenge, code: value }).then((redeemed) => {
                        expect(redeemed.result).to.eq('success');
                    });
                });
            });
        });
    });

    // ---------------------------------------------------------------------
    describe('through the login page', () => {
        it('asks for the code in a modal and signs in', () => {
            cy.visit('/login');
            cy.intercept('POST', '**/two-factor/login').as('redeem');

            cy.query('username').type(WITH_2FA);
            cy.query('password').type(PASSWORD);
            cy.query('btn-login').click();

            cy.get('#z-two-factor-modal').should('be.visible');

            code(852).then((value) => {
                cy.get('#z-two-factor-digits .z-two-factor-digit').first().type(value);
            });

            cy.wait('@redeem');
            cy.getCookie('z_login_token').should('not.be.null');
        });

        it('shows the error in the modal and lets another code be typed', () => {
            cy.visit('/login');

            cy.query('username').type(WITH_2FA);
            cy.query('password').type(PASSWORD);
            cy.query('btn-login').click();

            cy.get('#z-two-factor-digits .z-two-factor-digit').first().type('000000');

            cy.get('#z-two-factor-modal-error').should('be.visible').and('contain', 'Invalid code');
            cy.get('#z-two-factor-digits .z-two-factor-digit').first().should('have.value', '');
        });

        it('refuses an incomplete code without asking the server', () => {
            cy.visit('/login');

            cy.query('username').type(WITH_2FA);
            cy.query('password').type(PASSWORD);
            cy.query('btn-login').click();

            cy.get('#z-two-factor-digits .z-two-factor-digit').first().type('123');
            cy.get('#z-two-factor-modal-send').click();

            cy.get('#z-two-factor-modal-error').should('contain', 'Enter all six digits.');
        });

        it('leaves no modal in the way of an account without two factor', () => {
            cy.visit('/login');
            cy.intercept('POST', '**/login').as('uilogin');

            cy.query('username').type(WITHOUT_2FA);
            cy.query('password').type(PASSWORD);
            cy.query('btn-login').click();

            cy.wait('@uilogin');
            cy.getCookie('z_login_token').should('not.be.null');
        });
    });
});
