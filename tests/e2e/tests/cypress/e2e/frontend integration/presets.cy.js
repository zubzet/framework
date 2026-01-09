describe('Core Features', () => {
    before(() => {
        cy.dbSeed();
    });

    it('Presets Login', ()  => {
        cy.visit("/Frontend/login");
        cy.query("userid").contains("0");

        cy.query("email").click().type("admin@zierhut-it.de");
        cy.query("password").click().type("password");
        cy.query("submit").click();
        cy.visit("/Frontend/login");
        cy.query("userid").contains("1");
    });

    it('Presets Login wrong Password', ()  => {
        cy.visit("/Frontend/login");

        cy.query("email").click().type("admin@zierhut-it.de");
        cy.query("password").click().type("pass");
        cy.query("submit").click();
        cy.query("login-error").should("contain.text", "Username or password is wrong");
    });

    it('Presets Login wrong email', ()  => {
        cy.visit("/Frontend/login");

        cy.query("email").click().type("admin");
        cy.query("password").click().type("password");
        cy.query("submit").click();
        cy.query("login-error").should("contain.text", "Username or password is wrong");
    });

    it('Presets Register', () => {
        cy.visit("/Frontend/register");

        cy.get('#response').invoke('text').then((text) => {
            const data = JSON.parse(text);
            const exists = data.some(item => item.email === 'creation@zierhut-it.de');

            expect(exists).to.be.false;
        });

        cy.query("email").click().type("creation@zierhut-it.de");
        cy.query("password").click().type("password");
        cy.query("password-confirm").click().type("password");
        cy.query("submit").click();

        cy.visit("/Frontend/register");

        cy.get('#response').invoke('text').then((text) => {
            const data = JSON.parse(text);
            const exists = data.some(item => item.email === 'creation@zierhut-it.de');

            expect(exists).to.be.true;
        });

    });

    it('Presets Register Duplicate EMail', () => {
        cy.visit("/Frontend/register");
        cy.query("email").click().type("admin@zierhut-it.de");
        cy.query("password").click().type("password");
        cy.query("password-confirm").click().type("password");
        cy.query("submit").click();
        cy.query("login-error").should("contain.text", "This email is not allowed!");
    });

    it('Presets Register Invalid Email', () => {
        cy.visit("/Frontend/register");
        cy.query("email").click().type("admin");
        cy.query("password").click().type("password");
        cy.query("password-confirm").click().type("password");
        cy.query("submit").click();
        cy.query("login-error").should("contain.text", "This email is not allowed!");
    });

    it('Presets Register Invalid Password', () => {
        cy.visit("/Frontend/register");
        cy.query("email").click().type("testcreation@zierhut-it.de");
        cy.query("password").click().type("`");
        cy.query("password-confirm").click().type("`");
        cy.query("submit").click();
        cy.query("login-error").should("contain.text", "This email is not allowed!");
    });

    it('Presets Register Different Password', () => {
        cy.visit("/Frontend/register");
        cy.query("email").click().type("testcreation@zierhut-it.de");
        cy.query("password").click().type("password");
        cy.query("password-confirm").click().type("keinpassword");
        cy.query("submit").click();
        cy.query("login-error").should("contain.text", "The password are not the same!");
    });
});