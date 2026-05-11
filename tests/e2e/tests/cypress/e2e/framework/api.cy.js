// Probes for public framework APIs (z_generalModel, z_userModel) that no
// internal framework controller exercises end-to-end. Pattern follows
// permission/user.cy.js — one route per scenario, deep.equal on JSON.

describe('Framework API probes', () => {
    before(() => {
        cy.dbSeed();
    });

    function requestJson(path) {
        return cy.request(path).then((res) => JSON.parse(res.body));
    }

    // -----------------------------------------------------------------------
    describe('z_generalModel — languages', () => {
        it('getLanguageList returns the seeded languages', () => {
            requestJson('/FrameworkApi/languageList').then((rows) => {
                // Framework seed has at least EN and DE.
                const values = rows.map((r) => r.value);
                expect(values).to.include('EN');
                expect(values).to.include('DE');
            });
        });

        it('getLanguageByValue returns the right id for known values', () => {
            requestJson('/FrameworkApi/languageByValueKnown').then((out) => {
                expect(out.EN).to.eq(1);
                expect(out.DE).to.eq(2);
            });
        });

        it('getLanguageByValue falls back to the default for unknown values', () => {
            requestJson('/FrameworkApi/languageByValueUnknown').then((out) => {
                // Default (no override) is 1 per signature.
                expect(out.unknownDefault).to.eq(1);
                // Explicit override is honoured.
                expect(out.unknownExplicit).to.eq(7);
            });
        });
    });

    // -----------------------------------------------------------------------
    describe('z_userModel — role helpers', () => {
        it('getRoleIdByRoleName returns the id for known roles, null for unknown', () => {
            requestJson('/FrameworkApi/roleIdByName').then((out) => {
                expect(out.fwapi_KnownRole).to.eq(251);
                expect(out.no_such_role).to.eq(null);
            });
        });

        it('changeRoleStateByUserIdAndRoleId(true) grants the role and is idempotent', () => {
            requestJson('/FrameworkApi/changeRoleStateAdd').then((out) => {
                expect(out.hasBefore).to.eq(false);
                expect(out.hasAfter).to.eq(true);
                // A second add should NOT create a duplicate active row.
                expect(out.countAfterDoubleAdd).to.eq(1);
            });
        });

        it('changeRoleStateByUserIdAndRoleId(false) revokes a previously granted role', () => {
            requestJson('/FrameworkApi/changeRoleStateRemove').then((out) => {
                expect(out.hasBefore).to.eq(true);
                expect(out.hasAfter).to.eq(false);
            });
        });
    });
});
