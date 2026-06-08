// Drives the PasswordHash module through PasswordHashProbeController, via its
// public Password facade. Each probe returns only the Verification fields the
// assertion needs.
//
// Coverage target: every reachable line/branch in Password, Verification and
// LegacyHash.

describe('Authentication/PasswordHash', () => {

    describe('Password::hash()', () => {
        it('produces an argon2id hash that verifies', () => {
            cy.request('/PasswordHashProbe/hashValid').then((res) => {
                expect(res.body.isArgon2id).to.eq(true);
                expect(res.body.verifies).to.eq(true);
            });
        });

        it('throws for a password shorter than the minimum', () => {
            cy.request('/PasswordHashProbe/hashTooShort').then((res) => {
                expect(res.body.threw).to.eq(true);
                expect(res.body.type).to.eq('InvalidArgumentException');
                expect(res.body.message).to.match(/Invalid password length/);
            });
        });

        it('throws for a password longer than the maximum', () => {
            cy.request('/PasswordHashProbe/hashTooLong').then((res) => {
                expect(res.body.threw).to.eq(true);
                expect(res.body.type).to.eq('InvalidArgumentException');
            });
        });
    });

    describe('Password::verify() native', () => {
        it('matches a current-cost hash with no upgrade', () => {
            cy.request('/PasswordHashProbe/verifyNativeMatch').then((res) => {
                expect(res.body.ok).to.eq(true);
                expect(res.body.needsUpgrade).to.eq(false);
            });
        });

        it('rejects a wrong password', () => {
            cy.request('/PasswordHashProbe/verifyNativeMismatch').then((res) => {
                expect(res.body.ok).to.eq(false);
            });
        });

        it('upgrades a below-cost hash on a correct login (rehash-on-login)', () => {
            cy.request('/PasswordHashProbe/verifyNativeRehash').then((res) => {
                expect(res.body.ok).to.eq(true);
                expect(res.body.needsUpgrade).to.eq(true);
            });
        });
    });

    describe('Password::verify() guards', () => {
        it('rejects an empty stored value', () => {
            cy.request('/PasswordHashProbe/verifyEmptyStored').then((res) => {
                expect(res.body.ok).to.eq(false);
            });
        });

        it('rejects a password below the minimum length', () => {
            cy.request('/PasswordHashProbe/verifyPasswordTooShort').then((res) => {
                expect(res.body.ok).to.eq(false);
            });
        });

        it('rejects a password above the maximum length', () => {
            cy.request('/PasswordHashProbe/verifyPasswordTooLong').then((res) => {
                expect(res.body.ok).to.eq(false);
            });
        });

        it('rejects an unknown scheme', () => {
            cy.request('/PasswordHashProbe/verifyUnknownScheme').then((res) => {
                expect(res.body.ok).to.eq(false);
            });
        });
    });

    describe('Password::verify() legacy', () => {
        it('verifies a legacy hash and yields a native upgrade', () => {
            cy.request('/PasswordHashProbe/verifyLegacyMatch').then((res) => {
                expect(res.body.ok).to.eq(true);
                expect(res.body.needsUpgrade).to.eq(true);
            });
        });

        it('rejects a wrong password against a legacy hash', () => {
            cy.request('/PasswordHashProbe/verifyLegacyMismatch').then((res) => {
                expect(res.body.ok).to.eq(false);
            });
        });

        it('throws when a legacy hash has no salt', () => {
            cy.request('/PasswordHashProbe/verifyLegacyMissingSalt').then((res) => {
                expect(res.body.threw).to.eq(true);
                expect(res.body.type).to.eq('InvalidArgumentException');
            });
        });
    });

    describe('Password::verify() onion (+ Password::onionWrap)', () => {
        it('wraps a legacy hash, verifies it, and yields a native upgrade', () => {
            cy.request('/PasswordHashProbe/verifyOnionMatch').then((res) => {
                expect(res.body.wrappedFormat).to.eq(true);
                expect(res.body.ok).to.eq(true);
                expect(res.body.needsUpgrade).to.eq(true);
            });
        });

        it('rejects a wrong password against an onion hash', () => {
            cy.request('/PasswordHashProbe/verifyOnionMismatch').then((res) => {
                expect(res.body.ok).to.eq(false);
            });
        });
    });

    describe('Verification::upgradePassword()', () => {
        it('produces a fresh, current-cost native hash when an upgrade is pending', () => {
            cy.request('/PasswordHashProbe/upgradePassword').then((res) => {
                expect(res.body.isArgon2id).to.eq(true);
                expect(res.body.verifies).to.eq(true);
                expect(res.body.rehashed).to.eq(true);
            });
        });

        it('throws when called with no pending upgrade (misuse)', () => {
            cy.request('/PasswordHashProbe/upgradeWithoutPending').then((res) => {
                expect(res.body.threw).to.eq(true);
                expect(res.body.type).to.eq('LogicException');
                expect(res.body.message).to.match(/No upgrade pending/);
            });
        });
    });

    // Keeps the probe helper at 100%: every other action routes a (sometimes
    // throwing) closure through catchThrowableMessage; this is its no-throw arm.
    describe('probe helper', () => {
        it('reports threw=false when the closure succeeds', () => {
            cy.request('/PasswordHashProbe/catchHelperHappyPath').then((res) => {
                expect(res.body.threw).to.eq(false);
            });
        });
    });
});
