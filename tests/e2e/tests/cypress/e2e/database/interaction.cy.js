// Drives src/Database/Interaction.php through DatabaseProbeController.
// The probes hit fixtures defined in migrations/2026-05-12_DatabaseProbe.sql:
//   z_test_grouping: (1,'A',10), (1,'B',20), (2,'C',30)
//   z_test_empty:    no rows

describe('Database/Interaction', () => {
    before(() => cy.dbSeed());

    // mergeAsGroup($groupBy): group_id=1 has two rows (new-key + existing-key
    // branches), group_id=2 has one (new-key only). Confirms the foreach
    // builds nested arrays keyed by groupBy with every full row appended.
    describe('mergeAsGroup($groupBy)', () => {
        it('groups rows by the column, full assoc per entry', () => {
            cy.request('/DatabaseProbe/mergeAsGroupBasic').then((res) => {
                expect(Object.keys(res.body)).to.have.members(['1', '2']);
                expect(res.body['1']).to.have.lengthOf(2);
                expect(res.body['2']).to.have.lengthOf(1);
                const labels1 = res.body['1'].map((r) => r.label);
                expect(labels1).to.have.members(['A', 'B']);
                expect(res.body['2'][0].label).to.eq('C');
                expect(res.body['2'][0].val).to.eq(30);
            });
        });

        it('single-group result still constructs the new-key branch correctly', () => {
            cy.request('/DatabaseProbe/mergeAsGroupSingleGroup').then((res) => {
                expect(res.body).to.have.all.keys('2');
                expect(res.body['2']).to.have.lengthOf(1);
                expect(res.body['2'][0].label).to.eq('C');
            });
        });

        it('empty result returns an empty object', () => {
            cy.request('/DatabaseProbe/mergeAsGroupEmpty').then((res) => {
                // PHP json_encodes empty associative arrays as [].
                expect(res.body).to.be.empty;
            });
        });
    });

    // mergeAsGroup($groupBy, $subElement) collects ONLY that field per
    // group instead of the full row. The two branches inside the foreach
    // (new key + existing key) both append $element[$subElement].
    describe('mergeAsGroup($groupBy, $subElement)', () => {
        it('collects the sub-element values keyed by group', () => {
            cy.request('/DatabaseProbe/mergeAsGroupSubElement').then((res) => {
                expect(res.body).to.deep.equal({
                    1: [10, 20],
                    2: [30],
                });
            });
        });
    });

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
