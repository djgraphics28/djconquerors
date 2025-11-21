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
    Volt::route('genealogy/{riscoinId}', 'genealogy.index')->name('genealogy.show');
    Volt::route('my-team','my-team')->name('my-team');
    Volt::route('my-withdrawals', 'my-withdrawals')->name('my-withdrawals');

    Volt::route('book-appointment', 'appointments.create')->name('appointments.book');
    Volt::route('appointments', 'appointments.index')->name('appointments.index');

    Volt::route('users', 'users.index')->name('users.index');

    Volt::route('roles', 'roles.index')->name('roles.index');

    Volt::route('tutorials', 'tutorials.index')->name('tutorials.index');

    Volt::route('tutorials-access', 'tutorials.access')->name('tutorials.access');

    Volt::route('guide', 'guide.index')->name('guide.index');
    Volt::route('guide/{class}', 'guide.info')->name('guide.info');

    Volt::route('guide-access', 'guide.access')->name('guide.access');
    Volt::route('guide-access/{class}', 'guide.show')->name('guide.show');

    Volt::route('activity-logs', 'activity-logs')->name('activity-logs');

    Volt::route('email-receivers', 'email-receiver')->name('email-receivers');

    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');
    Volt::route('settings/share-link', 'settings.share-link')->name('share-link');

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
