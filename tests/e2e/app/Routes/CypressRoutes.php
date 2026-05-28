<?php
    use ZubZet\Framework\Routing\Route;

    Route::group('/arguments', function() {
        Route::get('/action', [RoutingController::class, 'TestRoute_WithArguments'], ['abc', 123]);

        Route::get('/middleware-accept', [RoutingController::class, 'TestRoute_WithArguments'])
            ->middleware([RoutingController::class, 'Route_Middleware_Accept_WithArguments'], ['abc', 123]);

        Route::get('/afterware', [RoutingController::class, 'TestRoute_WithArguments'])
            ->afterMiddleware([RoutingController::class, 'Route_Afterware_WithArguments'], ['abc', 123]);

        Route::get('/all', [RoutingController::class, 'TestRoute_WithArguments'], ['abc', 123])
            ->middleware([RoutingController::class, 'Route_Middleware_Accept_WithArguments'], ['def', 456])
            ->afterMiddleware([RoutingController::class, 'Route_Afterware_WithArguments'], ['ghi', 789]);

        Route::get('/{userId}/action', [RoutingController::class, 'TestRoute_WithArguments'], ['abc', 123]);
    });

    // it should execute "Route_Middleware_Accept" and then block by "Route_Middleware_Block" (no afterware or route action should be executed)
    Route::group('/RouteDeny', function() {
        Route::get('/check', [RoutingController::class, 'action_check']);
    })
        ->middleware([RoutingController::class, 'Route_Middleware_Accept'])
        ->middleware([RoutingController::class, 'Route_Middleware_Block'])
        ->afterMiddleware([RoutingController::class, 'Route_Afterware']);

    // it should execute "Route_Middleware_Accept", then the route action, then "Route_Afterware"
    Route::group('/RouteAccept', function() {
        Route::get('/check', [RoutingController::class, 'action_check']);
    })
        ->middleware([RoutingController::class, 'Route_Middleware_Accept'])
        ->afterMiddleware([RoutingController::class, 'Route_Afterware']);


    Route::get('/test', [RoutingController::class, 'TestRoute']);

    Route::get('/abc/{userId}/{postId}', [RoutingController::class, 'TestRoute']);
    Route::get('/abc/{userId}/{postId}/byKey', [RoutingController::class, 'action_TestRouteByKey']);

    Route::get('/middleware-accept', [RoutingController::class, 'TestRoute'])
        ->middleware([RoutingController::class, 'Route_Middleware_Accept']);

    Route::get('/middleware-block', [RoutingController::class, 'TestRoute'])
        ->middleware([RoutingController::class, 'Route_Middleware_Block']);

    Route::get('/afterware', [RoutingController::class, 'TestRoute'])
        ->afterMiddleware([RoutingController::class, 'Route_Afterware']);

    Route::get('/middleware-accept-afterware', [RoutingController::class, 'TestRoute'])
        ->middleware([RoutingController::class, 'Route_Middleware_Accept'])
        ->afterMiddleware([RoutingController::class, 'Route_Afterware']);

    Route::get('/middleware-block-afterware', [RoutingController::class, 'TestRoute'])
        ->middleware([RoutingController::class, 'Route_Middleware_Block'])
        ->afterMiddleware([RoutingController::class, 'Route_Afterware']);


    // Testing all Stages with a Group-Middleware which accepts the request
    Route::group('/accept', function() {
        Route::get('/test', [RoutingController::class, 'TestRoute']);

        Route::get('/middleware-accept', [RoutingController::class, 'TestRoute'])
            ->middleware([RoutingController::class, 'Route_Middleware_Accept']);

        Route::get('/middleware-block', [RoutingController::class, 'TestRoute'])
            ->middleware([RoutingController::class, 'Route_Middleware_Block']);

        Route::get('/afterware', [RoutingController::class, 'TestRoute'])
            ->afterMiddleware([RoutingController::class, 'Route_Afterware']);

        Route::get('/middleware-accept-afterware', [RoutingController::class, 'TestRoute'])
            ->middleware([RoutingController::class, 'Route_Middleware_Accept'])
            ->afterMiddleware([RoutingController::class, 'Route_Afterware']);

        Route::get('/middleware-block-afterware', [RoutingController::class, 'TestRoute'])
            ->middleware([RoutingController::class, 'Route_Middleware_Block'])
            ->afterMiddleware([RoutingController::class, 'Route_Afterware']);

    })
    ->middleware([RoutingController::class, 'Group_Middleware_Accept']);


    // Testing all Stages with a Group-Middleware which blocks the request
    Route::group('/block', function() {

        Route::get('/test', [RoutingController::class, 'TestRoute']);

        Route::get('/middleware-accept', [RoutingController::class, 'TestRoute'])
            ->middleware([RoutingController::class, 'Route_Middleware_Accept']);

        Route::get('/middleware-block', [RoutingController::class, 'TestRoute'])
            ->middleware([RoutingController::class, 'Route_Middleware_Block']);

        Route::get('/afterware', [RoutingController::class, 'TestRoute'])
            ->afterMiddleware([RoutingController::class, 'Route_Afterware']);

        Route::get('/middleware-accept-afterware', [RoutingController::class, 'TestRoute'])
            ->middleware([RoutingController::class, 'Route_Middleware_Accept'])
            ->afterMiddleware([RoutingController::class, 'Route_Afterware']);

        Route::get('/middleware-block-afterware', [RoutingController::class, 'TestRoute'])
            ->middleware([RoutingController::class, 'Route_Middleware_Block'])
            ->afterMiddleware([RoutingController::class, 'Route_Afterware']);

    })
    ->middleware([RoutingController::class, 'Group_Middleware_Block']);


    // Testing all Stages with a Group-Afterware
    Route::group('/afterware', function() {

        Route::get('/test', [RoutingController::class, 'TestRoute']);

        Route::get('/middleware-accept', [RoutingController::class, 'TestRoute'])
            ->middleware([RoutingController::class, 'Route_Middleware_Accept']);

        Route::get('/middleware-block', [RoutingController::class, 'TestRoute'])
            ->middleware([RoutingController::class, 'Route_Middleware_Block']);

        Route::get('/afterware', [RoutingController::class, 'TestRoute'])
            ->afterMiddleware([RoutingController::class, 'Route_Afterware']);

        Route::get('/middleware-accept-afterware', [RoutingController::class, 'TestRoute'])
            ->middleware([RoutingController::class, 'Route_Middleware_Accept'])
            ->afterMiddleware([RoutingController::class, 'Route_Afterware']);

        Route::get('/middleware-block-afterware', [RoutingController::class, 'TestRoute'])
            ->middleware([RoutingController::class, 'Route_Middleware_Block'])
            ->afterMiddleware([RoutingController::class, 'Route_Afterware']);

    })
    ->afterMiddleware([RoutingController::class, 'Group_Afterware']);


    // Testing all Stages with a Group-Middleware which accept the request and a Group-Afterware
    Route::group('/accept-afterware', function() {

        Route::get('/test', [RoutingController::class, 'TestRoute']);

        Route::get('/middleware-accept', [RoutingController::class, 'TestRoute'])
            ->middleware([RoutingController::class, 'Route_Middleware_Accept']);

        Route::get('/middleware-block', [RoutingController::class, 'TestRoute'])
            ->middleware([RoutingController::class, 'Route_Middleware_Block']);

        Route::get('/afterware', [RoutingController::class, 'TestRoute'])
            ->afterMiddleware([RoutingController::class, 'Route_Afterware']);

        Route::get('/middleware-accept-afterware', [RoutingController::class, 'TestRoute'])
            ->middleware([RoutingController::class, 'Route_Middleware_Accept'])
            ->afterMiddleware([RoutingController::class, 'Route_Afterware']);

        Route::get('/middleware-block-afterware', [RoutingController::class, 'TestRoute'])
            ->middleware([RoutingController::class, 'Route_Middleware_Block'])
            ->afterMiddleware([RoutingController::class, 'Route_Afterware']);

    })
    ->middleware([RoutingController::class, 'Group_Middleware_Accept'])
    ->afterMiddleware([RoutingController::class, 'Group_Afterware']);


    // Testing all Stages with a Group-Middleware which blocks the request and a Group-Afterware
    Route::group('/block-afterware', function() {

        Route::get('/test', [RoutingController::class, 'TestRoute']);

        Route::get('/middleware-accept', [RoutingController::class, 'TestRoute'])
            ->middleware([RoutingController::class, 'Route_Middleware_Accept']);

        Route::get('/middleware-block', [RoutingController::class, 'TestRoute'])
            ->middleware([RoutingController::class, 'Route_Middleware_Block']);

        Route::get('/afterware', [RoutingController::class, 'TestRoute'])
            ->afterMiddleware([RoutingController::class, 'Route_Afterware']);

        Route::get('/middleware-accept-afterware', [RoutingController::class, 'TestRoute'])
            ->middleware([RoutingController::class, 'Route_Middleware_Accept'])
            ->afterMiddleware([RoutingController::class, 'Route_Afterware']);

        Route::get('/middleware-block-afterware', [RoutingController::class, 'TestRoute'])
            ->middleware([RoutingController::class, 'Route_Middleware_Block'])
            ->afterMiddleware([RoutingController::class, 'Route_Afterware']);

    })
    ->middleware([RoutingController::class, 'Group_Middleware_Block'])
    ->afterMiddleware([RoutingController::class, 'Group_Afterware']);


    // Testing all Stages with a Group-Middleware which accept the request and a Group-Afterware with Parameters
    Route::group('/accept-afterware-parameters/{userId}/{postId}', function() {

        Route::get('/test', [RoutingController::class, 'TestRoute']);

        Route::get('/middleware-accept', [RoutingController::class, 'TestRoute'])
            ->middleware([RoutingController::class, 'Route_Middleware_Accept']);

        Route::get('/middleware-block', [RoutingController::class, 'TestRoute'])
            ->middleware([RoutingController::class, 'Route_Middleware_Block']);

        Route::get('/afterware', [RoutingController::class, 'TestRoute'])
            ->afterMiddleware([RoutingController::class, 'Route_Afterware']);

        Route::get('/middleware-accept-afterware', [RoutingController::class, 'TestRoute'])
            ->middleware([RoutingController::class, 'Route_Middleware_Accept'])
            ->afterMiddleware([RoutingController::class, 'Route_Afterware']);

        Route::get('/middleware-block-afterware', [RoutingController::class, 'TestRoute'])
            ->middleware([RoutingController::class, 'Route_Middleware_Block'])
            ->afterMiddleware([RoutingController::class, 'Route_Afterware']);

    })
    ->middleware([RoutingController::class, 'Group_Middleware_Accept'])
    ->afterMiddleware([RoutingController::class, 'Group_Afterware']);

    Route::post('/post-test', [RoutingController::class, 'TestRoute']);

    Route::put('/put-test', [RoutingController::class, 'TestRoute']);

    Route::patch('/patch-test', [RoutingController::class, 'TestRoute']);

    Route::delete('/delete-test', [RoutingController::class, 'TestRoute']);

    Route::options('/options-test', [RoutingController::class, 'TestRoute']);

    Route::any('/any-test', [RoutingController::class, 'TestRoute']);

    Route::define('get', '/define-get', [RoutingController::class, 'TestRoute']);


    Route::get('/rm-accept/rm-block', [RoutingController::class, 'TestRoute'])
        ->middleware([RoutingController::class, 'Route_Middleware_Accept'])
        ->middleware([RoutingController::class, 'Route_Middleware_Block']);

?>