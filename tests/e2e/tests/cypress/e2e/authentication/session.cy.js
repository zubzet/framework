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
            },
            {
                id: 401,
                token: '0400b00000000000000000000000000000000000',
                userId: 400,
                userIdExec: 401,
                extendedSeconds: null,
                created: '2025-01-01 12:00:00',
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