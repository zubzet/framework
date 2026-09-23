describe('Two factor gate', () => {
    before(() => {
        cy.dbSeed();
        cy.saveConfigBackup();
    });

    after(() => {
        cy.restoreConfigBackup();
    });

    const token = (prefix) => prefix.padEnd(40, '0');
    const login = (prefix) => cy.setCookie('z_login_token', token(prefix));

    const probe = (path) => cy.request(path).then((res) => JSON.parse(res.body));
    const code = (userId) => probe(`/TwoFactorProbe/code/${userId}`).then((out) => out.code);
    const sessionState = (prefix) => probe(`/TwoFactorProbe/session/${token(prefix)}`);

    // Stamps the last check <seconds> ago, without an argument it clears it
    const age = (prefix, seconds = null) =>
        cy.request(`/TwoFactorProbe/ageSession/${token(prefix)}` + (seconds === null ? '' : `?seconds=${seconds}`));

    const resetSession = (prefix, tries = 5) =>
        cy.request(`/TwoFactorProbe/resetSession/${token(prefix)}?tries=${tries}`);

    // A spent budget signs the session out, so every case puts them back first
    beforeEach(() => {
        resetSession('0854a');
        resetSession('0854b');
        resetSession('0854c');
        resetSession('0855a');
        resetSession('0855b');
        cy.request('/TwoFactorProbe/setState/854?secret=JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP&confirmed=1');
        cy.request('/TwoFactorProbe/setState/855?secret=');
    });

    function guard(path) {
        return cy.request({
            method: 'POST',
            url: `/TwoFactorProbe/${path}`,
            form: true,
            failOnStatusCode: false,
            body: {},
        }).then((res) => typeof res.body === 'string' ? JSON.parse(res.body) : res.body);
    }

    const expectPassed = (out) => expect(out.result).to.eq('success');
    const expectRenew = (out) => expect(out).to.include({
        result: 'error',
        message: 'Two factor required',
        twoFactorRenew: true,
    });

    // ---------------------------------------------------------------------
    describe('the configured window', () => {
        it('turns a session away that never passed a check', () => {
            login('0854a');
            age('0854a');

            guard('guardDefault').then(expectRenew);
        });

        it('lets a session through that just passed one', () => {
            login('0854a');
            age('0854a', 10);

            guard('guardDefault').then(expectPassed);
        });

        it('lets a session through right up to the edge of the window', () => {
            login('0854a');
            age('0854a', 880);

            guard('guardDefault').then(expectPassed);
        });

        it('turns a session away once the check aged past the window', () => {
            login('0854a');
            age('0854a', 901);

            guard('guardDefault').then(expectRenew);
        });

        it('follows the window the config names', () => {
            login('0854a');
            age('0854a', 120);

            guard('guardDefault').then(expectPassed);

            cy.setConfigSetting('two_factor_freshness_seconds', 60);
            guard('guardDefault').then(expectRenew);

            cy.setConfigSetting('two_factor_freshness_seconds', 900);
            guard('guardDefault').then(expectPassed);
        });
    });

    // ---------------------------------------------------------------------
    describe('a window the caller picks', () => {
        it('turns a session away that is older than the given seconds', () => {
            login('0854a');
            age('0854a', 45);

            guard('guardCustom?seconds=30').then(expectRenew);
        });

        it('lets a session through that is younger than the given seconds', () => {
            login('0854a');
            age('0854a', 15);

            guard('guardCustom?seconds=30').then(expectPassed);
        });

        it('outranks the configured window in both directions', () => {
            login('0854a');
            age('0854a', 300);

            guard('guardCustom?seconds=60').then(expectRenew);
            guard('guardCustom?seconds=3600').then(expectPassed);
            guard('guardDefault').then(expectPassed);
        });
    });

    // ---------------------------------------------------------------------
    describe('who the gate applies to', () => {
        it('lets an account without two factor through, however old the session', () => {
            login('0855a');
            age('0855a');

            guard('guardDefault').then(expectPassed);
            guard('guardCustom?seconds=1').then(expectPassed);
        });

        it('turns an api key away while the account carries two factor', () => {
            login('0854c');

            guard('guardDefault').then(expectRenew);
        });

        it('lets an api key through while the account carries none', () => {
            login('0855b');

            guard('guardDefault').then(expectPassed);
        });

        it('turns a visitor without a login away', () => {
            cy.clearCookie('z_login_token');

            guard('guardDefault').then((out) => {
                expect(out.twoFactorRenew).to.be.true;
            });
        });

        it('judges each session of an account on its own', () => {
            age('0854a', 10);
            age('0854b', 901);

            login('0854a');
            guard('guardDefault').then(expectPassed);

            login('0854b');
            guard('guardDefault').then(expectRenew);
        });
    });

    // ---------------------------------------------------------------------
    describe('without boolResult', () => {
        it('answers a stale session with a 403 page', () => {
            login('0854a');
            age('0854a');

            cy.request({ url: '/TwoFactorProbe/guardPage', failOnStatusCode: false })
                .then((res) => expect(res.status).to.eq(403));
        });

        it('serves the page to a fresh session', () => {
            login('0854a');
            age('0854a', 10);

            cy.request('/TwoFactorProbe/guardPage')
                .then((res) => expect(res.body).to.contain('guarded page reached'));
        });
    });

    // ---------------------------------------------------------------------
    describe('renewing the check', () => {
        const refresh = (body) => cy.request({
            method: 'POST',
            url: '/_zubzet/two-factor/refresh',
            form: true,
            failOnStatusCode: false,
            body,
        }).then((res) => typeof res.body === 'string' ? JSON.parse(res.body) : res.body);

        it('stamps the session and opens the gate again', () => {
            login('0854a');
            age('0854a');

            guard('guardDefault').then(expectRenew);

            code(854).then((value) => {
                refresh({ code: value }).then((out) => expect(out.result).to.eq('success'));
            });

            sessionState('0854a').then((state) => expect(state.lastTwoFactor).to.not.be.null);
            guard('guardDefault').then(expectPassed);
        });

        it('rejects a wrong code and leaves the gate shut', () => {
            login('0854a');
            age('0854a');

            refresh({ code: '000000' }).then((out) => {
                expect(out).to.include({ result: 'error', message: 'Wrong code' });
            });

            sessionState('0854a').then((state) => expect(state.lastTwoFactor).to.be.null);
            guard('guardDefault').then(expectRenew);
        });

        it('spends a try on a wrong code and gives the budget back on a right one', () => {
            login('0854a');
            resetSession('0854a', 5);

            refresh({ code: '000000' });
            sessionState('0854a').then((state) => expect(state.remainingTries).to.eq(4));

            code(854).then((value) => refresh({ code: value }));
            sessionState('0854a').then((state) => expect(state.remainingTries).to.eq(5));
        });

        it('signs the session out once the last try is gone', () => {
            login('0854b');
            resetSession('0854b', 1);

            refresh({ code: '000000' }).then((out) => {
                expect(out).to.include({
                    result: 'error',
                    message: 'Too many wrong codes. Signed out.',
                });
            });

            sessionState('0854b').then((state) => expect(state.active).to.be.false);
        });

        it('answers an api key rather than crashing on it', () => {
            login('0854c');

            refresh({ code: '000000' }).then((out) => {
                expect(out).to.include({ result: 'error', message: 'Wrong code' });
            });

            sessionState('0854c').then((state) => expect(state.remainingTries).to.eq(4));
        });

        it('lets an api key become fresh through a typed code', () => {
            login('0854c');

            guard('guardDefault').then(expectRenew);

            code(854).then((value) => {
                refresh({ code: value }).then((out) => expect(out.result).to.eq('success'));
            });

            guard('guardDefault').then(expectPassed);
        });

        it('gives an impersonated session no check of its own', () => {
            age('0854a', 10);

            cy.setCookie('z_login_token', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa01');
            cy.request({ url: '/z/login_as/854', followRedirect: false });

            // A fresh session of the same account does not lend its check
            guard('guardDefault').then(expectRenew);
        });

        it('refuses for an account without two factor', () => {
            login('0855a');

            refresh({ code: '000000' }).then((out) => {
                expect(out).to.include({ result: 'error', message: 'Two factor is not active' });
            });
        });

        it('refuses without a login', () => {
            cy.clearCookie('z_login_token');

            refresh({ code: '000000' }).then((out) => {
                expect(out).to.include({ result: 'error', message: 'Not logged in' });
            });
        });
    });

    // ---------------------------------------------------------------------
    describe('through the page', () => {
        const digits = () => cy.get('#z-two-factor-digits .z-two-factor-digit');
        const typeCode = (userId) => code(userId).then((value) => digits().first().type(value));

        const RENEWED = 'Two factor check was successful. Please try again.';

        // Cypress accepts an alert on its own, the text is what is asserted
        const catchAlerts = () => {
            const seen = [];
            cy.on('window:alert', (text) => seen.push(text));
            return seen;
        };

        it('opens the modal on a guarded ZForm and confirms without resending', () => {
            login('0854a');
            age('0854a');

            cy.visit('/TwoFactorProbe/formPage');
            cy.intercept('POST', '**/TwoFactorProbe/formPage').as('submit');
            const alerts = catchAlerts();

            cy.get('#form button.btn-primary').click();
            cy.wait('@submit');

            cy.get('#z-two-factor-modal').should('be.visible');
            cy.query('result').should('be.empty');

            typeCode(854);

            // The form was not resent, the visitor is told to do it
            cy.wrap(alerts).should('deep.eq', [RENEWED]);
            cy.get('#z-two-factor-modal').should('not.be.visible');
            cy.query('result').should('be.empty');

            // The typed values survived, resent once ZForm's 300 ms double-submit guard has passed
            cy.wait(400);
            cy.get('#form button.btn-primary').click();
            cy.wait('@submit');
            cy.query('result').should('contain', 'saved');
        });

        it('leaves no save error behind when a ZForm runs into the gate', () => {
            login('0854a');
            age('0854a');

            cy.visit('/TwoFactorProbe/formPage');
            catchAlerts();

            cy.get('#form button.btn-primary').click();
            cy.get('#z-two-factor-modal').should('be.visible');

            cy.get('#form .alert').should('not.be.visible');
        });

        it('opens the modal on a guarded ZAction', () => {
            login('0854a');
            age('0854a');

            cy.visit('/TwoFactorProbe/actionPage');
            cy.intercept('POST', '**/TwoFactorProbe/actionPage').as('probe');
            const alerts = catchAlerts();

            cy.query('btn-action').click();
            cy.wait('@probe');

            cy.get('#z-two-factor-modal').should('be.visible');
            cy.query('result').should('be.empty');

            typeCode(854);

            cy.wrap(alerts).should('deep.eq', [RENEWED]);
            cy.query('result').should('be.empty');

            cy.query('btn-action').click();
            cy.wait('@probe');
            cy.query('result').should('contain', 'passed');
        });

        it('shows a wrong code in the modal and keeps it open', () => {
            login('0854a');
            age('0854a');

            cy.visit('/TwoFactorProbe/actionPage');
            const alerts = catchAlerts();

            cy.query('btn-action').click();
            digits().first().type('000000');

            cy.get('#z-two-factor-modal-error').should('be.visible').and('contain', 'Wrong code');
            cy.get('#z-two-factor-modal').should('be.visible');
            digits().first().should('have.value', '');
            cy.wrap(alerts).should('be.empty');
            cy.query('result').should('be.empty');
        });

        it('leaves a fresh session alone', () => {
            login('0854a');
            age('0854a', 10);

            cy.visit('/TwoFactorProbe/actionPage');

            cy.query('btn-action').click();

            cy.query('result').should('contain', 'passed');

            // Z.js clones the modal out of its template on the first open
            cy.get('#z-two-factor-modal').should('not.exist');
        });
    });
});
