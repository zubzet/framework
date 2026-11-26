// $opt inside components, driven by ComponentNamespaceController and the views
// renderprobe/standard-opt and renderprobe/reserved-opt.
//
// Every component has the framework's standard $opt (root, user, ...) without being
// handed anything, nested ones too; what the controller passed to render() is not in
// it. In return the "opt" attribute is reserved: an app component that is handed the
// whole $opt (<x-alert :opt="$opt"/>) fails the render. The framework's zubzet::
// components are exempt, which component-namespace.cy.js covers by rendering
// <x-zubzet::head :opt="$opt"/>.
//
// Stateless render: no cy.dbSeed().

describe('Component $opt', () => {
    describe('standard $opt', () => {
        beforeEach(() => {
            cy.visit('/ComponentNamespace/standardOpt');
        });

        it('is available in a component without an attribute', () => {
            cy.get('[data-test="view-root"]').invoke('text').then((root) => {
                expect(root).to.not.be.empty;
                cy.get('[data-test="outer-root"]').should('have.text', root);
            });
            cy.get('[data-test="outer-user"]').should('have.text', 'USER');
        });

        it('is available in a nested component', () => {
            cy.get('[data-test="view-root"]').invoke('text').then((root) => {
                cy.get('[data-test="inner-root"]').should('have.text', root);
            });
        });

        it('does not include what the controller passed to render()', () => {
            cy.get('[data-test="view-caller"]').should('have.text', 'CALLER_VALUE');
            cy.get('[data-test="outer-caller"]').should('have.text', 'NOT_SHARED');
        });
    });

    it('refuses an app component that is handed the whole $opt', () => {
        cy.request({
            url: '/ComponentNamespace/reservedOpt',
            failOnStatusCode: false,
        }).then((res) => {
            expect(res.status).to.eq(500);
            expect(res.body).to.include('is handed &#039;opt&#039;, which is reserved');
        });
    });
});
