describe('Login', () => {
    before(() => {
        cy.dbSeed();
    });

    beforeEach(() => {
        cy.fixture('logins.json').as("logins");
        cy.visit("/login");
    });

    it('displays all relevant elements', () => {
        cy.areVisible([
            "username",
            "password",
            "btn-login",
        ]);
    });

    it('contains relevant links', () => {
        cy.hasLinks([
            "login/forgot-password",
        ]);
    });

    it('requires a password', () => {
        cy.query('username').type("some@email.de");
        cy.query('btn-login').click();
        cy.query('error').should("be.visible");
        cy.contains("Username or password is wrong");
    });

    it('requires an email', () => {
        cy.query('password').type("some password");
        cy.query('btn-login').click();
        cy.query('error').should("be.visible");
        cy.contains("Username or password is wrong");
    });

    it('shows an error when the login is wrong', () => {
        cy.get("@logins").then((logins) => {
            cy.query('username').type(logins.wrong.name);
            cy.query('password').type(logins.wrong.password);

            cy.query('btn-login').click();
            cy.query('error').should("be.visible");
            cy.contains("Username or password is wrong");
        });
    });

    it('warns about not activated accounts', () => {
        cy.get("@logins").then((logins) => {
            cy.query('username').type(logins.not_activated.name);
            cy.query('password').type(logins.not_activated.password);

            cy.query('btn-login').click();
            cy.query('error').should("be.visible");
            cy.contains("not activated yet");
            cy.get(`a[href*='login/verify']`).should("be.visible");
        });
    });
});