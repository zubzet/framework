describe('Rest', () => {
    before(() => {
        cy.dbSeed();
    });

    // jQuery parses a reply that is sent as application/json itself, so code that calls
    // JSON.parse() on the answer of a plain $.post() only keeps working with a text reply
    it('generateRest answers a client that does not ask for JSON with JSON as plain text', () => {
        cy.request({ url: "/Core/Rest", headers: { Accept: "*/*" } }).then((res) => {
            expect(res.status).to.eq(200);
            expect(res.headers['content-type']).to.eq('text/plain; charset=utf-8');
            expect(res.body).to.be.a('string');

            const reply = JSON.parse(res.body);
            expect(reply.Response).to.eq("Test");
            expect(reply.meta.endpoint).to.eq("REST API");
            expect(reply.meta.request).to.eq("Core/Rest");
        });
    });

    it('generateRest answers a client that asks for JSON with application/json', () => {
        cy.request({ url: "/Core/Rest", headers: { Accept: "application/json, text/javascript, */*; q=0.01" } }).then((res) => {
            expect(res.headers['content-type']).to.eq('application/json');

            // Cypress parses a body that is sent as JSON
            expect(res.body.Response).to.eq("Test");
        });
    });

    // Sent as text/html, the markup inside a value would run in a browser that opens the endpoint
    it('a browser that opens the endpoint gets text it does not render as a page', () => {
        cy.request({ url: "/Core/Rest", headers: { Accept: "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8" } }).then((res) => {
            expect(res.headers['content-type']).to.eq('text/plain; charset=utf-8');
            expect(res.headers['x-content-type-options']).to.eq('nosniff');
            expect(res.headers['vary']).to.contain('Accept');
        });
    });

    it('generateRestError sends only the error, typed like generateRest', () => {
        cy.request({ url: "/Core/RestErr", headers: { Accept: "*/*" } }).then((res) => {
            expect(res.headers['content-type']).to.eq('text/plain; charset=utf-8');
            expect(JSON.parse(res.body)).to.deep.eq({ error: { code: 400, message: "TestErr" } });
        });

        cy.request({ url: "/Core/RestErr", headers: { Accept: "application/json" } }).then((res) => {
            expect(res.headers['content-type']).to.eq('application/json');
            expect(res.body).to.deep.eq({ error: { code: 400, message: "TestErr" } });
        });
    });
});
