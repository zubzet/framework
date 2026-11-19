<?php

    use ZubZet\Framework\Routing\Route;

    // An afterMiddleware runs after an action that ended the request, see tests/cypress/e2e/core/end-of-request.cy.js
    Route::group('/end-of-request', function() {
        Route::get('/render', [EndprobeController::class, 'action_render'])
            ->afterMiddleware([EndprobeController::class, 'afterware']);

        Route::get('/rest', [EndprobeController::class, 'action_rest'])
            ->afterMiddleware([EndprobeController::class, 'afterware']);

        Route::get('/guard', [EndprobeController::class, 'action_guard'])
            ->afterMiddleware([EndprobeController::class, 'afterware']);

        Route::get('/blocked', [EndprobeController::class, 'action_rest'])
            ->middleware([EndprobeController::class, 'blocking'])
            ->afterMiddleware([EndprobeController::class, 'afterware']);
    });

?>
