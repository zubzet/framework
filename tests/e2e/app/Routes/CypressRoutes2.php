<?php

    use ZubZet\Framework\Routing\Route;

    // Test if all files are loaded instead of only one
    Route::get('/test2', [CoreController::class, 'TestRoute']);

    // Middleware-set default layout: a route-level middleware sets the default
    // layout for "this part of the app" before the action's render call runs.
    Route::get('/LayoutMiddleware/render', [LayoutMiddlewareController::class, 'action_render'])
        ->middleware([LayoutMiddlewareController::class, 'Layout_Middleware_SetDefault']);
?>