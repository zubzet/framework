describe('Authentication - Session', () => {
    before(() => {
        cy.dbSeed();
    });

    function requestJson(path) {
        return cy.request(path).then((res) => JSON.parse(res.body));
    }


    /**
     * Getters
     */

    it('should return only active sessions for a user (byUser)', () => {
        const expected = [
            {
                id: 400,
                token: '0400a00000000000000000000000000000000000',
                userId: 400,
                userIdExec: 400,
                extendedSeconds: null,
                created: '2025-01-01 12:00:00',
                name: null,
                isPermanent: false,
                isApiKey: false,
            },
            {
                id: 401,
                token: '0400b00000000000000000000000000000000000',
                userId: 400,
                userIdExec: 401,
                extendedSeconds: null,
                created: '2025-01-01 12:00:00',
                name: null,
                isPermanent: false,
                isApiKey: false,
            },
        ];

        const shouldNotInclude = {
            id: 402,
            token: '0400c00000000000000000000000000000000000',
        };

        requestJson('/session/byUser').then((output) => {
            expect(output).to.have.length(expected.length);

            expected.forEach(session => {
                expect(output).to.deep.include(session);
            });

            output.forEach(session => {
                expect(session.token).to.not.equal(shouldNotInclude.token);
                expect(session.id).to.not.equal(shouldNotInclude.id);
            });
        });
    });

    it('should return correct values for all getters (token, userId, userIdExec, extendedSeconds, created)', () => {
        requestJson('/session/getters').then((output) => {
            expect(output).to.deep.equal({
                id: 404,
                token: '0402a00000000000000000000000000000000000',
                userId: 402,
                userIdExec: 402,
                extendedSeconds: 300,
                created: '2025-01-01 12:00:00',
                name: null,
                isPermanent: false,
                isApiKey: false,
            });
        });
    });


    /**
     * byToken
     */

    it('should return a session by its token (byToken)', () => {
        requestJson('/session/byToken').then((output) => {
            expect(output).to.deep.equal({
                id: 420,
                token: '0420a00000000000000000000000000000000000',
                userId: 420,
                userIdExec: 420,
                extendedSeconds: null,
                created: output.created,
                name: null,
                isPermanent: false,
                isApiKey: false,
            });
        });
    });

    it('should return null for an inactive token (byToken)', () => {
        requestJson('/session/byTokenInactive').then((output) => {
            expect(output).to.deep.equal({ found: false });
        });
    });

    it('should return null for a non-existent token (byToken)', () => {
        requestJson('/session/byTokenNotFound').then((output) => {
            expect(output).to.deep.equal({ found: false });
        });
    });


    /**
     * add
     */

    it('should create a new session and return it (add)', () => {
        requestJson('/session/add').then((output) => {
            expect(output.userId).to.equal(421);
            expect(output.userIdExec).to.equal(421);
            expect(output.extendedSeconds).to.be.null;
            expect(output.token).to.be.a('string').and.have.length(40);
        });
    });

    it('should set userIdExec separately when provided (add with exec)', () => {
        requestJson('/session/addWithExec').then((output) => {
            expect(output.userId).to.equal(422);
            expect(output.userIdExec).to.equal(423);
            expect(output.token).to.be.a('string').and.have.length(40);
        });
    });

    it('should store the name passed as the third argument (add with name)', () => {
        requestJson('/session/addWithName').then((output) => {
            expect(output.userId).to.equal(434);
            expect(output.name).to.equal('Named session');
            // A named session is still an ordinary, expiring login
            expect(output.isApiKey).to.be.false;
            expect(output.isPermanent).to.be.false;
        });
    });


    /**
     * Interactions
     */

    it('should invalidate a session so it no longer appears in byUser', () => {
        requestJson('/session/invalidate').then((output) => {
            expect(output).to.deep.equal({
                beforeCount: 1,
                afterCount: 0,
            });
        });
    });

    it('should set the extension time of a session (setExtensionTime)', () => {
        requestJson('/session/setExtensionTime').then((output) => {
            expect(output).to.deep.equal({
                extendedSeconds: 3600,
            });
        });
    });

    it('should extend a session cumulatively (extendSession)', () => {
        requestJson('/session/extendSession').then((output) => {
            expect(output).to.deep.equal({
                before: 100,
                after: 300,
            });
        });
    });

    it('should reload updated session data from the database after refresh (refresh)', () => {
        requestJson('/session/refresh').then((output) => {
            expect(output).to.deep.equal({
                before: null,
                after: 999,
            });
        });
    });


    /**
     * Business Logic
     */

    it('should report a recently created session as not expired (isExpired)', () => {
        requestJson('/session/isExpiredActive').then((output) => {
            expect(output).to.deep.equal({
                isExpired: false,
            });
        });
    });

    it('should report an old session without extension as expired (isExpired)', () => {
        requestJson('/session/isExpiredExpired').then((output) => {
            expect(output).to.deep.equal({
                isExpired: true,
            });
        });
    });

    it('should report an old session as not expired when the extension covers the gap (isExpired)', () => {
        requestJson('/session/isExpiredExtended').then((output) => {
            expect(output).to.deep.equal({
                isExpired: false,
            });
        });
    });


    /**
     * API Keys
     */

    it('should return every session, only api keys, or only logins (byUser filter)', () => {
        requestJson('/session/apiKeyFilter').then((output) => {
            // 433 is a revoked api key and must not show up anywhere
            expect(output.all).to.have.members([430, 431, 432]);
            expect(output.apiKeys).to.have.members([431, 432]);
            expect(output.logins).to.have.members([430]);
        });
    });

    it('should set name, permanent and api key flags through the session object', () => {
        requestJson('/session/apiKeyManage').then((output) => {
            expect(output.before).to.deep.equal({
                name: null,
                isPermanent: false,
                isApiKey: false,
            });

            const expected = {
                name: 'Renamed key',
                isPermanent: true,
                isApiKey: true,
            };

            // Refreshed in place and read back from the database
            expect(output.after).to.deep.equal(expected);
            expect(output.stored).to.deep.equal(expected);
        });
    });

    it('should remove the name of a session when null is passed (setName)', () => {
        requestJson('/session/apiKeyClearName').then((output) => {
            expect(output).to.deep.equal({
                before: 'Temporary name',
                after: null,
            });
        });
    });

    it('should never expire a permanent session and expire it again once the flag is dropped', () => {
        requestJson('/session/apiKeyPermanentExpiry').then((output) => {
            // Created in the year 2000 without extension - only the flag keeps it alive
            expect(output.permanent).to.deep.equal({
                isExpired: false,
                expiresAt: null,
            });

            expect(output.temporary.isExpired).to.be.true;
            expect(output.temporary.expiresAt).to.be.a('string');
        });
    });

    it('should authenticate a request that carries a permanent api key as cookie', () => {
        // Session 436, user 433: created in the year 2000, permanent api key
        cy.session('auth_apikey_433', () => {
            cy.setCookie('z_login_token', '0433a00000000000000000000000000000000000');
        });
        requestJson('/session/whoami').then((output) => {
            expect(output.isLoggedIn).to.be.true;
            expect(output.userId).to.equal(433);
        });
    });


    /**
     * Session Naming from the User Agent
     */

    const CHROME_ON_WINDOWS = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    function login(userAgent) {
        return cy.request({
            method: 'POST',
            url: '/login',
            form: true,
            body: {
                name: 'session_login_name@cypress.test',
                password: 'password',
            },
            headers: { 'user-agent': userAgent },
        }).then((res) => {
            expect(res.body).to.include('success');
            // The login left a cookie behind; the auth flow tests set their own
            cy.clearCookie('z_login_token');
        });
    }

    it('should name a login session after the user agent it was started from', () => {
        login(CHROME_ON_WINDOWS);

        requestJson('/session/loginSessionNames').then((output) => {
            expect(output.names).to.deep.equal([CHROME_ON_WINDOWS]);
        });
    });

    it('should cut an overlong user agent to what the column takes', () => {
        // The header is client controlled and `name` holds 255 characters, so an
        // oversized one must not break the login.
        const overlong = 'Mozilla/5.0 ' + 'A'.repeat(500);
        login(overlong);

        requestJson('/session/loginSessionNames').then((output) => {
            // byUser has no ORDER BY, so pick the row out instead of taking the last
            const stored = output.names.filter((name) => name.startsWith('Mozilla/5.0 A'));
            expect(stored).to.have.length(1);
            expect(stored[0]).to.have.length(255);
            expect(overlong.startsWith(stored[0])).to.be.true;
        });
    });


    /**
     * Authentication Flow (cookie-based login)
     *
     * These tests verify that the z_login_token cookie is validated correctly
     * across all relevant session states. Nothing must break.
     *
     * cy.session() is used (the same mechanism as cy.loginAs()) to reliably
     * associate the cookie with the correct browser origin before cy.request().
     */

    it('should authenticate a request that carries a valid session cookie', () => {
        // Session 411, user 409 — active, recently created
        cy.session('auth_valid_409', () => {
            cy.setCookie('z_login_token', '0409a00000000000000000000000000000000000');
        });
        requestJson('/session/whoami').then((output) => {
            expect(output.isLoggedIn).to.be.true;
            expect(output.userId).to.equal(409);
        });
    });

    it('should reject a request whose session was explicitly invalidated', () => {
        // Establish the cookie for session 412, then invalidate it server-side.
        // All subsequent requests in this test carry the same (now dead) cookie.
        cy.session('auth_invalidated_410', () => {
            cy.setCookie('z_login_token', '0410a00000000000000000000000000000000000');
        });
        // Invalidates session 412 in the DB (active → 0)
        requestJson('/session/invalidateForAuth');
        // Cookie is still set but the token is inactive → rejected
        requestJson('/session/whoami').then((output) => {
            expect(output.isLoggedIn).to.be.false;
            expect(output.userId).to.be.null;
        });
    });

    it('should reject a request that carries an expired session cookie', () => {
        // Session 413: created year 2000, no extension — long expired
        cy.session('auth_expired_411', () => {
            cy.setCookie('z_login_token', '0411a00000000000000000000000000000000000');
        });
        requestJson('/session/whoami').then((output) => {
            expect(output.isLoggedIn).to.be.false;
            expect(output.userId).to.be.null;
        });
    });

    it('should authenticate a request whose session is kept alive by a large extension', () => {
        // Session 414: created year 2000, extended_seconds = 2000000000 (~63 years total)
        // (created + defaultLifetime + extension) > time() → still valid
        cy.session('auth_extended_412', () => {
            cy.setCookie('z_login_token', '0412a00000000000000000000000000000000000');
        });
        requestJson('/session/whoami').then((output) => {
            expect(output.isLoggedIn).to.be.true;
            expect(output.userId).to.equal(412);
        });
    });

    it('should not be possible to revive an expired session by extending it after expiry', () => {
        // Session 415 (user 413): expired (created 2000, no extension), active=1 in DB at start.
        cy.session('auth_revive_413', () => {
            cy.setCookie('z_login_token', '0413a00000000000000000000000000000000000');
        });

        // Step 1: Use the expired cookie.
        //   validateCookie rejects it AND calls invalidateSession() → active becomes 0.
        requestJson('/session/whoami').then((firstAttempt) => {
            expect(firstAttempt.isLoggedIn).to.be.false;

            // Step 2: Try to retrieve session 415 server-side.
            //   Session::byId(415) uses WHERE active=1 → returns null because active=0.
            //   Extension is impossible; the session stays permanently dead.
            requestJson('/session/extendAfterExpire').then((extendResult) => {
                expect(extendResult.sessionFoundAfterExpiry).to.be.false;

                // Step 3: Use the same cookie one more time.
                //   active=0 in DB → still rejected.
                requestJson('/session/whoami').then((secondAttempt) => {
                    expect(secondAttempt.isLoggedIn).to.be.false;
                    expect(secondAttempt.userId).to.be.null;
                });
            });
        });
    });
});