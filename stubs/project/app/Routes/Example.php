<?php
    use ZubZet\Framework\Routing\Route;

    Route::get('/example', [IndexController::class, 'action_index']);
?>
