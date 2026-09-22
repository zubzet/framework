describe('Two factor', () => {
    before(() => {
        cy.dbSeed();
    });

    const token = (prefix) => prefix.padEnd(40, '0');
    const login = (prefix) => cy.setCookie('z_login_token', token(prefix));

    function post(action, body, url = '/_zubzet/profile') {
        return cy.request({
            method: 'POST',
            url: `${url}/${action}`,
            form: true,
            failOnStatusCode: false,
            body,
        }).then((res) => typeof res.body === 'string' ? JSON.parse(res.body) : res.body);
    }

    const probe = (path) => cy.request(path).then((res) => JSON.parse(res.body));

    const code = (userId, offset = 0) =>
        probe(`/TwoFactorProbe/code/${userId}?offset=${offset}`).then((out) => out.code);

    const userState = (userId) => probe(`/TwoFactorProbe/user/${userId}`);
    const sessionState = (prefix) => probe(`/TwoFactorProbe/session/${token(prefix)}`);

    const SECRET_A = 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP';
    const SECRET_B = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    // Each case states the state it needs, none relies on the one before it
    const setState = (userId, secret = '', confirmed = 1) =>
        cy.request(`/TwoFactorProbe/setState/${userId}?secret=${secret}&confirmed=${confirmed}`);

    const resetSession = (prefix, tries = 5) =>
        cy.request(`/TwoFactorProbe/resetSession/${token(prefix)}?tries=${tries}`);

    // ---------------------------------------------------------------------
    describe('enabling', () => {
        beforeEach(() => {
            setState(850);
            setState(851, SECRET_A);
            setState(855);
            setState(856, SECRET_A, 0);
            resetSession('0850a');
        });

        it('hands out a secret and a provisioning uri', () => {
            login('0850a');

            post('start-two-factor', {}).then((out) => {
                expect(out.result).to.eq('success');
                expect(out.secret).to.match(/^[A-Z2-7]{32}$/);
                expect(out.uri).to.contain('otpauth://totp/');
                expect(out.uri).to.contain('twofactor_enroll%40cypress.test');
                expect(out.uri).to.contain(`secret=${out.secret}`);
            });
        });

        it('stores the secret unconfirmed, so two factor stays off', () => {
            login('0850a');

            post('start-two-factor', {}).then(() => {
                userState(850).then((state) => {
                    expect(state.hasSecret).to.be.true;
                    expect(state.confirmedAt).to.be.null;
                    expect(state.hasTwoFactor).to.be.false;
                });
            });
        });

        it('turns two factor on once a code confirms the secret', () => {
            login('0850a');

            post('start-two-factor', {}).then((out) => {
                probe(`/TwoFactorProbe/codeForSecret/${out.secret}`).then(({ code }) => {
                    post('confirm-two-factor', { code }).then((confirmed) => {
                        expect(confirmed.result).to.eq('success');
                    });

                    userState(850).then((state) => {
                        expect(state.hasTwoFactor).to.be.true;
                        expect(state.confirmedAt).to.not.be.null;
                    });
                });
            });
        });

        it('rejects a wrong code and leaves two factor off', () => {
            login('0850a');

            post('start-two-factor', {}).then(() => {
                post('confirm-two-factor', { code: '000000' }).then((out) => {
                    expect(out).to.include({ result: 'error', message: 'Wrong code' });
                });

                userState(850).then((state) => {
                    expect(state.hasSecret).to.be.true;
                    expect(state.hasTwoFactor).to.be.false;
                });
            });
        });

        it('refuses to confirm an account that was never enrolled', () => {
            login('0855a');

            post('confirm-two-factor', { code: '000000' }).then((out) => {
                expect(out).to.include({ result: 'error', message: 'Wrong code' });
            });
        });

        it('confirms a secret that was left pending', () => {
            login('0856a');

            code(856).then((value) => {
                post('confirm-two-factor', { code: value }).then((out) => {
                    expect(out.result).to.eq('success');
                });
            });

            userState(856).then((state) => expect(state.hasTwoFactor).to.be.true);
        });

        it('refuses to start over while two factor is active', () => {
            login('0851a');

            post('start-two-factor', {}).then((out) => {
                expect(out).to.include({ result: 'error', message: 'Two factor is already active' });
            });

            // The stored secret is untouched
            userState(851).then((state) => {
                expect(state.secret).to.eq('JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP');
            });
        });

        it('refuses to confirm again while two factor is active', () => {
            login('0851a');

            code(851).then((value) => {
                post('confirm-two-factor', { code: value }).then((out) => {
                    expect(out).to.include({ result: 'error', message: 'Two factor is already active' });
                });
            });
        });

        it('turns nobody on without a login', () => {
            cy.clearCookie('z_login_token');

            post('start-two-factor', {}).then((out) => {
                expect(out).to.include({ result: 'error', message: 'Not logged in' });
            });

            post('confirm-two-factor', { code: '000000' }).then((out) => {
                expect(out).to.include({ result: 'error', message: 'Not logged in' });
            });
        });

        it('forgives the tries the session spent while enrolling', () => {
            login('0850a');
            resetSession('0850a', 2);

            post('start-two-factor', {}).then((out) => {
                probe(`/TwoFactorProbe/codeForSecret/${out.secret}`).then(({ code }) => {
                    post('confirm-two-factor', { code });
                });
            });

            sessionState('0850a').then((state) => expect(state.remainingTries).to.eq(5));
        });

        it('counts turning it on as a passed check', () => {
            login('0850a');
            cy.request(`/TwoFactorProbe/ageSession/${token('0850a')}`);

            post('start-two-factor', {}).then((out) => {
                probe(`/TwoFactorProbe/codeForSecret/${out.secret}`).then(({ code }) => {
                    post('confirm-two-factor', { code });
                });
            });

            sessionState('0850a').then((state) => expect(state.lastTwoFactor).to.not.be.null);
        });
    });

    // ---------------------------------------------------------------------
    describe('disabling', () => {
        beforeEach(() => {
            setState(851, SECRET_A);
            setState(855);
            setState(857, SECRET_B);
            resetSession('0851a');
        });

        it('needs a code', () => {
            login('0851a');

            post('disable-two-factor', {}).then((out) => {
                expect(out).to.include({ result: 'error', message: 'Wrong code' });
            });

            userState(851).then((state) => expect(state.hasTwoFactor).to.be.true);
        });

        it('rejects a wrong code and keeps two factor on', () => {
            login('0851a');

            post('disable-two-factor', { code: '000000' }).then((out) => {
                expect(out).to.include({ result: 'error', message: 'Wrong code' });
            });

            userState(851).then((state) => expect(state.hasTwoFactor).to.be.true);
        });

        it('rejects a code that belongs to another account', () => {
            login('0851a');

            code(857).then((value) => {
                post('disable-two-factor', { code: value }).then((out) => {
                    expect(out).to.include({ result: 'error', message: 'Wrong code' });
                });
            });

            userState(851).then((state) => expect(state.hasTwoFactor).to.be.true);
        });

        it('drops two factor and the secret behind it on a correct code', () => {
            login('0851a');

            code(851).then((value) => {
                post('disable-two-factor', { code: value }).then((out) => {
                    expect(out.result).to.eq('success');
                });
            });

            userState(851).then((state) => {
                expect(state.hasTwoFactor).to.be.false;
                expect(state.hasSecret).to.be.false;
                expect(state.confirmedAt).to.be.null;
            });
        });

        it('refuses when two factor is not active', () => {
            login('0855a');

            post('disable-two-factor', { code: '000000' }).then((out) => {
                expect(out).to.include({ result: 'error', message: 'Two factor is not active' });
            });
        });

        it('refuses without a login', () => {
            cy.clearCookie('z_login_token');

            post('disable-two-factor', { code: '000000' }).then((out) => {
                expect(out).to.include({ result: 'error', message: 'Not logged in' });
            });
        });
    });

    // ---------------------------------------------------------------------
    describe('the try budget', () => {
        beforeEach(() => {
            setState(857, SECRET_B);
            resetSession('0857a');
        });

        it('spends a try on every wrong code', () => {
            login('0857a');

            post('disable-two-factor', { code: '000000' });
            sessionState('0857a').then((state) => expect(state.remainingTries).to.eq(4));

            post('disable-two-factor', { code: '000000' });
            sessionState('0857a').then((state) => expect(state.remainingTries).to.eq(3));
        });

        it('signs the session out once the last try is gone', () => {
            login('0857a');
            resetSession('0857a', 1);

            post('disable-two-factor', { code: '000000' }).then((out) => {
                expect(out).to.include({
                    result: 'error',
                    message: 'Too many wrong codes. Signed out.',
                });
            });

            sessionState('0857a').then((state) => {
                expect(state.active).to.be.false;
                expect(state.remainingTries).to.eq(0);
            });
        });

        it('leaves the budget alone on a correct code', () => {
            login('0857a');
            resetSession('0857a', 3);

            code(857).then((value) => {
                post('disable-two-factor', { code: value }).then((out) => {
                    expect(out.result).to.eq('success');
                });
            });

            sessionState('0857a').then((state) => expect(state.remainingTries).to.eq(3));
        });
    });

    // ---------------------------------------------------------------------
    describe('the accepted window', () => {
        beforeEach(() => {
            setState(851, SECRET_A);
            resetSession('0851a');
        });

        it('accepts the code of the step before', () => {
            login('0851a');

            code(851, -30).then((value) => {
                post('disable-two-factor', { code: value }).then((out) => {
                    expect(out.result).to.eq('success');
                });
            });
        });

        it('accepts the code of the step after', () => {
            login('0851a');

            code(851, 30).then((value) => {
                post('disable-two-factor', { code: value }).then((out) => {
                    expect(out.result).to.eq('success');
                });
            });
        });

        it('rejects a code two steps old', () => {
            login('0851a');

            code(851, -90).then((value) => {
                post('disable-two-factor', { code: value }).then((out) => {
                    expect(out).to.include({ result: 'error', message: 'Wrong code' });
                });
            });

            userState(851).then((state) => expect(state.hasTwoFactor).to.be.true);
        });

        it('ignores the whitespace around a code', () => {
            login('0851a');

            code(851).then((value) => {
                post('disable-two-factor', { code: `  ${value} ` }).then((out) => {
                    expect(out.result).to.eq('success');
                });
            });
        });
    });

    // ---------------------------------------------------------------------
    describe('through the profile page', () => {
        beforeEach(() => {
            setState(850);
            setState(851, SECRET_A);
            resetSession('0850a');
            resetSession('0851a');
        });

        it('shows the off state', () => {
            login('0850a');
            cy.visit('/z/profile');

            cy.query('two-factor-inactive').should('be.visible');
            cy.query('btn-start-two-factor').should('be.visible');
            cy.query('two-factor-active').should('not.exist');
            cy.query('two-factor-setup').should('not.be.visible');
        });

        it('shows the on state', () => {
            login('0851a');
            cy.visit('/z/profile');

            cy.query('two-factor-active').should('be.visible').and('contain', 'Two factor is on');
            cy.query('btn-disable-two-factor').should('be.visible');
            cy.query('btn-start-two-factor').should('not.exist');
            cy.query('two-factor-disable-form').should('not.be.visible');
        });

        it('enrolls, showing the secret and a qr code once', () => {
            login('0850a');
            cy.visit('/z/profile');
            cy.intercept('POST', '**/profile/start-two-factor').as('start');
            cy.intercept('POST', '**/profile/confirm-two-factor').as('confirm');

            cy.query('btn-start-two-factor').click();
            cy.wait('@start');

            cy.query('two-factor-setup').should('be.visible');
            cy.query('two-factor-qr').find('canvas, img, table').should('exist');

            cy.query('two-factor-secret').invoke('val').then((secret) => {
                expect(secret).to.match(/^[A-Z2-7]{32}$/);

                return cy.request(`/TwoFactorProbe/codeForSecret/${secret}`);
            }).then((res) => {
                cy.query('two-factor-code').type(JSON.parse(res.body).code);
                cy.query('btn-confirm-two-factor').click();
            });

            cy.wait('@confirm');
            cy.query('two-factor-active').should('be.visible');
            userState(850).then((state) => expect(state.hasTwoFactor).to.be.true);
        });

        it('shows a wrong confirmation code as an error', () => {
            login('0850a');
            cy.visit('/z/profile');
            cy.intercept('POST', '**/profile/confirm-two-factor').as('confirm');

            cy.query('btn-start-two-factor').click();
            cy.query('two-factor-setup').should('be.visible');

            cy.query('two-factor-code').type('000000');
            cy.query('btn-confirm-two-factor').click();
            cy.wait('@confirm');

            cy.query('two-factor-error').should('be.visible').and('contain', 'Wrong code');
            userState(850).then((state) => expect(state.hasTwoFactor).to.be.false);
        });

        it('turns it off behind a code', () => {
            login('0851a');
            cy.visit('/z/profile');
            cy.intercept('POST', '**/profile/disable-two-factor').as('disable');

            cy.query('btn-disable-two-factor').click();
            cy.query('two-factor-disable-form').should('be.visible');

            code(851).then((value) => {
                cy.query('two-factor-disable-code').type(value);
                cy.query('btn-confirm-disable-two-factor').click();
            });

            cy.wait('@disable');
            cy.query('two-factor-inactive').should('be.visible');
            userState(851).then((state) => expect(state.hasTwoFactor).to.be.false);
        });

        it('shows a wrong disable code as an error', () => {
            login('0851a');
            cy.visit('/z/profile');
            cy.intercept('POST', '**/profile/disable-two-factor').as('disable');

            cy.query('btn-disable-two-factor').click();
            cy.query('two-factor-disable-code').type('000000');
            cy.query('btn-confirm-disable-two-factor').click();
            cy.wait('@disable');

            cy.query('two-factor-error').should('be.visible').and('contain', 'Wrong code');
            userState(851).then((state) => expect(state.hasTwoFactor).to.be.true);
        });
    });

    // ---------------------------------------------------------------------
    describe('an admin turning it off', () => {
        const adminDisable = (userId) => post(`disable_two_factor/${userId}`, { action: 'disable_two_factor' }, '/z');

        beforeEach(() => {
            setState(851, SECRET_A);
            setState(853, SECRET_A);
            setState(855);
        });

        it('takes two factor off an account without a code', () => {
            login('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa01');

            adminDisable(853).then((out) => expect(out.result).to.eq('success'));

            userState(853).then((state) => {
                expect(state.hasTwoFactor).to.be.false;
                expect(state.hasSecret).to.be.false;
            });
        });

        it('refuses when the account has no two factor', () => {
            login('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa01');

            adminDisable(855).then((out) => {
                expect(out).to.include({ result: 'error', message: 'Two factor is not active' });
            });
        });

        it('refuses for an unknown account', () => {
            login('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa01');

            adminDisable(999999).then((out) => {
                expect(out).to.include({ result: 'error', message: 'Unknown user' });
            });
        });

        it('is closed to an account without admin.user.edit', () => {
            login('0851a');

            cy.request({
                method: 'POST',
                url: '/z/disable_two_factor/851',
                form: true,
                failOnStatusCode: false,
                body: { action: 'disable_two_factor' },
            }).then((res) => {
                expect(res.status).to.eq(403);
            });

            userState(851).then((state) => expect(state.hasTwoFactor).to.be.true);
        });

        it('offers the button on the edit page only while two factor is on', () => {
            login('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa01');

            cy.visit('/z/edit_user/851');
            cy.contains('Disable two factor').should('exist');

            cy.visit('/z/edit_user/855');
            cy.contains('Disable two factor').should('not.exist');
        });
    });
});
