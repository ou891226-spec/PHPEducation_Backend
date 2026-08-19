<?php

use Illuminate\Support\Facades\Route;

Route::get('/ggg', function () {
    return response()->json([
        'message' => 'PHPEducation Backend API',
    ]);
});
