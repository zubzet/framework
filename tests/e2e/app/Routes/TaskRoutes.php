<?php
    use ZubZet\Framework\Routing\Route;

    Route::group('/tasks', function() {
        Route::get('/dispatch/{rows:\d+}', [TaskProbeController::class, 'action_dispatch']);
        Route::get('/dispatch-failing', [TaskProbeController::class, 'action_dispatchFailing']);
        Route::get('/dispatch-queue/{queue:[a-z]+}', [TaskProbeController::class, 'action_dispatchToQueue']);
        Route::get('/dispatch-delayed', [TaskProbeController::class, 'action_dispatchDelayed']);
        Route::get('/dispatch-unknown', [TaskProbeController::class, 'action_dispatchUnknown']);
        Route::get('/dispatch-bad-payload', [TaskProbeController::class, 'action_dispatchBadPayload']);
        Route::get('/dispatch-owned', [TaskProbeController::class, 'action_dispatchOwned']);
        Route::get('/status/{id:\d+}', [TaskProbeController::class, 'action_status']);
        Route::get('/pending', [TaskProbeController::class, 'action_pending']);
        Route::get('/mine', [TaskProbeController::class, 'action_mine']);
    });
?>
