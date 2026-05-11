describe('Form Text Validation', () => {
    before(() => {
        cy.dbSeed();
    });

    it('Correct Input', () => {
        cy.visit("/Form/validationText");

        cy.form('field_text').click().type("Text");
        cy.form('field_text_required').click().type("TextRequired");
        cy.form('field_text_length').click().type("TextLength");
        cy.form('field_text_unique').click().type("TextUnique");
        cy.form('field_text_unique_ignore').click().type("UniqueIgnore");
        cy.get('button').click();
        cy.get('#form').contains("Saved!");
    });

    it('Correct Input Ignore Not Required Fields', () => {
        cy.visit("/Form/validationText");

        cy.form('field_text_required').click().type("TextRequired");
        cy.form('field_text_length').click().type("TextLength");
        cy.form('field_text_unique').click().type("TextUnique");
        cy.get('button').click();
        cy.get('#form').contains("Saved!");
    });

    it('Input Failure Required', () => {
        cy.visit("/Form/validationText");
        cy.form('field_text').click().type("Text");
        cy.form('field_text_length').click().type("TextLength");
        cy.form('field_text_unique').click().type("TextUnique");
        cy.get('button').click();

        cy.form('field_text_required').parent().contains("Please fill in this field");
    });

    it('Input Failure Length', () => {
        cy.visit("/Form/validationText");

        cy.form('field_text').click().type("Text");
        cy.form('field_text_required').click().type("TextRequired");
        cy.form('field_text_length').click().type("Txt");
        cy.form('field_text_unique').click().type("TextUnique");
        cy.get('button').click();

        cy.form('field_text_length').parent().contains("Your input is too long or too short");
    });

    it('Input Failure Unique', () => {
        cy.visit("/Form/validationText");

        cy.form('field_text').click().type("Text");
        cy.form('field_text_required').click().type("TextRequired");
        cy.form('field_text_length').click().type("TextLength");
        cy.form('field_text_unique').click().type("UniqueText");
        cy.get('button').click();

        cy.form('field_text_unique').parent().contains("This already exists!");
    });

    it('Unique with ignoreField — the ignored row passes the uniqueness check', () => {
        cy.visit("/Form/validationText");

        cy.form('field_text_required').click().type("TextRequired");
        cy.form('field_text_length').click().type("TextLength");
        cy.form('field_text_unique').click().type("TextUnique");
        // duplicate.value="UniqueText" exists, but the rule ignores it.
        cy.form('field_text_unique_ignore').click().type("UniqueText");
        cy.get('button').click();
        cy.get('#form').contains("Saved!");
    });

});