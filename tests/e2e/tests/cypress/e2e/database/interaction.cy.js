// Drives src/Database/Interaction.php through DatabaseProbeController.
// The probes hit fixtures defined in migrations/2026-05-12_DatabaseProbe.sql:
//   z_test_grouping: (1,'A',10), (1,'B',20), (2,'C',30)
//   z_test_empty:    no rows

describe('Database/Interaction', () => {
    before(() => cy.dbSeed());

    describe('countTableEntries()', () => {
        it('returns the row count for a populated table', () => {
            cy.request('/DatabaseProbe/countTableEntriesHappy').then((res) => {
                expect(res.body.count).to.eq(3);
            });
        });

        it('returns 0 for an empty table', () => {
            cy.request('/DatabaseProbe/countTableEntriesEmpty').then((res) => {
                expect(res.body.count).to.eq(0);
            });
        });
    });
});
