describe('Form Number Validation', () => {
    before(() => {
        cy.dbSeed();
    });

    it('Correct Input', () => {
        cy.visit("/Form/validationNumber");

        cy.get('[name="field_number"]').click().type("5");
        cy.get('[name="field_number_required"]').click().type("10");
        cy.get('[name="field_number_range"]').click().type("15");
        cy.get('[name="field_number_unique"]').click().type("20");
        cy.get('button').click();
        cy.get('#form').contains("Saved!");
    });

    it('Correct Input Ignore Unrequired Fields', () => {
        cy.visit("/Form/validationNumber");

        cy.get('[name="field_number_required"]').click().type("10");
        cy.get('[name="field_number_range"]').click().type("15");
        cy.get('[name="field_number_unique"]').click().type("20");
        cy.get('button').click();
        cy.get('#form').contains("Saved!");
    });

    it('Input Failure Required', () => {
        cy.visit("/Form/validationNumber");
        cy.get('[name="field_number"]').click().type("5");
        cy.get('[name="field_number_range"]').click().type("10");
        cy.get('[name="field_number_unique"]').click().type("25");
        cy.get('button').click();

        cy.get('[name="field_number_required"]').parent().contains("Please fill in this field");
    });

    it('Input Failure Length', () => {
        cy.visit("/Form/validationNumber");

        cy.get('[name="field_number"]').click().type("5");
        cy.get('[name="field_number_required"]').click().type("10");
        cy.get('[name="field_number_range"]').click().type("50");
        cy.get('[name="field_number_unique"]').click().type("20");
        cy.get('button').click();

        cy.get('[name="field_number_range"]').parent().contains("The number is too large to too small.");
    });

    it('Input Failure Type', () => {
        cy.visit("/Form/validationNumber");

        cy.get('[name="field_number"]').click().type("NotANumber")
        cy.get('[name="field_number_required"]').click().type("10");
        cy.get('[name="field_number_range"]').click().type("15");
        cy.get('[name="field_number_unique"]').click().type("20");
        cy.get('button').click();

        cy.get('[name="field_number"]').parent().contains("Your input does not have the correct pattern!");
    });

    it('Input Failure Unique', () => {
        cy.visit("/Form/validationNumber");

        cy.get('[name="field_number"]').click().type("5")
        cy.get('[name="field_number_required"]').click().type("10");
        cy.get('[name="field_number_range"]').click().type("15");
        cy.get('[name="field_number_unique"]').click().type("99");
        cy.get('button').click();

        cy.get('[name="field_number_unique"]').parent().contains("This already exists!");
    });

});