<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DayController;
use App\Http\Controllers\Api\MealController;
use App\Http\Controllers\Api\MetricController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\WeightController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('logout', [AuthController::class, 'logout']);

Route::middleware('token')->group(function () {
    Route::get('day', DayController::class);

    Route::post('meals', [MealController::class, 'store']);
    Route::get('meals/deleted', [MealController::class, 'deleted']);
    Route::patch('meals/{id}', [MealController::class, 'update'])->whereNumber('id');
    Route::delete('meals/{id}', [MealController::class, 'destroy'])->whereNumber('id');
    Route::post('meals/{id}/restore', [MealController::class, 'restore'])->whereNumber('id');

    Route::get('weights', [WeightController::class, 'index']);
    Route::post('weights', [WeightController::class, 'store']);
    Route::delete('weights/{id}', [WeightController::class, 'destroy'])->whereNumber('id');

    Route::get('calories', [ReportController::class, 'calories']);
    Route::get('progress', [ReportController::class, 'progress']);

    Route::get('metrics', [MetricController::class, 'index']);
    Route::post('metrics', [MetricController::class, 'store']);
    Route::delete('metrics/{id}', [MetricController::class, 'destroy'])->whereNumber('id');

    Route::get('notes', [NoteController::class, 'index']);
    Route::post('notes', [NoteController::class, 'store']);
    Route::delete('notes/{id}', [NoteController::class, 'destroy'])->whereNumber('id');

    Route::get('products', [ProductController::class, 'index']);
    Route::post('products', [ProductController::class, 'store']);
    Route::delete('products/{id}', [ProductController::class, 'destroy'])->whereNumber('id');

    Route::get('settings', [SettingsController::class, 'show']);
    Route::put('settings', [SettingsController::class, 'update']);
});
