describe('Z-Admin Panel', () => {
    before(() => {
        cy.dbSeed();
    });

    it('LoginAs', () => {
        cy.visit("/admin/loginas");
        cy.contains("0");

        cy.visit("/admin/loginas");
        cy.contains("1");
    });

    it('Opens Z-Admin', () => {
        cy.visit("/admin/loginas");
        cy.visit("/z");

        // Check we are in Z-Admin and relevant buttons exit
        cy.query("btn-database").should("exist");
        cy.query("btn-edit-user").should("exist");
        cy.query("btn-add-user").should("exist");
        cy.query("btn-roles").should("exist");
    });

    it('Goes into Database', () => {
        cy.visit("/admin/loginas");
        cy.visit("/z");

        // Check we are in Z-Admin and relevant buttons exit
        cy.query("btn-database").should("exist");
        cy.query("btn-database").click();

        // Check that data is imported
        cy.query("table-amount").should("exist");
        cy.query("table-amount").invoke('text').then((text) => {
            const numTables = parseInt(text);
            expect(numTables).to.be.greaterThan(0);
        });

        cy.query("row-amount").should("exist");
        cy.query("row-amount").invoke('text').then((text) => {
            const numTables = parseInt(text);
            expect(numTables).to.be.greaterThan(0);
        });

        cy.query("table-name-admin_panel_test").should("exist");
        cy.query("table-name-admin_panel_test").find("a").first().click();

        // page 1-5 should exist

        let topId;
          // top id should be different
        cy.query("row-id").first().invoke('text').then((text) => {
            topId = text;
        });
        cy.query("pagination-next").should("exist");
        cy.query("pagination-next").click();

        cy.query("row-id").first().invoke('text').then((text) => {
            expect(text).not.to.eq(topId);
            topId = text;
        });

        cy.query("pagination-previous").should("exist");
        cy.query("pagination-previous").click();

        cy.query("row-id").first().invoke('text').then((text) => {
            expect(text).not.to.eq(topId);
            topId = text;
        });

        cy.query("pagination-last").should("exist");
        cy.query("pagination-last").click();

        cy.query("row-id").first().invoke('text').then((text) => {
            expect(text).not.to.eq(topId);
            topId = text;
        });

        cy.query("pagination-first").should("exist");
        cy.query("pagination-first").click();

        cy.query("row-id").first().invoke('text').then((text) => {
            expect(text).not.to.eq(topId);
            topId = text;
        });

        cy.query("pagination-page-1").should("exist");
        cy.query("pagination-page-2").should("exist");
        cy.query("pagination-page-3").should("exist");
        cy.query("pagination-page-4").should("exist");
        cy.query("pagination-page-5").should("exist");

        cy.query("sort-by").click();
    });

    it('Goes into add User', () => {
        cy.visit("/admin/loginas");
        cy.visit("/z");

        cy.query("btn-add-user").should("exist");
        cy.query("btn-add-user").click();


        cy.form("email").should("exist");
        cy.form("password").should("exist");
        cy.get('.btn.btn-primary').should("exist");

        // try to create invalid user
        cy.get('.btn.btn-primary').last().click();
        cy.get(".form-text.text-danger").should("exist");

        // create actually user
        cy.form("email").type("exampleUser@domain.de");
        cy.form("password").type("examplePass");
        cy.get('.btn.btn-primary').last().click();

        // check for error
        cy.get(".alert.sticky-top.alert-danger").should("not.exist");
        cy.get(".form-text.text-danger").should('not.be.visible');

    });

    it('Checks that user has been created', () => {
        cy.visit("/admin/loginas");
        cy.visit("/z");
        cy.query("btn-edit-user").click();

        cy.query("user").contains("exampleUser@domain.de").should("exist");

    });

    it('Goes into edit User', () => {
        cy.visit("/admin/loginas");
        cy.visit("/z");

        cy.query("btn-edit-user").should("exist");
        cy.query("btn-edit-user").click();

        cy.query("user").contains("customer@zierhut-it.de").should("exist");
        cy.query("user").contains("customer@zierhut-it.de").click();

        cy.form("email").should("exist");

    });

    it('Goes into roles', () => {
        cy.visit("/admin/loginas");
        cy.visit("/z");

        cy.query("btn-roles").should("exist");
        cy.query("btn-roles").click();
        cy.query("role").should("exist");
        cy.query("role-create").should("exist");
        cy.query("role-create").click();

        cy.form("name").should("exist");
        cy.form("name").type("test");
        cy.get('.btn.btn-primary').find('i.fas.fa-plus').should('exist');
        cy.wait(500);
        cy.get('.btn.btn-primary').filter(':has(i.fas.fa-plus)').click();
        cy.get('.btn.btn-primary').filter(':has(i.fas.fa-plus)').click();
        cy.get('input#input-2').type('test');
        cy.get('.btn.btn-primary').last().click();
        cy.get(".form-text.text-danger").should('not.be.visible');
    });

});