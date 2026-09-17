describe('Profile', () => {
    before(() => {
        cy.dbSeed();
    });

    const uuid = (id) => `00000000-0000-7000-8000-000000000${id}`;
    const token = (prefix) => prefix.padEnd(40, '0');

    const login = (prefix) => cy.setCookie('z_login_token', token(prefix));
    const sessionRow = (id) => cy.get(`[data-test="session-${uuid(id)}"]`);
    const apiKeyRow = (id) => cy.get(`[data-test="api-key-${uuid(id)}"]`);
    const apiKeyList = () => cy.get('#z-api-keys .list-group');

    function post(action, body) {
        const isRaw = typeof body === 'string';

        return cy.request({
            method: 'POST',
            url: `/_zubzet/profile/${action}`,
            form: !isRaw,
            headers: isRaw ? { 'content-type': 'application/x-www-form-urlencoded' } : undefined,
            failOnStatusCode: false,
            body,
        }).then((res) => typeof res.body === 'string' ? JSON.parse(res.body) : res.body);
    }

    function tokenActive(prefix) {
        return cy.request(`/AuthProbe/tokenActive/${token(prefix)}`)
            .then((res) => JSON.parse(res.body));
    }

    function expectUnknownToken(out) {
        expect(out).to.include({ result: 'error', message: 'Unknown token' });
    }

    // A fading modal swallows keystrokes and clicks while it animates
    const dontFade = (selector) => cy.query(selector).invoke('removeClass', 'fade');

    // The expiry the backend derives from the database-written `created`
    function dayOffset(days) {
        const date = new Date();
        date.setDate(date.getDate() + days);
        return date.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    // ---------------------------------------------------------------------
    describe('action_profile', () => {
        it('shows the data of the account behind the cookie', () => {
            login('0800a');
            cy.visit('/z/profile');

            cy.query('profile-heading').should('be.visible');
            cy.query('profile-email').should('contain', 'profile_view@cypress.test');
            cy.query('profile-created').should('contain', '17.05.2020');
            cy.query('profile-organization').should('contain', 'profile_Organization');
        });

        it('leaves the organization out when the account has none', () => {
            login('0801a');
            cy.visit('/z/profile');

            cy.query('profile-email').should('contain', 'profile_plain@cypress.test');
            cy.query('profile-organization').should('not.exist');
        });

        it('renders the login page when nobody is logged in', () => {
            cy.clearCookie('z_login_token');
            cy.visit('/z/profile');

            cy.query('btn-login').should('be.visible');
            cy.query('profile-heading').should('not.exist');
        });

        // The only page under /z that is not gated on a permission
        it('is open to an account that may not open the admin panel', () => {
            cy.loginAs('customer');

            cy.visit('/z/profile');
            cy.query('profile-heading').should('be.visible');

            cy.request({ url: '/z', failOnStatusCode: false })
                .its('status').should('eq', 403);
        });

        it('is linked from the sidebar and the dashboard', () => {
            cy.loginAs('admin');
            cy.visit('/z');

            cy.query('dash-profile').should('have.attr', 'href').and('match', /\/z\/profile$/);
            cy.query('btn-profile').should('have.attr', 'href').and('match', /\/z\/profile$/);
        });
    });

    // ---------------------------------------------------------------------
    describe('changePassword', () => {
        const credentials = {
            password_current: 'password',
            password_new: 'changed-password',
            password_repeat: 'changed-password',
        };

        it('rejects a wrong current password', () => {
            login('0803a');

            post('change-password', { ...credentials, password_current: 'not the password' }).then((out) => {
                expect(out.result).to.eq('formErrors');
                expect(out.formErrors).to.deep.include({ name: 'password_current', type: 'password_wrong' });
            });
        });

        it('rejects a new password that was repeated differently', () => {
            login('0803a');

            post('change-password', { ...credentials, password_repeat: 'something else' }).then((out) => {
                expect(out.result).to.eq('formErrors');
                expect(out.formErrors).to.deep.include({ name: 'password_repeat', type: 'password_mismatch' });
            });
        });

        it('rejects a new password under three characters', () => {
            login('0803a');

            post('change-password', { ...credentials, password_new: 'ab', password_repeat: 'ab' }).then((out) => {
                expect(out.result).to.eq('formErrors');
                expect(out.formErrors).to.deep.include({ name: 'password_new', type: 'length', info: [3, 64] });
            });
        });

        it('requires every field', () => {
            login('0803a');

            post('change-password', {}).then((out) => {
                expect(out.result).to.eq('formErrors');
                expect(out.formErrors.map((error) => error.name)).to.deep.eq([
                    'password_current',
                    'password_new',
                    'password_repeat',
                ]);
            });
        });

        // An array passes the length rule by its item count, so the endpoint
        // has to turn it away before it reaches the hashing
        it('turns away a password that is not a string', () => {
            login('0803a');

            const body = 'password_current=password&password_new[]=a&password_new[]=b&password_new[]=c&password_repeat=abc';

            post('change-password', body).then((out) => {
                expect(out).to.include({ result: 'error', message: 'Invalid input' });
            });
        });

        it('turns away a visitor who is not logged in', () => {
            cy.clearCookie('z_login_token');

            post('change-password', credentials).then((out) => {
                expect(out).to.include({ result: 'error', message: 'Not logged in' });
            });
        });

        it('marks the current password as wrong on the form', () => {
            login('0803a');
            cy.visit('/z/profile');
            cy.intercept('POST', '**/_zubzet/profile/change-password').as('change');

            cy.form('password_current').type('not the password');
            cy.form('password_new').type('changed-password');
            cy.form('password_repeat').type('changed-password');
            cy.get('#z-change-password button.btn-primary').click();
            cy.wait('@change');

            cy.get('#z-change-password .text-danger')
                .should('be.visible')
                .and('contain', 'Your current password is wrong!');
        });

        it('changes the password, ends every login and spares the api keys', () => {
            login('0802a');

            post('change-password', credentials).then((out) => {
                expect(out.result).to.eq('success');
            });

            cy.request('/AuthProbe/checkPassword/802?password=changed-password').then((res) => {
                expect(JSON.parse(res.body)).to.deep.eq({ found: true, ok: true });
            });
            cy.request('/AuthProbe/checkPassword/802?password=password').then((res) => {
                expect(JSON.parse(res.body)).to.deep.eq({ found: true, ok: false });
            });

            tokenActive('0802a').then((out) => expect(out).to.deep.eq({ exists: true, active: false }));
            tokenActive('0802b').then((out) => expect(out).to.deep.eq({ exists: true, active: false }));
            tokenActive('0802c').then((out) => expect(out).to.deep.eq({ exists: true, active: true }));
        });
    });

    // ---------------------------------------------------------------------
    describe('sessions component', () => {
        it('lists every session with its origin, device and expiry', () => {
            login('0800a');
            cy.visit('/z/profile');

            sessionRow(800)
                .should('contain', 'Cypress Chrome on Linux')
                .and('contain', 'Started')
                .and('contain', 'last seen');

            sessionRow(801)
                .should('contain', 'Laptop at home')
                .and('contain', 'Firefox on Linux')
                .and('contain', 'Started 04.03.2025 08:30 from 203.0.113.11')
                .and('contain', 'never used since')
                .and('contain', 'Expires 02.01.2099 10:15')
                .and('contain', 'Reason: z-admin impersonation');

            // Api keys belong to their own section
            cy.get(`[data-test="session-${uuid(802)}"]`).should('not.exist');
        });

        it('marks the session of this browser', () => {
            login('0800a');
            cy.visit('/z/profile');

            sessionRow(800).find('[data-test="session-current"]').should('be.visible');
            sessionRow(801).find('[data-test="session-current"]').should('not.exist');
        });

        it('renames a session through the modal', () => {
            login('0804a');
            cy.visit('/z/profile');
            cy.intercept('POST', '**/rename-session').as('rename');
            dontFade('session-rename');

            sessionRow(809).find('.z-rename-session').click();
            cy.query('session-rename').should('be.visible');
            cy.query('session-name').should('have.value', 'Tablet in the kitchen');

            cy.query('session-name').clear().type('Workstation');
            cy.query('btn-save-session-name').click();
            cy.wait('@rename');

            sessionRow(809).should('contain', 'Workstation');
        });

        it('falls back to a generic name when the modal is emptied', () => {
            login('0804a');
            cy.visit('/z/profile');
            cy.intercept('POST', '**/rename-session').as('rename');
            dontFade('session-rename');

            sessionRow(809).find('.z-rename-session').click();
            cy.query('session-rename').should('be.visible');

            cy.query('session-name').clear();
            cy.query('btn-save-session-name').click();
            cy.wait('@rename');

            sessionRow(809).find('.z-rename-session').should('have.attr', 'data-name', '');
            sessionRow(809).should('contain', 'Session');
        });

        it('cuts an overlong name to what the column takes', () => {
            login('0804a');

            post('rename-session', { uuid: uuid(809), name: 'N'.repeat(400) }).then((out) => {
                expect(out.result).to.eq('success');
            });

            cy.visit('/z/profile');
            sessionRow(809).find('.z-rename-session')
                .invoke('attr', 'data-name')
                .should('have.length', 255);
        });

        it('revokes another session', () => {
            login('0804a');
            cy.visit('/z/profile');
            cy.intercept('POST', '**/revoke-token').as('revoke');

            sessionRow(810).find('.z-revoke-session').click();
            cy.wait('@revoke');

            sessionRow(810).should('not.exist');
            tokenActive('0804c').then((out) => expect(out).to.deep.eq({ exists: true, active: false }));
        });

        it('clears every session and spares the api keys', () => {
            login('0804a');
            cy.visit('/z/profile');
            cy.intercept('POST', '**/clear-sessions').as('clear');

            cy.query('btn-clear-sessions').click();
            cy.wait('@clear');

            // The reload has nothing left to authenticate with
            cy.query('btn-login').should('be.visible');

            tokenActive('0804a').then((out) => expect(out.active).to.eq(false));
            tokenActive('0804b').then((out) => expect(out.active).to.eq(false));
            tokenActive('0804d').then((out) => expect(out.active).to.eq(true));
        });

        it('revokes the session of this browser', () => {
            login('0808a');
            cy.visit('/z/profile');
            cy.intercept('POST', '**/revoke-token').as('revoke');

            sessionRow(818).find('.z-revoke-session').click();
            cy.wait('@revoke');

            cy.query('btn-login').should('be.visible');
            tokenActive('0808a').then((out) => expect(out.active).to.eq(false));
        });
    });

    // ---------------------------------------------------------------------
    describe('api keys component', () => {
        it('lists an api key with its creation and expiry', () => {
            login('0805a');
            cy.visit('/z/profile');

            apiKeyRow(813)
                .should('contain', 'Reporting job')
                .and('contain', 'Created 06.02.2025 10:00 from 203.0.113.14')
                .and('contain', 'never used since')
                .and('contain', 'Expires 07.06.2099 10:00');
        });

        it('shows an empty state for an account without api keys', () => {
            login('0801a');
            cy.visit('/z/profile');

            cy.query('api-keys-empty').should('be.visible');
        });

        it('creates a key with the chosen lifetime and shows its token once', () => {
            login('0805a');
            cy.visit('/z/profile');
            cy.intercept('POST', '**/create-api-key').as('create');
            dontFade('api-key-created');

            cy.query('api-key-name').type('Deployment pipeline');
            cy.query('api-key-lifetime').select('7');
            cy.query('btn-create-api-key').click();
            cy.wait('@create');

            cy.query('api-key-created').should('be.visible');
            cy.query('api-key-token').invoke('val').should('match', /^zub-[0-9a-f]{64}$/);
            cy.query('api-key-name').should('have.value', '');

            cy.query('btn-api-key-done').click();

            apiKeyList().contains('li', 'Deployment pipeline')
                .should('contain', `Expires ${dayOffset(7)}`)
                .and('contain', 'never used since');
        });

        it('copies the shown token to the clipboard', () => {
            login('0805a');
            cy.visit('/z/profile', {
                onBeforeLoad(win) {
                    cy.stub(win.navigator.clipboard, 'writeText').as('clipboard').resolves();
                },
            });
            cy.intercept('POST', '**/create-api-key').as('create');
            dontFade('api-key-created');

            cy.query('btn-create-api-key').click();
            cy.wait('@create');
            cy.query('api-key-created').should('be.visible');

            cy.query('api-key-token').invoke('val').then((shown) => {
                cy.query('btn-copy-api-key').click();
                cy.get('@clipboard').should('have.been.calledWith', shown);
            });

            cy.query('btn-copy-api-key').should('contain', 'Copied');
        });

        it('creates a key that never expires', () => {
            login('0805a');
            cy.visit('/z/profile');
            cy.intercept('POST', '**/create-api-key').as('create');
            dontFade('api-key-created');

            cy.query('api-key-name').type('Forever');
            cy.query('api-key-lifetime').select('never');
            cy.query('btn-create-api-key').click();
            cy.wait('@create');
            cy.query('api-key-created').should('be.visible');

            cy.query('btn-api-key-done').click();

            apiKeyList().contains('li', 'Forever').should('contain', 'Never expires');
        });

        it('falls back to the shortest lifetime the form offers', () => {
            login('0805a');

            post('create-api-key', { name: 'Unoffered lifetime', lifetime: '9999' }).then((out) => {
                expect(out.result).to.eq('success');
                expect(out.token).to.match(/^zub-/);
            });

            cy.visit('/z/profile');
            apiKeyList().contains('li', 'Unoffered lifetime')
                .should('contain', `Expires ${dayOffset(1)}`);
        });

        it('calls a key without a name an api key', () => {
            login('0805a');

            post('create-api-key', { name: '   ', lifetime: 'never' }).then((out) => {
                expect(out.result).to.eq('success');
            });

            cy.visit('/z/profile');
            apiKeyList().contains('li', 'API key').should('be.visible');
        });

        it('revokes an api key', () => {
            login('0805a');
            cy.visit('/z/profile');
            cy.intercept('POST', '**/revoke-token').as('revoke');

            apiKeyRow(813).find('.z-revoke-api-key').click();
            cy.wait('@revoke');

            apiKeyRow(813).should('not.exist');
            tokenActive('0805b').then((out) => expect(out).to.deep.eq({ exists: true, active: false }));
        });
    });

    // ---------------------------------------------------------------------
    describe('token guards', () => {
        beforeEach(() => {
            login('0807a');
        });

        it('refuses a session of somebody else', () => {
            post('revoke-token', { uuid: uuid(814), type: 'session' }).then(expectUnknownToken);
            tokenActive('0806a').then((out) => expect(out.active).to.eq(true));
        });

        it('refuses an api key of somebody else', () => {
            post('revoke-token', { uuid: uuid(815), type: 'api-key' }).then(expectUnknownToken);
            tokenActive('0806b').then((out) => expect(out.active).to.eq(true));
        });

        it('refuses an unknown uuid', () => {
            post('revoke-token', { uuid: uuid(999), type: 'session' }).then(expectUnknownToken);
        });

        it('refuses a uuid that is not a string', () => {
            post('revoke-token', 'uuid[]=a&type=session').then(expectUnknownToken);
        });

        // The kind picks the lookup and each one only finds its own rows
        it('refuses a session addressed as an api key', () => {
            post('revoke-token', { uuid: uuid(816), type: 'api-key' }).then(expectUnknownToken);
            tokenActive('0807a').then((out) => expect(out.active).to.eq(true));
        });

        it('refuses an api key addressed as a session', () => {
            post('revoke-token', { uuid: uuid(817), type: 'session' }).then(expectUnknownToken);
            tokenActive('0807b').then((out) => expect(out.active).to.eq(true));
        });

        it('refuses a kind the form never offers', () => {
            post('revoke-token', { uuid: uuid(816), type: 'cookie' }).then(expectUnknownToken);
            tokenActive('0807a').then((out) => expect(out.active).to.eq(true));
        });

        it('refuses to rename a session of somebody else', () => {
            post('rename-session', { uuid: uuid(814), name: 'Mine now' }).then(expectUnknownToken);

            login('0806a');
            cy.visit('/z/profile');
            sessionRow(814).should('contain', 'Not yours');
        });

        it('turns a visitor who is not logged in away from every endpoint', () => {
            cy.clearCookie('z_login_token');

            post('revoke-token', { uuid: uuid(816), type: 'session' }).then(expectUnknownToken);
            post('rename-session', { uuid: uuid(816), name: 'Mine now' }).then(expectUnknownToken);

            post('clear-sessions', {}).then((out) => {
                expect(out).to.include({ result: 'error', message: 'Not logged in' });
            });
            post('create-api-key', {}).then((out) => {
                expect(out).to.include({ result: 'error', message: 'Not logged in' });
            });

            tokenActive('0807a').then((out) => expect(out.active).to.eq(true));
        });
    });
});
