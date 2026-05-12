// Coverage for src/IncludedComponents/controllers/LoginController.php beyond the
// happy path of action_index already covered in account/login.cy.js.
//
// One spec, one describe per action. Seed range 600-620, see AuthFlows.sql.

describe('Auth flows', () => {
    before(() => {
        cy.dbSeed();
        cy.saveConfigBackup();
    });

    after(() => {
        cy.restoreConfigBackup();
    });

    function requestJson(path) {
        return cy.request(path).then((res) => JSON.parse(res.body));
    }

    function clearMailhog() {
        cy.request({
            method: 'DELETE',
            url: 'http://localhost:3300/api/v1/messages',
            failOnStatusCode: false,
        });
    }

    function fetchLatestMail() {
        return cy.request('http://localhost:3300/api/messages').then((res) => {
            const body = typeof res.body === 'string' ? JSON.parse(res.body) : res.body;
            return body.results[0];
        });
    }

    // ---------------------------------------------------------------------
    describe('action_logout', () => {
        it('invalidates the active session token', () => {
            const token = '0600a00000000000000000000000000000000000';

            requestJson(`/AuthProbe/tokenActive/${token}`).then((before) => {
                expect(before).to.deep.equal({ exists: true, active: true });
            });

            cy.setCookie('z_login_token', token);
            cy.request('/login/logout');

            requestJson(`/AuthProbe/tokenActive/${token}`).then((after) => {
                expect(after).to.deep.equal({ exists: true, active: false });
            });
        });

        // Response::logout() early-exits via rerouteUrl() when no user is
        // logged in - covers the `if(!$user->isLoggedIn)` branch and
        // avoids touching the session-invalidation path.
        it('reroutes (without crashing) when no user is logged in', () => {
            cy.clearCookie('z_login_token');
            cy.request({
                url: '/login/logout',
                followRedirect: false,
            }).then((res) => {
                expect(res.status).to.be.oneOf([301, 302]);
                expect(res.headers).to.have.property('location');
            });
        });
    });

    // ---------------------------------------------------------------------
    describe('action_forgot_password', () => {
        it('writes a reset code, sends an email, and returns success JSON', () => {
            clearMailhog();

            cy.request({
                method: 'POST',
                url: '/login/forgot-password/check',
                form: true,
                body: { unameemail: 'auth_forgot@cypress.test' },
            }).then((res) => {
                expect(res.status).to.eq(200);
                expect(JSON.parse(res.body).result).to.eq('success');
            });

            // A row is in z_password_reset for user 601, with reason 'forgot'.
            requestJson('/AuthProbe/lastResetCode/601').then((row) => {
                expect(row.found).to.eq(true);
                expect(row.userId).to.eq(601);
                expect(row.reason).to.eq('forgot');
                expect(row.code).to.match(/^ZIT-/);
            });

            // And the password-reset email landed in mailhog.
            fetchLatestMail().then((mail) => {
                expect(mail.to[0]).to.eq('auth_forgot@cypress.test');
                expect(JSON.stringify(mail.subject).toLowerCase())
                    .to.match(/password reset|passwort/);
            });
        });

        it('returns "error" JSON for an unknown email but still 200', () => {
            cy.request({
                method: 'POST',
                url: '/login/forgot-password/check',
                form: true,
                body: { unameemail: 'no-such-user@cypress.test' },
            }).then((res) => {
                expect(res.status).to.eq(200);
                expect(JSON.parse(res.body).result).to.eq('error');
            });
        });

        it('renders the forgot-password form when no action posted', () => {
            cy.visit('/login/forgot-password');
            cy.contains('Forgot password');
        });
    });

    // ---------------------------------------------------------------------
    describe('action_reset', () => {
        it('consumes a reset code, updates the password, and disables the code', () => {
            // Step 1: kick off forgot-password to create a reset code for user 602.
            cy.request({
                method: 'POST',
                url: '/login/forgot-password/check',
                form: true,
                body: { unameemail: 'auth_reset@cypress.test' },
            });

            // Step 2: pull the code from the DB via probe.
            requestJson('/AuthProbe/lastResetCode/602').then((row) => {
                expect(row.found).to.eq(true);
                const code = row.code;

                // Step 3: GET the reset form (renders the template; doesn't change state).
                cy.request(`/login/reset/${code}/`).then((res) => {
                    expect(res.status).to.eq(200);
                    expect(res.body).to.include('Password reset');
                });

                // Step 4: POST the new password.
                cy.request({
                    method: 'POST',
                    url: `/login/reset/${code}/`,
                    form: true,
                    body: { password: 'newpassword' },
                }).then((res) => {
                    expect(res.status).to.eq(200);
                });

                // Step 5: new password works.
                requestJson('/AuthProbe/checkPassword/602?password=newpassword').then((check) => {
                    expect(check).to.deep.equal({ found: true, ok: true });
                });

                // Step 6: old password no longer works.
                requestJson('/AuthProbe/checkPassword/602?password=password').then((check) => {
                    expect(check).to.deep.equal({ found: true, ok: false });
                });

                // Step 7: reset code is now inactive - no active row for user 602.
                requestJson('/AuthProbe/lastResetCode/602').then((after) => {
                    expect(after.found).to.eq(false);
                });
            });
        });

        it.skip('invalid reset code dies with a bare-text error [skip: die() makes assertions fragile, no error template yet]', () => {
            // The action calls die("ERROR: ...") when the code is invalid, which
            // is awkward to test reliably and surfaces a UX gap (no error
            // template). Revisit together.
        });
    });

    // ---------------------------------------------------------------------
    describe('action_verify', () => {
        it('consumes a valid email-verify token and marks the user verified', () => {
            // Pre-seeded token for user 603 (see AuthFlows.sql).
            const token = 'verify-token-603-active';

            requestJson('/AuthProbe/userVerified/603').then((before) => {
                expect(before.found).to.eq(true);
                expect(before.verified).to.eq(null);
            });

            cy.request(`/login/verify/${token}`).then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body).to.include('Email verification');
            });

            requestJson('/AuthProbe/userVerified/603').then((after) => {
                expect(after.found).to.eq(true);
                expect(after.verified).to.not.eq(null);
            });
        });

        it('resend path: POSTing email re-issues a verify token and renders the wait page', () => {
            clearMailhog();

            cy.request({
                method: 'POST',
                url: '/login/verify',
                form: true,
                body: { email: 'auth_verify@cypress.test' },
            }).then((res) => {
                expect(res.status).to.eq(200);
                // Renders login_verify_wait.php
                expect(res.body).to.include('Email verification');
            });

            fetchLatestMail().then((mail) => {
                expect(mail.to[0]).to.eq('auth_verify@cypress.test');
            });
        });

        // Failure branch of login_verify.php: when verifyUser returns false
        // ($success=false), the template renders the resend-prompt section
        // instead of the success line.
        it('renders the resend prompt when the verify code is invalid', () => {
            cy.request('/login/verify/code-that-does-not-exist').then((res) => {
                expect(res.status).to.eq(200);
                // Framework login_verify.php - English resend prompt.
                expect(res.body).to.include('You are missing the verification mail');
            });
        });
    });

    // ---------------------------------------------------------------------
    describe('action_index - rate limiting', () => {
        it('blocks login after configured threshold and emails a security alert', () => {
            cy.setConfigSetting('maxLoginTriesPerTimespan', '2');
            clearMailhog();

            const submit = (password) => cy.request({
                method: 'POST',
                url: '/login',
                form: true,
                failOnStatusCode: false,
                body: {
                    name: 'auth_ratelimit@cypress.test',
                    password,
                },
            });

            // The check is `count > maxLoginTriesPerTimespan`, and a failed
            // attempt only records its `z_logintry` row AFTER the password
            // check. So with threshold=2: attempts 1-3 see counts 0,1,2 (all
            // not >2 yet) and report wrong-password; attempt 4 sees count=3
            // and trips the rate-limit branch.
            submit('nope-1').then((res) => expect(res.body).to.include('Username or password is wrong'));
            submit('nope-2').then((res) => expect(res.body).to.include('Username or password is wrong'));
            submit('nope-3').then((res) => expect(res.body).to.include('Username or password is wrong'));

            submit('nope-4').then((res) => {
                expect(res.body).to.include('Too many login tries');
            });

            // Security alert email arrived.
            fetchLatestMail().then((mail) => {
                expect(mail.to[0]).to.eq('auth_ratelimit@cypress.test');
                expect(JSON.stringify(mail.subject).toLowerCase())
                    .to.match(/security alert|sicherheits/);
            });
        });
    });

    // ---------------------------------------------------------------------
    describe('action_create_password / action_change_password', () => {
        it.skip('action_create_password reroutes to /login/reset [skip: stub reroutes to /login/reset which dies without a code; expected behavior unclear without design doc]', () => {
            // See DEAD_CODE_CANDIDATES.md - these two actions are stubs that
            // forward to /login/reset which dies if no code is in the URL.
            // Intent (when do they make sense, with what setup?) needs a docs
            // pass before the test is meaningful.
        });

        it.skip('action_change_password reroutes to /login/reset [skip: same as create_password]', () => {});
    });
});
