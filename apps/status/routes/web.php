<?php

use App\Http\Controllers\Auth\AdminInviteController;
use App\Http\Controllers\Auth\AdminSetupController;
use App\Http\Controllers\Auth\SubscriberController;
use App\Http\Controllers\PublicStatus\HistoryPageController;
use App\Http\Controllers\PublicStatus\IncidentPageController;
use App\Http\Controllers\PublicStatus\StatusPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', StatusPageController::class)->name('status.index');
Route::get('/history', HistoryPageController::class)->name('status.history');
Route::get('/incidents/{incident:slug}', IncidentPageController::class)->name('status.incidents.show');

Route::get('/admin/setup', [AdminSetupController::class, 'show'])->name('admin.setup.show');
Route::post('/admin/setup', [AdminSetupController::class, 'store'])->name('admin.setup.store');
Route::get('/admin/invites/{token}', [AdminInviteController::class, 'show'])->name('admin.invites.show');
Route::post('/admin/invites/{token}', [AdminInviteController::class, 'store'])->name('admin.invites.store');

Route::get('/status/subscribers/confirm/{token}', [SubscriberController::class, 'confirm'])->name('status.subscribers.confirm');
Route::get('/status/subscribers/unsubscribe/{token}', [SubscriberController::class, 'unsubscribe'])->name('status.subscribers.unsubscribe');
