describe('Vue mini example via import map', () => {
    before(() => {
        cy.dbSeed();
    });

    it('renders the counter and increments on click', () => {
        cy.visit('/vue-demo');

        cy.get('[data-test="vue-demo-heading"]').should('contain.text', 'Vue Demo');

        // The component mounts asynchronously after the ESM import resolves.
        cy.get('[data-test="vue-counter-btn"]', { timeout: 15000 })
            .should('contain.text', 'Clicked 0 times')
            .click()
            .should('contain.text', 'Clicked 1 times')
            .click()
            .should('contain.text', 'Clicked 2 times');
    });

    it('serves framework essentials through the asset proxy', () => {
        cy.request('/_zubzet/asset-proxy/jquery/jquery.min.js').then((response) => {
            expect(response.status).to.eq(200);
            expect(response.body).to.contain('jQuery');
        });
        cy.request('/_zubzet/asset-proxy/bootstrap/css/bootstrap.min.css').then((response) => {
            expect(response.status).to.eq(200);
            expect(response.body).to.contain('Bootstrap');
        });
        cy.request('/_zubzet/asset-proxy/fontawesome/css/all.min.css').then((response) => {
            expect(response.status).to.eq(200);
            expect(response.body).to.contain('Font Awesome');
        });
    });
});
