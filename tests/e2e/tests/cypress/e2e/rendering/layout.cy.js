describe('Layout', () => {
    before(() => {
        cy.dbSeed();
    });

    it('Layout', () => {
        cy.visit("/Core/Renderlayout");
        cy.contains("New Layout");
        cy.contains("Render");
        cy.query("data").contains("Data");
    });

    describe('Default-layout resolution', () => {
        it('controller __construct (instance scope) sets the default layout', () => {
            cy.visit('/LayoutConstructor/render');
            cy.contains('New Layout');
            cy.query('data').contains('Data');
        });

        it('route middleware (instance scope) sets the default layout', () => {
            cy.visit('/LayoutMiddleware/render');
            cy.contains('New Layout');
            cy.query('data').contains('Data');
        });

        it('ZController admin sidebar still renders (global scope from __construct)', () => {
            cy.loginAs('admin');
            cy.visit('/z/edit_user');
            cy.get('[data-test="btn-edit-user"]').should('be.visible');
            cy.get('[data-test="btn-add-user"]').should('be.visible');
            cy.get('#logo').should('contain', 'ZubZet');
        });

        it('explicit render() arg beats both instance and global', () => {
            cy.request('/LayoutResolution/explicitwins').then((r) => {
                expect(r.body).to.contain('ZubZet QA Suite');
                expect(r.body).to.not.contain('New Layout');
                expect(r.body).to.not.contain('Alt Layout');
            });
        });
    });

    it('layout_essentials emits no stray characters between asset tags', () => {
        cy.request('/Core/Renderemptylayout').then((response) => {
            expect(response.status).to.equal(200);

            const doc = (new DOMParser()).parseFromString(response.body, 'text/html');

            const directTextOf = (node) => Array.from(node.childNodes)
                .filter((n) => n.nodeType === Node.TEXT_NODE)
                .map((n) => n.textContent)
                .filter((t) => t.trim() !== '');

            expect(directTextOf(doc.head), 'stray text in <head>').to.deep.equal([]);
            expect(directTextOf(doc.body), 'stray text in <body>').to.deep.equal([]);
            expect(directTextOf(doc.documentElement), 'stray text in <html>').to.deep.equal([]);
        });
    });

    // The `layout/empty` wrapper is the bare-bones layout used when a
    // partial of the page (e.g. a paginated list, a refreshed widget)
    // is fetched over AJAX and the caller wants just the rendered body
    // with none of the page chrome. The response must contain the view
    // output but no <html>/<head>/<body> framing.
    it('layout/empty renders the body without surrounding page chrome', () => {
        cy.request('/Core/renderRaw').then((res) => {
            expect(res.status).to.eq(200);

            // Rendered view content is present.
            expect(res.body).to.include('data-test="title"');
            expect(res.body).to.include('Render');
            expect(res.body).to.include('data-test="data"');
            expect(res.body).to.include('>Data<');

            // None of the page-shell tags from min_layout / new_layout.
            expect(res.body).to.not.match(/<html\b/i);
            expect(res.body).to.not.match(/<head\b/i);
            expect(res.body).to.not.match(/<body\b/i);
            expect(res.body).to.not.match(/<!doctype/i);
        });
    });
});
