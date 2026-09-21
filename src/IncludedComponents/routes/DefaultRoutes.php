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
            Route::post('/revoke-token', [ZubZetController::class, 'revokeToken']);
            Route::post('/rename-token', [ZubZetController::class, 'renameToken']);
            Route::post('/create-api-key', [ZubZetController::class, 'createApiKey']);
            Route::post('/start-two-factor', [ZubZetController::class, 'startTwoFactor']);
            Route::post('/confirm-two-factor', [ZubZetController::class, 'confirmTwoFactor']);
            Route::post('/disable-two-factor', [ZubZetController::class, 'disableTwoFactor']);
        });

        Route::post('/two-factor/login', [LoginController::class, 'twoFactorLogin']);
        Route::post('/two-factor/refresh', [ZubZetController::class, 'refreshTwoFactor']);
    });
?>
