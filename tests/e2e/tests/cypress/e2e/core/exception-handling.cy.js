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
        // the response is 200 - so the status check is the actual guard.
        cy.request({ url: '/Core/triggersDeprecation', failOnStatusCode: false }).then((res) => {
            expect(res.status).to.eq(500);
            expect(res.body).to.include('regression-controller-deprecation-marker');
        });
    });
});

// ErrorController::action_500 is reached when an action throws and
// showErrors=0 - the Router catches the exception, re-dispatches to
// /error/500, and the 500 page is rendered instead of Whoops's stacktrace.
//
// The probe flips the mode at request time via
// ExceptionBehavior::setExceptionBehavior(0) instead of mutating
// z_settings.ini, so the change is scoped to the single request and
// the setExceptionBehavior code path itself gets covered.
describe('Exception Handling (showErrors=0)', () => {
    it('renders the 500 page (no stacktrace) when an action throws', () => {
        cy.request({
            url: '/Core/throwsExceptionAfterBehaviorNone',
            failOnStatusCode: false,
        }).then((res) => {
            expect(res.status).to.eq(500);
            expect(res.body, '500 page rendered').to.include('Sorry, we messed up!');
            expect(res.body, 'no Whoops stacktrace leaked').to.not.include('Whoops!');
            expect(res.body, 'no exception marker leaked').to.not.include('regression-controller-exception-marker');
        });
    });
});

describe("Exception Handling Prod", () => {

    before(() => {
        cy.saveConfigBackup();
        cy.setConfigSetting('execution_type', 'prod');
    });

    after(() => {
        cy.restoreConfigBackup();
    });

    it("should not call Whoops handlers in production mode", () => {
        cy.visit("/exception/whoops");
        cy.contains("This is a test exception to check if Whoops is disabled in production mode.").should("be.visible");
    });

});