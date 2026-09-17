<?php
    use ZubZet\Framework\Routing\Route;

    Route::group('/_zubzet', function() {
        Route::get('/asset-proxy/{assetPath:.+}', function(array $args) {
            zubzet()->assetProxy->serve($args['assetPath']);
        });

        if(config("health_endpoint_enabled", default: true)) {
            Route::get('/health', [ZubZetController::class, 'health']);
        }

        // What the account components post to, so they work on any page
        Route::group('/profile', function() {
            Route::post('/change-password', [ZubZetController::class, 'changePassword']);
            Route::post('/clear-sessions', [ZubZetController::class, 'clearSessions']);
            Route::post('/revoke-session', [ZubZetController::class, 'revokeSession']);
            Route::post('/rename-session', [ZubZetController::class, 'renameSession']);
            Route::post('/revoke-api-key', [ZubZetController::class, 'revokeApiKey']);
            Route::post('/create-api-key', [ZubZetController::class, 'createApiKey']);
        });
    });
?>
