<?php

use Livewire\Volt\Volt;
use Laravel\Fortify\Features;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\ImpersonationController;

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

    // Impersonation: generate a temporary signed URL that logs in as the specified user when opened
    Route::get('impersonate/login/{user}', [ImpersonationController::class, 'loginAs'])->name('impersonate.login')->middleware('signed');
    // Endpoint to stop impersonation (requires auth)
    Route::post('impersonate/stop', [ImpersonationController::class, 'stop'])->name('impersonate.stop')->middleware('auth');

    Volt::route('roles', 'roles.index')->name('roles.index');

    Volt::route('tutorials', 'tutorials.index')->name('tutorials.index');

    Volt::route('tutorials-access', 'tutorials.access')->name('tutorials.access');

    Volt::route('guide', 'guide.index')->name('guide.index');
    Volt::route('guide/{class}', 'guide.info')->name('guide.info');

    Volt::route('guide-access', 'guide.access')->name('guide.access');
    Volt::route('guide-access/{class}', 'guide.show')->name('guide.show');
    Volt::route('guide-options', 'guide.option-lists')->name('guide.options');

    Volt::route('activity-logs', 'activity-logs')->name('activity-logs');

    Volt::route('email-receivers', 'email-receiver')->name('email-receivers');

    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');
    Volt::route('settings/share-link', 'settings.share-link')->name('share-link');

    Volt::route('managers', 'managers.index')->name('managers.index');

    // Compound Interest Calculator
    Volt::route('compound-calculator', 'compound-interest-calculator')->name('compound-calculator');

    // Export endpoint for compound calculator
    Route::match(['GET','POST'], 'compound-calculator/export', [\App\Http\Controllers\CompoundCalculatorExportController::class, 'export'])->name('compound-calculator.export');

    Volt::route('/manage-opalite','opalite.manage')->name('opalite.manage');
    Volt::route('/opalite-winners','opalite.index')->name('opalite.index');

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

        Route::get('clear-cache', function() {
            Artisan::call('optimize:clear');
            return "Cache Cleared!";
        });
});


Route::get('/run-queue', function () {
    Artisan::call('queue:work --once');
    return 'Queue triggered';
});

Route::get('/migrate', function () {
    Artisan::call('migrate');
    return 'Migration triggered';
});

Route::get('/clear-cache', function () {
    Artisan::call('optimize:clear');
    return 'Cache cleared';
});

require __DIR__.'/auth.php';
