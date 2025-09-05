describe('Cookies', () => {
    before(() => {
        cy.dbSeed();
    });

    it('Cookies', () => {
        cy.visit("/Core/cookiesset");
        cy.getCookie("testCookie")
            .should("exist")
            .and('have.property', 'value', 'cookieValue');

        cy.visit("/Core/cookieget");
        cy.contains("cookieValue");

        cy.visit("/Core/cookieunset");
        cy.getCookie("testCookie").should("not.exist");
    });
});