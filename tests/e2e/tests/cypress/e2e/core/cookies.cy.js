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

    // getCookies() is a no-key alias for getCookie() that returns the full
    // $_COOKIE array. Set two cookies in one response, then read them all
    // back to exercise the alias and its no-key branch.
    it('getCookies() returns every cookie on the request', () => {
        cy.visit("/Core/cookiessetMulti");
        cy.getCookie("cookieA").should("have.property", "value", "valueA");
        cy.getCookie("cookieB").should("have.property", "value", "valueB");

        cy.request("/Core/cookiesAll").then((res) => {
            expect(res.body).to.include({
                cookieA: "valueA",
                cookieB: "valueB",
            });
        });

        cy.clearCookie("cookieA");
        cy.clearCookie("cookieB");
    });
});