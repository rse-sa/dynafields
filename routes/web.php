<?php

use Illuminate\Support\Facades\Route;
use RSE\DynaFields\Http\Controllers\CustomFieldController;

Route::get('create', [CustomFieldController::class, 'create'])->name('create');
Route::post('', [CustomFieldController::class, 'store'])->name('store');
Route::get('{customField}/edit', [CustomFieldController::class, 'edit'])->name('edit');
Route::put('{customField}', [CustomFieldController::class, 'update'])->name('update');
Route::delete('{customField}', [CustomFieldController::class, 'destroy'])->name('destroy');
