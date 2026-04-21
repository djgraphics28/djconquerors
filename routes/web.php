<?php

use Livewire\Volt\Volt;
use Laravel\Fortify\Features;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\GenealogyPdfController;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

// Route::view('dashboard', 'dashboard')
//     ->middleware(['auth', 'verified'])
//     ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Volt::route('dashboard', 'dashboard')->name('dashboard');
    Volt::route('genealogy', 'genealogy.index')->name('genealogy');
    Route::get('genealogy/pdf', [GenealogyPdfController::class, 'generate'])->name('genealogy.pdf');
    Route::get('genealogy/{riscoinId}/pdf', [GenealogyPdfController::class, 'generate'])->name('genealogy.pdf.show');
    Volt::route('genealogy/{riscoinId}', 'genealogy.index')->name('genealogy.show');
    Volt::route('my-team','my-team')->name('my-team');
    Volt::route('my-withdrawals', 'my-withdrawals')->name('my-withdrawals');
    Volt::route('withdrawals', 'withdrawals.index')->name('withdrawals.index');
    // Volt::route('withdrawals/create', 'withdrawals.create')->name('withdrawals.create');

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
    Volt::route('settings/reply-to-sir-martin', 'settings.reply-to-sir-martin')->name('reply-to-sir-martin');

    Volt::route('managers', 'managers.index')->name('managers.index');

    // Compound Interest Calculator
    Volt::route('compound-calculator', 'compound-interest-calculator')->name('compound-calculator');

    // Calculator logging endpoint
    Route::post('calculator/log', function(\Illuminate\Http\Request $request) {
        try {
            \App\Models\CalculatorUsageLog::create([
                'user_id' => auth()->id(),
                'calculator_type' => 'compound_interest',
                'invested_amount' => $request->input('initial_investment'),
                'first_reward' => $request->input('first_reward'),
                'signals_per_day' => $request->input('signals_per_day'),
                'number_of_days' => $request->input('days'),
                'is_first_time' => $request->input('is_first_time', false),
                'final_amount' => $request->input('final_amount'),
                'calculation_data' => $request->all(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Log::error('Calculator usage log failed: ' . $e->getMessage());
            return response()->json(['success' => false], 500);
        }
    })->name('calculator.log');

    // Calculator Usage Logs (Admin only)
    Volt::route('calculator-usage-logs', 'calculator-usage-logs')->name('calculator-usage-logs');

    // Export endpoint for compound calculator
    Route::match(['GET','POST'], 'compound-calculator/export', [\App\Http\Controllers\CompoundCalculatorExportController::class, 'export'])->name('compound-calculator.export');

    Volt::route('/manage-opalite','opalite.manage')->name('opalite.manage');
    Volt::route('/opalite-winners','opalite.index')->name('opalite.index');

    // Reply Templates - Using Livewire Volt
    Volt::route('reply-template', 'reply-template.index')->name('reply-template.index')->middleware('can:reply-template.access');

    // Celebrations: Birthday Celebrants & Monthly Milestones
    Volt::route('celebrations', 'celebrations.index')->name('celebrations.index')->middleware('can:celebrations.view');
    Volt::route('celebrations/{riscoinId}', 'celebrations.index')->name('celebrations.show')->middleware('can:celebrations.view');

    // Leaderboards: Top Inviters & Top Assisters
    Volt::route('leaderboards', 'leaderboards.index')->name('leaderboards.index')->middleware('can:leaderboards.view');

    // Support Tickets (all authenticated users)
    Volt::route('tickets', 'tickets.index')->name('tickets.index');
    Volt::route('tickets/create', 'tickets.create')->name('tickets.create');
    Volt::route('tickets/{ticketId}', 'tickets.show')->name('tickets.show');

    // FAQ (all authenticated users can browse; admin can manage)
    Volt::route('faq', 'faq.index')->name('faq.index');
    Volt::route('faq/manage', 'faq.manage')->name('faq.manage')->middleware('can:faq.manage');

    // Chatbot logs (admin only)
    Volt::route('chatbot/logs', 'chatbot.logs')->name('chatbot.logs')->middleware('can:chatbot.manage');

    // Donate
    Volt::route('donate', 'donate.index')->name('donate.index');
    Volt::route('donate/manage', 'donate.manage')->name('donate.manage')->middleware('can:donate.manage');

    // Teams Management
    Volt::route('teams', 'teams.index')->name('teams.index')->middleware('can:teams.view');

    // Riscoin Links Management (Admin Only)
    Volt::route('riscoin-links', 'riscoin-links.index')->name('riscoin-links.index')->middleware('can:riscoin-links.manage');

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
