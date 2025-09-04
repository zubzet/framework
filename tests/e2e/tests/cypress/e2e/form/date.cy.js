describe('Form Date Validation', () => {
    before(() => {
        cy.dbSeed();
    });

    it('Correct Input', () => {
        cy.visit("/Form/validationDate");

        cy.get('[name="field_date"]').click().type("2024-10-10");
        cy.get('[name="field_date_required"]').click().type("2024-10-10");
        cy.get('[name="field_date_length"]').click().type("2024-10-10");
        cy.get('[name="field_date_unique"]').click().type("2024-10-10");
        cy.get('button').click();
        cy.get('#form').contains("Saved!");
    });

    it('Correct Input Ignore Unrequired Fields', () => {
        cy.visit("/Form/validationDate");

        cy.get('[name="field_date_required"]').click().type("2024-10-10");
        cy.get('[name="field_date_length"]').click().type("2024-10-10");
        cy.get('[name="field_date_unique"]').click().type("2024-10-10");
        cy.get('button').click();
        cy.get('#form').contains("Saved!");
    });

    it('Input Failure Required', () => {
        cy.visit("/Form/validationDate");
        cy.get('[name="field_date"]').click().type("2024-10-10");
        cy.get('[name="field_date_length"]').click().type("2024-10-10");
        cy.get('[name="field_date_unique"]').click().type("2024-10-10");
        cy.get('button').click();

        cy.get('[name="field_date_required"]').parent().contains("Please fill in this field");
    });

    it('Input Failure Type', () => {
        cy.visit("/Form/validationDate");

        cy.get('[name="field_date"]').click().type("NotAUrl")
        cy.get('[name="field_date_required"]').click().type("2024-10-10");
        cy.get('[name="field_date_length"]').click().type("2024-10-10");
        cy.get('[name="field_date_unique"]').click().type("2024-10-10");
        cy.get('button').click();

        cy.get('[name="field_date"]').parent().contains("Please give a correct date!");
    });

    it('Input Failure Unique', () => {
        cy.visit("/Form/validationDate");

        cy.get('[name="field_date"]').click().type("2024-10-10")
        cy.get('[name="field_date_required"]').click().type("2024-10-10");
        cy.get('[name="field_date_length"]').click().type("2024-10-10");
        cy.get('[name="field_date_unique"]').click().type("1111-11-11");
        cy.get('button').click();

        cy.get('[name="field_date_unique"]').parent().contains("This already exists!");
    });

});