<?php

    use ZubZet\Framework\Routing\Route;

    // Test if all files are loaded instead of only one
    Route::get('/test2', [CoreController::class, 'TestRoute']);
?>