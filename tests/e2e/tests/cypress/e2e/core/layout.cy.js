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
});
