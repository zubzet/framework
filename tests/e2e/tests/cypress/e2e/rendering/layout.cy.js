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
        // Each rendered HTML document closes with </html>; multi-render
        // actions concatenate their outputs, so we split on it to inspect
        // each render in isolation.
        const segmentsOf = (body) => body.split('</html>').slice(0, -1);

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

        it('falls back to framework default when no override is set', () => {
            cy.request('/LayoutResolution/neither').then((r) => {
                expect(r.body).to.contain('ZubZet QA Suite');
                expect(r.body).to.not.contain('New Layout');
                expect(r.body).to.not.contain('Alt Layout');
            });
        });

        it('global default is used when only global is set', () => {
            cy.request('/LayoutResolution/globalonly').then((r) => {
                expect(r.body).to.contain('Alt Layout');
                expect(r.body).to.not.contain('New Layout');
                expect(r.body).to.not.contain('ZubZet QA Suite');
            });
        });

        it('instance default is used when only instance is set', () => {
            cy.request('/LayoutResolution/instanceonly').then((r) => {
                expect(r.body).to.contain('New Layout');
                expect(r.body).to.not.contain('Alt Layout');
                expect(r.body).to.not.contain('ZubZet QA Suite');
            });
        });

        it('instance default beats global default', () => {
            cy.request('/LayoutResolution/instanceoverglobal').then((r) => {
                expect(r.body).to.contain('New Layout');
                expect(r.body).to.not.contain('Alt Layout');
                expect(r.body).to.not.contain('ZubZet QA Suite');
            });
        });

        it('explicit render() arg beats both instance and global', () => {
            cy.request('/LayoutResolution/explicitwins').then((r) => {
                expect(r.body).to.contain('ZubZet QA Suite');
                expect(r.body).to.not.contain('New Layout');
                expect(r.body).to.not.contain('Alt Layout');
            });
        });

        it('instance push/pop restores the previous layout', () => {
            cy.request('/LayoutResolution/pushpopinstance').then((r) => {
                const [pushed, popped] = segmentsOf(r.body);
                expect(pushed).to.contain('New Layout');
                expect(pushed).to.contain('Pushed');
                expect(popped).to.contain('ZubZet QA Suite');
                expect(popped).to.contain('Popped');
                expect(popped).to.not.contain('New Layout');
            });
        });

        it('global push/pop restores the previous layout', () => {
            cy.request('/LayoutResolution/pushpopglobal').then((r) => {
                const [pushed, popped] = segmentsOf(r.body);
                expect(pushed).to.contain('Alt Layout');
                expect(pushed).to.contain('Pushed');
                expect(popped).to.contain('ZubZet QA Suite');
                expect(popped).to.contain('Popped');
                expect(popped).to.not.contain('Alt Layout');
            });
        });

        it('popDefaultLayout throws on an empty instance stack', () => {
            cy.request('/LayoutResolution/underflowinstance').then((r) => {
                expect(r.body).to.contain('threw:');
                expect(r.body).to.contain('popDefaultLayout');
            });
        });

        it('popGlobalDefaultLayout throws on an empty global stack', () => {
            cy.request('/LayoutResolution/underflowglobal').then((r) => {
                expect(r.body).to.contain('threw:');
                expect(r.body).to.contain('popGlobalDefaultLayout');
            });
        });

        it('nested instance push/pop unwinds in LIFO order', () => {
            cy.request('/LayoutResolution/pushpopnested').then((r) => {
                const [outer, inner, afterPop, afterAllPops] = segmentsOf(r.body);
                expect(outer).to.contain('Alt Layout');
                expect(outer).to.contain('Outer');
                expect(inner).to.contain('New Layout');
                expect(inner).to.contain('Inner');
                expect(afterPop).to.contain('Alt Layout');
                expect(afterPop).to.contain('AfterPop');
                expect(afterPop).to.not.contain('New Layout');
                expect(afterAllPops).to.contain('ZubZet QA Suite');
                expect(afterAllPops).to.contain('AfterAllPops');
                expect(afterAllPops).to.not.contain('Alt Layout');
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
