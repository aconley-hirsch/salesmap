<?php

use App\Http\Controllers\TerritoryMapController;
use Illuminate\Support\Facades\Route;

// Public territory map at the root
Route::get('/', [TerritoryMapController::class, 'index'])->name('territory-map');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/sales-team', \App\Livewire\Admin\SalesTeamMemberManager::class)->name('sales-team.index');
    Route::get('/sales-team/create', \App\Livewire\Admin\SalesTeamMemberForm::class)->name('sales-team.create');
    Route::get('/sales-team/{memberId}/edit', \App\Livewire\Admin\SalesTeamMemberForm::class)->name('sales-team.edit');

    Route::get('/territory-map', \App\Livewire\Admin\TerritoryAssignmentMap::class)->name('territory-map.edit');

    Route::get('/invitations', \App\Livewire\Admin\InvitationManager::class)->name('invitations.index');
});

// Override Fortify's registration route with invitation-required version
Route::middleware(['guest', 'invitation'])->group(function () {
    Route::get('/register', function () {
        return view('pages.auth.register');
    })->name('register');
});

require __DIR__.'/settings.php';
