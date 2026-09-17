<?php
    use ZubZet\Framework\Routing\Route;

    Route::group('/_zubzet', function() {
        Route::get('/asset-proxy/{assetPath:.+}', function(array $args) {
            zubzet()->assetProxy->serve($args['assetPath']);
        });

        if(config("health_endpoint_enabled", default: true)) {
            Route::get('/health', [ZubZetController::class, 'health']);
        }

        Route::group('/organization', function() {
            Route::post('/rename', [ZOrganizationController::class, 'rename']);
            Route::post('/members/{id:\d+}/roles', [ZOrganizationController::class, 'roles']);
            Route::post('/invites', [ZOrganizationController::class, 'invite']);
            Route::post('/invites/{id:\d+}/revoke', [ZOrganizationController::class, 'revoke']);
            Route::post('/invitations/{token}/accept', [ZOrganizationController::class, 'accept']);
        });

        // What the account components post to, so they work on any page
        Route::group('/profile', function() {
            Route::post('/change-password', [ZubZetController::class, 'changePassword']);
            Route::post('/clear-sessions', [ZubZetController::class, 'clearSessions']);
            Route::post('/revoke-token', [ZubZetController::class, 'revokeToken']);
            Route::post('/rename-token', [ZubZetController::class, 'renameToken']);
            Route::post('/create-api-key', [ZubZetController::class, 'createApiKey']);
        });
    });
?>
