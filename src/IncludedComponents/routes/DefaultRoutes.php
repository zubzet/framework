<?php
    use ZubZet\Framework\Routing\Route;

    Route::group('/_zubzet', function() {
        Route::get('/asset-proxy/{assetPath:.+}', function(array $args) {
            zubzet()->assetMapper->serve($args['assetPath']);
        });
    });
?>