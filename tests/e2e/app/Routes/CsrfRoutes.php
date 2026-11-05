<?php

    use ZubZet\Framework\Routing\Route;

    // Probes for the withoutCsrf() opt-out in tests/cypress/e2e/core/csrf.cy.js
    Route::group('/csrf-exempt', function() {
        Route::post('/group', [CsrfProbeController::class, 'action_plain']);
    })->withoutCsrf();

    Route::post('/csrf-exempt-route', [CsrfProbeController::class, 'action_plain'])->withoutCsrf();
    Route::post('/csrf-checked-route', [CsrfProbeController::class, 'action_plain']);

    // No route declares this path, it is reached by the convention fallback
    Route::group('/CsrfProbe/exempt')->withoutCsrf();
    Route::group('/CsrfProbe/guarded')->withoutCsrf();

    // Probes for the withoutCsrfCookie() opt-out
    Route::group('/csrf-cookie-free', function() {
        Route::any('/group', [CsrfProbeController::class, 'action_plain']);

        // A nested withoutCsrf() keeps the cookie opt-out of the outer group
        Route::group('/nested', function() {
            Route::get('/child', [CsrfProbeController::class, 'action_plain'])->withoutCsrf();
        });
    })->withoutCsrfCookie();

    Route::any('/csrf-cookie-free-route', [CsrfProbeController::class, 'action_plain'])->withoutCsrfCookie();
    Route::get('/csrf-cookie-route', [CsrfProbeController::class, 'action_plain']);

    Route::group('/CsrfProbe/cookiefree')->withoutCsrfCookie();

?>
