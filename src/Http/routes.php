<?php

use Sudongxu\OperationLog\Http\Controllers;
use Illuminate\Support\Facades\Route;

use Sudongxu\OperationLog\Models\OperationLog;

if (OperationLog::withRoutes()) {
    Route::get('auth/operation-logs', Controllers\LogController::class.'@index')->name('dcat-admin.operation-log.index');
    Route::delete('auth/operation-logs/{id}', Controllers\LogController::class.'@destroy')->name('dcat-admin.operation-log.destroy');
    Route::post('auth/operation-logs/destroy-all', Controllers\LogController::class.'@destroyAll')->name('dcat-admin.operation-log.destroy-all');
    Route::post('auth/operation-logs/destroy-selected', Controllers\LogController::class.'@destroySelected')->name('dcat-admin.operation-log.destroy-selected');

}
