describe('Form Text Validation', () => {
    before(() => {
        cy.dbSeed();
    });

    it('Correct Input', () => {
        cy.visit("/Form/validationText");

        cy.get('[name="field_text"]').click().type("Text");
        cy.get('[name="field_text_required"]').click().type("TextRequired");
        cy.get('[name="field_text_length"]').click().type("TextLength");
        cy.get('[name="field_text_unique"]').click().type("TextUnique");
        cy.get('button').click();
        cy.get('#form').contains("Saved!");
    });

    it('Correct Input Ignore Unrequired Fields', () => {
        cy.visit("/Form/validationText");

        cy.get('[name="field_text_required"]').click().type("TextRequired");
        cy.get('[name="field_text_length"]').click().type("TextLength");
        cy.get('[name="field_text_unique"]').click().type("TextUnique");
        cy.get('button').click();
        cy.get('#form').contains("Saved!");
    });

    it('Input Failure Required', () => {
        cy.visit("/Form/validationText");
        cy.get('[name="field_text"]').click().type("Text");
        cy.get('[name="field_text_length"]').click().type("TextLength");
        cy.get('[name="field_text_unique"]').click().type("TextUnique");
        cy.get('button').click();

        cy.get('[name="field_text_required"]').parent().contains("Please fill in this field");
    });

    it('Input Failure Length', () => {
        cy.visit("/Form/validationText");

        cy.get('[name="field_text"]').click().type("Text");
        cy.get('[name="field_text_required"]').click().type("TextRequired");
        cy.get('[name="field_text_length"]').click().type("Txt");
        cy.get('[name="field_text_unique"]').click().type("TextUnique");
        cy.get('button').click();

        cy.get('[name="field_text_length"]').parent().contains("Your input is too long or too short");
    });

    it('Input Failure Unique', () => {
        cy.visit("/Form/validationText");

        cy.get('[name="field_text"]').click().type("Text");
        cy.get('[name="field_text_required"]').click().type("TextRequired");
        cy.get('[name="field_text_length"]').click().type("TextLength");
        cy.get('[name="field_text_unique"]').click().type("UniqueText");
        cy.get('button').click();

        cy.get('[name="field_text_unique"]').parent().contains("This already exists!");
    });

});