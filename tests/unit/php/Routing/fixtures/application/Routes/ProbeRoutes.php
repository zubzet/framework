<?php

    use ZubZet\Framework\Routing\Route;

    // The routes of tests/unit/php/Routing/RequestLifecycleTest.php

    Route::get('/explicit', [ProbeController::class, 'action_action']);
    Route::post('/explicit-post', [ProbeController::class, 'action_rest']);
    Route::any('/explicit-any', [ProbeController::class, 'action_action']);
    Route::get('/users/{userId}/posts/{postId}', [ProbeController::class, 'action_route'], ['a', 'b']);

    Route::get('/callback', function(Request $req, Response $res) {
        $res->success(["from" => "callback"]);
    })->afterMiddleware([ProbeController::class, 'afterware']);

    // An afterMiddleware runs after an action, however the action ended
    Route::group('/after', function() {
        Route::get('/returned', [ProbeController::class, 'action_action'])
            ->afterMiddleware([ProbeController::class, 'afterware']);

        Route::get('/rest', [ProbeController::class, 'action_rest'])
            ->afterMiddleware([ProbeController::class, 'afterware']);

        Route::get('/render', [ProbeController::class, 'action_render'])
            ->afterMiddleware([ProbeController::class, 'afterware']);

        Route::get('/guard', [ProbeController::class, 'action_guard'])
            ->afterMiddleware([ProbeController::class, 'afterware']);

        Route::get('/redirect', [ProbeController::class, 'action_redirect'])
            ->afterMiddleware([ProbeController::class, 'afterware']);

        Route::get('/stop', function(Request $req, Response $res) {
            echo "printed";
            $res->stop();
        })->afterMiddleware([ProbeController::class, 'afterware']);

        Route::get('/throws', [ProbeController::class, 'action_throws'])
            ->afterMiddleware([ProbeController::class, 'afterware']);
    });

    // A middleware decides whether the action runs
    Route::group('/before', function() {
        Route::get('/passing', [ProbeController::class, 'action_action'])
            ->middleware([ProbeController::class, 'passing'])
            ->afterMiddleware([ProbeController::class, 'afterware']);

        Route::get('/blocking', [ProbeController::class, 'action_action'])
            ->middleware([ProbeController::class, 'blocking'])
            ->afterMiddleware([ProbeController::class, 'afterware']);

        Route::get('/refusing', [ProbeController::class, 'action_action'])
            ->middleware([ProbeController::class, 'refusing'])
            ->afterMiddleware([ProbeController::class, 'afterware']);
    });

    // Groups without routes guard the actions that are found by their name
    Route::group('/guarded')
        ->middleware([ProbeController::class, 'passing'])
        ->afterMiddleware([ProbeController::class, 'afterware']);

    Route::group('/guarded/blocked')
        ->middleware([ProbeController::class, 'blocking']);

?>
