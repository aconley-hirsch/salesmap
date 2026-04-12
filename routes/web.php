<?php

use App\Http\Controllers\KeyContactController;
use App\Http\Controllers\PasswordSetupController;
use App\Http\Controllers\TerritoryMapController;
use App\Livewire\Admin\KeyContactManager;
use App\Livewire\Admin\SalesTeamMemberForm;
use App\Livewire\Admin\SalesTeamMemberManager;
use App\Livewire\Admin\TerritoryAssignmentMap;
use App\Livewire\Admin\UserManager;
use Illuminate\Support\Facades\Route;

// Public pages
Route::get('/', [TerritoryMapController::class, 'index'])->name('territory-map');
Route::get('/contacts', [KeyContactController::class, 'index'])->name('key-contacts');

// Password setup — GET accepts signed URLs (no auth required) or authenticated users
Route::get('/set-password', [PasswordSetupController::class, 'show'])->name('password.setup');
Route::post('/set-password', [PasswordSetupController::class, 'store'])->middleware('auth')->name('password.setup.store');

Route::middleware(['auth'])->group(function () {
    Route::redirect('dashboard', '/admin/territory-map')->name('dashboard');
});

// Admin routes — require auth + admin + password set
Route::middleware(['auth', 'verified', 'admin', 'password.set'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/sales-team', SalesTeamMemberManager::class)->name('sales-team.index');
    Route::get('/sales-team/create', SalesTeamMemberForm::class)->name('sales-team.create');
    Route::get('/sales-team/{memberId}/edit', SalesTeamMemberForm::class)->name('sales-team.edit');

    Route::get('/territory-map', TerritoryAssignmentMap::class)->name('territory-map.edit');

    Route::get('/key-contacts', KeyContactManager::class)->name('key-contacts.index');

    Route::get('/users', UserManager::class)->name('users.index');
});

require __DIR__.'/settings.php';
