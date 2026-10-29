<?php

    use ZubZet\Framework\Routing\Route;

    // An explicit route, checked like the convention paths, in tests/cypress/e2e/core/csrf.cy.js
    Route::any('/csrf-probe-route', [CsrfProbeController::class, 'action_plain']);

?>
