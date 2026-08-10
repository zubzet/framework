<?php
    use ZubZet\Framework\Routing\Route;

    Route::group('/guestbook', function() {
        Route::get('', [GuestbookController::class, 'action_index']);
        Route::post('/add', [GuestbookController::class, 'action_add']);
    });
?>
