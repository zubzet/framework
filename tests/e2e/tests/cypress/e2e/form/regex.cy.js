describe("Form Regex Validation", () => {
    before(() => {
        cy.dbSeed();
    });

    it("Correct Input - letters and spaces only", () => {
        cy.visit("/Form/validationRegex");
        cy.form("field_regex").type("Hello World");
        cy.form("field_regex_exceptions").type("Hello World");
        cy.get("button").click();
        cy.get("#form").contains("Saved!");
    });

    it("Correct Input - exceptions strip `-` and `!` before regex check", () => {
        cy.visit("/Form/validationRegex");
        cy.form("field_regex").type("Plain");
        cy.form("field_regex_exceptions").type("Hello-World!");
        cy.get("button").click();
        cy.get("#form").contains("Saved!");
    });

    it("Input Failure - digit is not allowed by /[A-Za-z ]/", () => {
        cy.visit("/Form/validationRegex");
        cy.form("field_regex").type("Has1Digit");
        cy.get("button").click();
        cy.form("field_regex").parent().contains("The input does not meet the required pattern!");
    });

    it("Input Failure - exception field still rejects chars outside regex+exceptions", () => {
        cy.visit("/Form/validationRegex");
        cy.form("field_regex").type("Plain");
        cy.form("field_regex_exceptions").type("Hello-9!");
        cy.get("button").click();
        cy.form("field_regex_exceptions").parent().contains("The input does not meet the required pattern!");
    });
});
