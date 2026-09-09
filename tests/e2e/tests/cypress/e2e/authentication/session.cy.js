describe('Authentication - Session', () => {
    before(() => {
        cy.dbSeed();
    });

    function requestJson(path) {
        return cy.request(path).then((res) => JSON.parse(res.body));
    }

    // Issued tokens are a `zub-` prefix plus 32 random bytes in hex
    const TOKEN_FORMAT = /^zub-[0-9a-f]{64}$/;


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
                device: null,
                reason: null,
                canExpire: true,
            },
            {
                id: 401,
                token: '0400b00000000000000000000000000000000000',
                userId: 400,
                userIdExec: 401,
                extendedSeconds: null,
                created: '2025-01-01 12:00:00',
                name: null,
                device: null,
                reason: null,
                canExpire: true,
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
                device: null,
                reason: null,
                canExpire: true,
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
                device: null,
                reason: null,
                canExpire: true,
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
            expect(output.token).to.match(TOKEN_FORMAT);
        });
    });

    it('should set userIdExec separately when provided (add with exec)', () => {
        requestJson('/session/addWithExec').then((output) => {
            expect(output.userId).to.equal(422);
            expect(output.userIdExec).to.equal(423);
            expect(output.token).to.match(TOKEN_FORMAT);
        });
    });

    it('should store the name passed as the third argument (add with name)', () => {
        requestJson('/session/addWithName').then((output) => {
            expect(output.userId).to.equal(434);
            expect(output.name).to.equal('Named session');
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


    it('should exempt an ordinary login from expiring, reviving an expired session', () => {
        requestJson('/session/sessionPermanent').then((output) => {
            // Session 438 is a login created in the year 2000, without extension
            expect(output.before.canExpire).to.be.true;
            expect(output.before.isExpired).to.be.true;
            expect(output.before.expiresAt).to.be.a('string');

            expect(output.after).to.deep.equal({
                isExpired: false,
                expiresAt: null,
                canExpire: false,
            });
        });
    });


    /**
     * A fixed expiry
     */

    it('should let a fixed expiry outrank the computed lifetime', () => {
        requestJson('/session/expiresAtFixed').then((output) => {
            // Session 440 was created in the year 2000, so only the date keeps it alive
            expect(output).to.deep.equal({
                isExpired: false,
                expiresAt: '2099-01-01 12:00:00',
                canExpire: true,
            });
        });
    });

    it('should ignore the timeout and the extension once an expiry is fixed', () => {
        requestJson('/session/expiresAtOverrules').then((output) => {
            // Session 441 was created now and extended by ~63 years on top
            expect(output.extendedSeconds).to.equal(2000000000);

            expect(output.expiresAt).to.equal('2000-01-01 12:00:00');
            expect(output.isExpired).to.be.true;
        });
    });

    it('should fix and drop the expiry through the session object (setExpiresAt)', () => {
        requestJson('/session/setExpiresAt').then((output) => {
            // Session 442 is a login from the year 2000 without an extension
            expect(output.before.isExpired).to.be.true;
            expect(output.before.expiresAt).to.be.a('string');

            expect(output.fixed).to.deep.equal({
                isExpired: false,
                expiresAt: '2099-01-01 12:00:00',
                canExpire: true,
            });

            // Dropping it hands the session back to created + timeout
            expect(output.dropped).to.deep.equal(output.before);
        });
    });

    it('should keep a session exempt from expiring despite a fixed expiry', () => {
        requestJson('/session/expiresAtPermanent').then((output) => {
            // Being exempt outranks the date - the session never becomes unusable
            expect(output).to.deep.equal({
                isExpired: false,
                expiresAt: null,
                canExpire: false,
            });
        });
    });

    it('should reject a request whose session is past its fixed expiry', () => {
        // Session 444, user 444: created now, so only the fixed expiry rejects it
        cy.session('auth_expires_at_444', () => {
            cy.setCookie('z_login_token', '0444a00000000000000000000000000000000000');
        });
        requestJson('/session/whoami').then((output) => {
            expect(output.isLoggedIn).to.be.false;
            expect(output.userId).to.be.null;
        });
    });


    it('should record name, reason, device and the creating address (add)', () => {
        requestJson('/session/sessionOrigin').then((output) => {
            expect(output.name).to.equal('Named');
            expect(output.reason).to.equal('Support request #42');
            // Taken from the request that created the session, not passed in
            expect(output.device).to.equal(output.requestAgent);
            expect(output.ipCreation).to.be.a('string').and.not.be.empty;
            // Never used yet, so there is no last address
            expect(output.ipLast).to.be.null;
        });
    });

    it('should update the last address when a session is used from a new one', () => {
        // Session 439 is seeded with 203.0.113.7, which cypress is not
        cy.session('ip_last_439', () => {
            cy.setCookie('z_login_token', '0439a00000000000000000000000000000000000');
        });
        requestJson('/session/whoami').then((who) => {
            expect(who.isLoggedIn).to.be.true;

            requestJson('/session/sessionIpLast').then((output) => {
                expect(output.ipLast).to.not.equal('203.0.113.7');
                expect(output.ipLast).to.equal(output.requestIp);
            });
        });
    });


    /**
     * API Keys
     */

    it('should list logins and api keys of a user separately (byUser)', () => {
        requestJson('/session/apiKeyByUser').then((output) => {
            // 433 is a revoked api key and must not show up anywhere
            expect(output.logins).to.have.members([430]);
            expect(output.apiKeys).to.have.members([431, 432]);
        });
    });

    it('should find a session only through the class of its kind (byId)', () => {
        requestJson('/session/apiKeyById').then((output) => {
            expect(output).to.deep.equal({
                sessionOnLogin: 'Session',
                sessionOnKey: null,
                apiKeyOnKey: 'APIKey',
                apiKeyOnLogin: null,
            });
        });
    });

    it('should build the class matching the row a token belongs to (byToken)', () => {
        requestJson('/session/apiKeyByToken').then((output) => {
            expect(output).to.deep.equal({
                // The row picks the class, not the class that was asked. Both
                // resolve both kinds, which is what authentication needs.
                sessionOnLogin: 'Session',
                sessionOnKey: 'APIKey',
                apiKeyOnLogin: 'Session',
                apiKeyOnKey: 'APIKey',
            });
        });
    });

    it('should create a named api key (add)', () => {
        requestJson('/session/apiKeyAdd').then((output) => {
            expect(output.class).to.equal('APIKey');
            expect(output.userId).to.equal(437);
            expect(output.name).to.equal('Deployment pipeline');
            expect(output.reason).to.equal('CI needs read access');
            expect(output.token).to.match(TOKEN_FORMAT);
            // A fresh key expires like a login until it is exempted
            expect(output.canExpire).to.be.true;
        });
    });

    it('should set name and expiry exemption through the api key object', () => {
        requestJson('/session/apiKeyManage').then((output) => {
            expect(output.before).to.deep.equal({
                name: null,
                canExpire: true,
            });

            const expected = {
                name: 'Renamed key',
                canExpire: false,
            };

            // Refreshed in place and read back from the database
            expect(output.after).to.deep.equal(expected);
            expect(output.stored).to.deep.equal(expected);
        });
    });

    it('should remove the name of an api key when null is passed (setName)', () => {
        requestJson('/session/apiKeyClearName').then((output) => {
            expect(output).to.deep.equal({
                before: 'Temporary name',
                after: null,
            });
        });
    });

    it('should never expire an exempt api key and expire it again once that is dropped', () => {
        requestJson('/session/apiKeyPermanentExpiry').then((output) => {
            // Created in the year 2000 without extension - only the exemption keeps it alive
            expect(output.exempt).to.deep.equal({
                isExpired: false,
                expiresAt: null,
                canExpire: false,
            });

            expect(output.expiring.isExpired).to.be.true;
            expect(output.expiring.expiresAt).to.be.a('string');
            expect(output.expiring.canExpire).to.be.true;
        });
    });

    it('should authenticate a request that carries an exempt api key as cookie', () => {
        // Session 436, user 433: created in the year 2000, exempt from expiring
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

    it('should record the user agent as the device, leaving the name unset', () => {
        login(CHROME_ON_WINDOWS);

        requestJson('/session/loginSessionNames').then((output) => {
            expect(output).to.have.length(1);
            expect(output[0].device).to.equal(CHROME_ON_WINDOWS);
            // The user agent is the device, not the name - a name is chosen, not sniffed
            expect(output[0].name).to.be.null;
            expect(output[0].reason).to.be.null;
            // loginAs() records where the session was created from
            expect(output[0].ipCreation).to.be.a('string').and.not.be.empty;
        });
    });

    it('should cut an overlong user agent to what the column takes', () => {
        // The header is client controlled and `device` holds 255 characters, so an
        // oversized one must not break the login.
        const overlong = 'Mozilla/5.0 ' + 'A'.repeat(500);
        login(overlong);

        requestJson('/session/loginSessionNames').then((output) => {
            // byUser has no ORDER BY, so pick the row out instead of taking the last
            const stored = output
                .map((session) => session.device)
                .filter((device) => device.startsWith('Mozilla/5.0 A'));
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