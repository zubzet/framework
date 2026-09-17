<?php
    use ZubZet\Framework\Routing\Route;

    Route::group('/_zubzet', function() {
        Route::get('/asset-proxy/{assetPath:.+}', function(array $args) {
            zubzet()->assetProxy->serve($args['assetPath']);
        });

        if(config("health_endpoint_enabled", default: true)) {
            Route::get('/health', [ZubZetController::class, 'health']);
        }
    });

    Route::group('/profile', function() {
        Route::post('/change-password', [ProfileController::class, 'changePassword']);
        Route::post('/clear-sessions', [ProfileController::class, 'clearSessions']);
        Route::post('/revoke-session', [ProfileController::class, 'revokeSession']);
        Route::post('/revoke-api-key', [ProfileController::class, 'revokeApiKey']);
        Route::post('/create-api-key', [ProfileController::class, 'createApiKey']);
    });
?>
