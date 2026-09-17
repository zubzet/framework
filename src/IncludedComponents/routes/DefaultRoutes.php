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
    });
?>
