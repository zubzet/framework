<?php
    use ZubZet\Framework\Routing\Route;

    Route::group()->middleware([RoutingController::class, "Group_Middleware_Accept"]);

    Route::get('/routing/callback/accept', function() {
        print_r("test");
    })->middleware([RoutingController::class, "Group_Middleware_Accept"]);

    Route::get('/routing/callback/block', function() {
        print_r("test");
    })->middleware([RoutingController::class, "Group_Middleware_Block"]);