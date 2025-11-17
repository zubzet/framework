describe('Controllers', () => {
    before(() => {
        cy.dbSeed();
    });

    it('should call an error cause you cannot define functions with the name of the helper functions', () => {
        cy.visit("/collision.php");
        cy.get('body').should('contain.text', "The function 'model' is already defined");
    });

    it('should be possible to use the "zubzet" helper function', () => {
        cy.visit("/helper/zubzet");
        cy.get('body').should('contain.text', "TestValue");
    });

    it('should be possible to use the "model" helper function', () => {
        cy.visit("/helper/model");
        cy.get('body').should('contain.text', "HelperModel called");
    });

    it('should be possible to use the "request" helper function', () => {
        cy.visit("/helper/request/test");
        cy.get('body').should('contain.text', "test");
    });

    it('should be possible to use the "response" helper function', () => {
        cy.visit("/helper/response");
        cy.get('body').should('contain.text', '"response": "success"');
    });

    it('should be possible to use the "config" helper function', () => {
        cy.visit("/helper/config");
        cy.get('body').should('contain.text', "TestValue");
    });

    it('should be possible to use the "user" helper function', () => {
        cy.visit("/helper/user");
        cy.get('body').should('contain.text', "not logged in");
    });

    it('should be possible to use the "db" helper function', () => {
        cy.visit("/helper/db");
        cy.get('body').should('contain.text', "database connected");
    });

    it('should be possible to use the "view" helper function', () => {
        cy.visit("/helper/view");
        cy.get('body').should('contain.text', "Render");
        cy.get('body').should('contain.text', "HelperFunction");
    });
});