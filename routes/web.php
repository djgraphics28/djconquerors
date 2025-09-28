<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

// Route::view('dashboard', 'dashboard')
//     ->middleware(['auth', 'verified'])
//     ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Volt::route('dashboard', 'dashboard')->name('dashboard');
    Volt::route('genealogy', 'genealogy.index')->name('genealogy');
    Volt::route('genealogy/{riscoinId}', 'genealogy.show')->name('genealogy.show');
    Volt::route('my-withdrawals', 'my-withdrawals')->name('my-withdrawals');

    // Volt::route('my-withdrawals/{id}', 'my-withdrawals.show')->name('my-withdrawals.show');

    Volt::route('users', 'users.index')->name('users.index');

    Volt::route('roles', 'roles.index')->name('roles.index');

    Volt::route('tutorials', 'tutorials.index')->name('tutorials.index');

    Volt::route('tutorials-access', 'tutorials.access')->name('tutorials.access');

    Volt::route('activity-logs', 'activity-logs')->name('activity-logs');

    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});

require __DIR__.'/auth.php';
