// Framework tables have to share the collation of z_user (#184): a table
// created under another default collation breaks the UNION of the
// permission lookup on every authenticated request.
describe('Framework table collation', () => {
    before(() => cy.dbSeed());

    it('creates every framework table with the collation of z_user', () => {
        cy.request('/CollationProbe/state').its('body').then((state) => {
            expect(state.misaligned).to.deep.equal([]);
            expect(state.permissions).to.eq('ok');
        });
    });

    it('aligns a framework table with another collation on migrate', () => {
        cy.request('/CollationProbe/misalign').its('body').then((state) => {
            expect(state.misaligned).to.deep.equal(['z_user_permission']);
            expect(state.permissions).to.match(/Illegal mix of collations/);
        });

        cy.exec('docker exec application php index.php db:migrate');

        cy.request('/CollationProbe/state').its('body').then((state) => {
            expect(state.misaligned).to.deep.equal([]);
            expect(state.permissions).to.eq('ok');
        });
    });
});
