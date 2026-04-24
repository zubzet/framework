describe('Controllers', () => {
    before(() => {
        cy.dbSeed();
    });

    it('Controller Index', () => {
        cy.visit("/Core");
        cy.contains("Controller Index");
    });

    it('Controller Fallback', () => {
        cy.visit("/Core/AABBCCDDEEFFGG");
        cy.contains("Controller Fallback");
    });

    it('Controller Action', () => {
        cy.visit("/Core/Action");
        cy.contains("Controller Action");
    });

    it('Controller Parameters', () => {
        cy.visit("/Core/Parameters/Param1/Param2");
        cy.contains("Param1");
        cy.contains("Param2");

        cy.visit("/Core/Parameters1/TestParameter");
        cy.contains("Ja");

        cy.visit("/Core/Parameters1/TestParameterss");
        cy.contains("Nein");

        cy.visit("/Core/Parameters2/ABC/DEF/GHI/JKL");
        cy.contains("ABC");
        cy.contains("DEF");
        cy.contains("GHI").should("not.exist");
    });

    it('Controller Render', () => {
        cy.visit("/Core/Render");
        cy.query("title").contains("Render");
        cy.query("data").contains("Data");
    });

    // Regression guards for the Whoops error page pipeline. The bug they cover:
    // when an uncaught throwable triggered handleException(), loading the Whoops
    // PrettyPageHandler eagerly autoloaded PlainTextHandler whose `(integer)`
    // cast emits an E_DEPRECATED on PHP 8.5. ALL-mode promoted that to an
    // ErrorException inside the exception handler, so the rendered page showed
    // "Uncaught ErrorException: Non-canonical cast" instead of the real cause.
    describe('Error rendering (ALL mode)', () => {
        it('renders the original exception class and message, not a vendor cascade', () => {
            cy.request({ url: '/Core/throwsException', failOnStatusCode: false }).then((res) => {
                expect(res.status).to.eq(500);
                expect(res.body).to.include('regression-controller-exception-marker');
                expect(res.body).to.include('RuntimeException');
                expect(res.body).to.not.match(/Non-canonical cast/i);
                expect(res.body).to.not.include('PlainTextHandler');
            });
        });

        it('promotes E_USER_DEPRECATED to a fatal exception (historical ALL contract)', () => {
            // If a future change skips deprecations, the action's echo runs and
            // the response is 200 — so the status check is the actual guard.
            cy.request({ url: '/Core/triggersDeprecation', failOnStatusCode: false }).then((res) => {
                expect(res.status).to.eq(500);
                expect(res.body).to.include('regression-controller-deprecation-marker');
            });
        });
    });
});