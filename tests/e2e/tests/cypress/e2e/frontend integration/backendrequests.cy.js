describe('Core Features', () => {
    before(() => {
        cy.dbSeed();
    });

    it('Backend Request', () => {
        cy.visit("/Frontend/backendrequest");
        cy.query("add").click();
        cy.query("response").contains("11 success");
    });

    it('Backend Request Response: Error', () => {
        cy.visit("/Frontend/backendrequest");
        cy.query("err").click();
        cy.query("response").contains("error");
    });

    it('Backend Request Custom Rest', () => {
        cy.visit("/Frontend/backendrequest");
        cy.query("cust").click();
        cy.query("response").contains("customrest");
    });

    it('Backend Request Custom RestError', () => {
        cy.visit("/Frontend/backendrequest");
        cy.query("custerr").click();
        cy.query("response").contains("customerror");
    });
});