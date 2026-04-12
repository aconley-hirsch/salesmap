<?php

use App\Http\Controllers\KeyContactController;
use App\Http\Controllers\TerritoryMapController;
use App\Livewire\Admin\InvitationManager;
use App\Livewire\Admin\KeyContactManager;
use App\Livewire\Admin\SalesTeamMemberForm;
use App\Livewire\Admin\SalesTeamMemberManager;
use App\Livewire\Admin\TerritoryAssignmentMap;
use Illuminate\Support\Facades\Route;

// Public pages
Route::get('/', [TerritoryMapController::class, 'index'])->name('territory-map');
Route::get('/contacts', [KeyContactController::class, 'index'])->name('key-contacts');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/sales-team', SalesTeamMemberManager::class)->name('sales-team.index');
    Route::get('/sales-team/create', SalesTeamMemberForm::class)->name('sales-team.create');
    Route::get('/sales-team/{memberId}/edit', SalesTeamMemberForm::class)->name('sales-team.edit');

    Route::get('/territory-map', TerritoryAssignmentMap::class)->name('territory-map.edit');

    Route::get('/invitations', InvitationManager::class)->name('invitations.index');

    Route::get('/key-contacts', KeyContactManager::class)->name('key-contacts.index');
});

// Override Fortify's registration route with invitation-required version
Route::middleware(['guest', 'invitation'])->group(function () {
    Route::get('/register', function () {
        return view('pages.auth.register');
    })->name('register');
});

require __DIR__.'/settings.php';
