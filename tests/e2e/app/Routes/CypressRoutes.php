<?php
    use ZubZet\Framework\Routing\Route;

    // it should execute "Route_Middleware_Accept" and then block by "Route_Middleware_Block" (no afterware or route action should be executed)
    Route::group('/RouteDeny', function() {})
        ->middleware([CoreController::class, 'Route_Middleware_Accept'])
        ->middleware([CoreController::class, 'Route_Middleware_Block'])
        ->afterMiddleware([CoreController::class, 'Route_Afterware']);

    // it should execute "Route_Middleware_Accept", then the route action, then "Route_Afterware"
    Route::group('/RouteAccept', function() {})
        ->middleware([CoreController::class, 'Route_Middleware_Accept'])
        ->afterMiddleware([CoreController::class, 'Route_Afterware']);


    Route::get('/test', [CoreController::class, 'TestRoute']);

    Route::get('/abc/{userId}/{postId}', [CoreController::class, 'TestRoute']);

    Route::get('/middleware-accept', [CoreController::class, 'TestRoute'])
        ->middleware([CoreController::class, 'Route_Middleware_Accept']);

    Route::get('/middleware-block', [CoreController::class, 'TestRoute'])
        ->middleware([CoreController::class, 'Route_Middleware_Block']);

    Route::get('/afterware', [CoreController::class, 'TestRoute'])
        ->afterMiddleware([CoreController::class, 'Route_Afterware']);

    Route::get('/middleware-accept-afterware', [CoreController::class, 'TestRoute'])
        ->middleware([CoreController::class, 'Route_Middleware_Accept'])
        ->afterMiddleware([CoreController::class, 'Route_Afterware']);

    Route::get('/middleware-block-afterware', [CoreController::class, 'TestRoute'])
        ->middleware([CoreController::class, 'Route_Middleware_Block'])
        ->afterMiddleware([CoreController::class, 'Route_Afterware']);


    // Testing all Stages with a Group-Middleware which accepts the request
    Route::group('/accept', function() {
        Route::get('/test', [CoreController::class, 'TestRoute']);

        Route::get('/middleware-accept', [CoreController::class, 'TestRoute'])
            ->middleware([CoreController::class, 'Route_Middleware_Accept']);

        Route::get('/middleware-block', [CoreController::class, 'TestRoute'])
            ->middleware([CoreController::class, 'Route_Middleware_Block']);

        Route::get('/afterware', [CoreController::class, 'TestRoute'])
            ->afterMiddleware([CoreController::class, 'Route_Afterware']);

        Route::get('/middleware-accept-afterware', [CoreController::class, 'TestRoute'])
            ->middleware([CoreController::class, 'Route_Middleware_Accept'])
            ->afterMiddleware([CoreController::class, 'Route_Afterware']);

        Route::get('/middleware-block-afterware', [CoreController::class, 'TestRoute'])
            ->middleware([CoreController::class, 'Route_Middleware_Block'])
            ->afterMiddleware([CoreController::class, 'Route_Afterware']);

    })
    ->middleware([CoreController::class, 'Group_Middleware_Accept']);


    // Testing all Stages with a Group-Middleware which blocks the request
    Route::group('/block', function() {

        Route::get('/test', [CoreController::class, 'TestRoute']);

        Route::get('/middleware-accept', [CoreController::class, 'TestRoute'])
            ->middleware([CoreController::class, 'Route_Middleware_Accept']);

        Route::get('/middleware-block', [CoreController::class, 'TestRoute'])
            ->middleware([CoreController::class, 'Route_Middleware_Block']);

        Route::get('/afterware', [CoreController::class, 'TestRoute'])
            ->afterMiddleware([CoreController::class, 'Route_Afterware']);

        Route::get('/middleware-accept-afterware', [CoreController::class, 'TestRoute'])
            ->middleware([CoreController::class, 'Route_Middleware_Accept'])
            ->afterMiddleware([CoreController::class, 'Route_Afterware']);

        Route::get('/middleware-block-afterware', [CoreController::class, 'TestRoute'])
            ->middleware([CoreController::class, 'Route_Middleware_Block'])
            ->afterMiddleware([CoreController::class, 'Route_Afterware']);

    })
    ->middleware([CoreController::class, 'Group_Middleware_Block']);


    // Testing all Stages with a Group-Afterware
    Route::group('/afterware', function() {

        Route::get('/test', [CoreController::class, 'TestRoute']);

        Route::get('/middleware-accept', [CoreController::class, 'TestRoute'])
            ->middleware([CoreController::class, 'Route_Middleware_Accept']);

        Route::get('/middleware-block', [CoreController::class, 'TestRoute'])
            ->middleware([CoreController::class, 'Route_Middleware_Block']);

        Route::get('/afterware', [CoreController::class, 'TestRoute'])
            ->afterMiddleware([CoreController::class, 'Route_Afterware']);

        Route::get('/middleware-accept-afterware', [CoreController::class, 'TestRoute'])
            ->middleware([CoreController::class, 'Route_Middleware_Accept'])
            ->afterMiddleware([CoreController::class, 'Route_Afterware']);

        Route::get('/middleware-block-afterware', [CoreController::class, 'TestRoute'])
            ->middleware([CoreController::class, 'Route_Middleware_Block'])
            ->afterMiddleware([CoreController::class, 'Route_Afterware']);

    })
    ->afterMiddleware([CoreController::class, 'Group_Afterware']);


    // Testing all Stages with a Group-Middleware which accept the request and a Group-Afterware
    Route::group('/accept-afterware', function() {

        Route::get('/test', [CoreController::class, 'TestRoute']);

        Route::get('/middleware-accept', [CoreController::class, 'TestRoute'])
            ->middleware([CoreController::class, 'Route_Middleware_Accept']);

        Route::get('/middleware-block', [CoreController::class, 'TestRoute'])
            ->middleware([CoreController::class, 'Route_Middleware_Block']);

        Route::get('/afterware', [CoreController::class, 'TestRoute'])
            ->afterMiddleware([CoreController::class, 'Route_Afterware']);

        Route::get('/middleware-accept-afterware', [CoreController::class, 'TestRoute'])
            ->middleware([CoreController::class, 'Route_Middleware_Accept'])
            ->afterMiddleware([CoreController::class, 'Route_Afterware']);

        Route::get('/middleware-block-afterware', [CoreController::class, 'TestRoute'])
            ->middleware([CoreController::class, 'Route_Middleware_Block'])
            ->afterMiddleware([CoreController::class, 'Route_Afterware']);

    })
    ->middleware([CoreController::class, 'Group_Middleware_Accept'])
    ->afterMiddleware([CoreController::class, 'Group_Afterware']);


    // Testing all Stages with a Group-Middleware which blocks the request and a Group-Afterware
    Route::group('/block-afterware', function() {

        Route::get('/test', [CoreController::class, 'TestRoute']);

        Route::get('/middleware-accept', [CoreController::class, 'TestRoute'])
            ->middleware([CoreController::class, 'Route_Middleware_Accept']);

        Route::get('/middleware-block', [CoreController::class, 'TestRoute'])
            ->middleware([CoreController::class, 'Route_Middleware_Block']);

        Route::get('/afterware', [CoreController::class, 'TestRoute'])
            ->afterMiddleware([CoreController::class, 'Route_Afterware']);

        Route::get('/middleware-accept-afterware', [CoreController::class, 'TestRoute'])
            ->middleware([CoreController::class, 'Route_Middleware_Accept'])
            ->afterMiddleware([CoreController::class, 'Route_Afterware']);

        Route::get('/middleware-block-afterware', [CoreController::class, 'TestRoute'])
            ->middleware([CoreController::class, 'Route_Middleware_Block'])
            ->afterMiddleware([CoreController::class, 'Route_Afterware']);

    })
    ->middleware([CoreController::class, 'Group_Middleware_Block'])
    ->afterMiddleware([CoreController::class, 'Group_Afterware']);


    // Testing all Stages with a Group-Middleware which accept the request and a Group-Afterware with Parameters
    Route::group('/accept-afterware-parameters/{userId}/{postId}', function() {

        Route::get('/test', [CoreController::class, 'TestRoute']);

        Route::get('/middleware-accept', [CoreController::class, 'TestRoute'])
            ->middleware([CoreController::class, 'Route_Middleware_Accept']);

        Route::get('/middleware-block', [CoreController::class, 'TestRoute'])
            ->middleware([CoreController::class, 'Route_Middleware_Block']);

        Route::get('/afterware', [CoreController::class, 'TestRoute'])
            ->afterMiddleware([CoreController::class, 'Route_Afterware']);

        Route::get('/middleware-accept-afterware', [CoreController::class, 'TestRoute'])
            ->middleware([CoreController::class, 'Route_Middleware_Accept'])
            ->afterMiddleware([CoreController::class, 'Route_Afterware']);

        Route::get('/middleware-block-afterware', [CoreController::class, 'TestRoute'])
            ->middleware([CoreController::class, 'Route_Middleware_Block'])
            ->afterMiddleware([CoreController::class, 'Route_Afterware']);

    })
    ->middleware([CoreController::class, 'Group_Middleware_Accept'])
    ->afterMiddleware([CoreController::class, 'Group_Afterware']);

    Route::post('/post-test', [CoreController::class, 'TestRoute']);

    Route::any('/any-test', [CoreController::class, 'TestRoute']);

    Route::define('get', '/define-get', [CoreController::class, 'TestRoute']);


    Route::get('/rm-accept/rm-block', [CoreController::class, 'TestRoute'])
        ->middleware([CoreController::class, 'Route_Middleware_Accept'])
        ->middleware([CoreController::class, 'Route_Middleware_Block']);

?>