// Regression guards for the ExceptionBehavior trait + Whoops error page.
//
// The bug these cover: when an uncaught throwable triggered the new
// handleException() path in test mode, loading Whoops PrettyPageHandler
// eagerly pulled in PlainTextHandler whose `(integer)` cast emits an
// E_DEPRECATED on PHP 8.5. ALL-mode unconditionally promoted that to an
// ErrorException inside the exception handler, so the rendered page showed
// "Uncaught ErrorException: Non-canonical cast" instead of the real cause.
// The proper fix was bumping filp/whoops; the historical contract that
// ALL-mode promotes every error (deprecations included) must be preserved.

describe('Exception Handling (ALL mode)', () => {
    before(() => {
        cy.dbSeed();
    });

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
