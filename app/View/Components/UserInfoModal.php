<?php

namespace App\View\Components;

use App\Models\User;
use Illuminate\View\Component;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class UserInfoModal extends Component
{
    public $userId;
    public $user;
    public $activityLogs;
    public $totalWithdrawals;
    public $capitalStatus;
    public $teamCount;
    public $directTeamCount;

    /**
     * Create a new component instance.
     */
    public function __construct($userId = null)
    {
        $this->userId = $userId;

        if ($userId) {
            $this->loadUserData($userId);
        }
    }

    /**
     * Load user data
     */
    private function loadUserData($userId)
    {
        $this->user = User::with(['roles', 'withdrawals', 'inviter', 'assistant', 'invites'])
            ->find($userId);

        if ($this->user) {
            // Get activity logs
            $this->activityLogs = Activity::where('subject_id', $userId)
                ->where('subject_type', User::class)
                ->with('causer')
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();

            // Calculate withdrawals
            $this->totalWithdrawals = $this->user->withdrawals()
                ->where('status', 'paid')
                ->sum('amount');

            // Calculate capital recovery status
            $this->capitalStatus = $this->getCapitalRecoveryStatus();

            // Get team counts
            $this->directTeamCount = $this->user->invites->count();
            $this->teamCount = $this->getTeamCount($this->user->id);
        }
    }

    /**
     * Get capital recovery status
     */
    private function getCapitalRecoveryStatus()
    {
        if (!$this->user) {
            return [
                'status' => 'unknown',
                'label' => 'Unknown',
                'color' => 'gray',
            ];
        }

        $investedAmount = $this->user->invested_amount ?? 0;

        if ($investedAmount == 0) {
            return [
                'status' => 'no_investment',
                'label' => 'No Investment',
                'color' => 'gray',
            ];
        }

        if ($this->totalWithdrawals < $investedAmount) {
            $percentage = ($this->totalWithdrawals / $investedAmount) * 100;
            return [
                'status' => 'recovering',
                'label' => 'Recovering (' . number_format($percentage, 1) . '%)',
                'color' => 'yellow',
            ];
        } else {
            return [
                'status' => 'recovered',
                'label' => 'Capital Recovered',
                'color' => 'green',
            ];
        }
    }

    /**
     * Get total team count recursively
     */
    private function getTeamCount($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return 0;
        }

        $directInvites = User::where('inviters_code', $user->riscoin_id)->count();
        $totalCount = $directInvites;

        $directUsers = User::where('inviters_code', $user->riscoin_id)->get();

        foreach ($directUsers as $directUser) {
            $totalCount += $this->getTeamCount($directUser->id);
        }

        return $totalCount;
    }

    /**
     * Check if current user is admin
     */
    public function isAdmin()
    {
        return Auth::check() && Auth::user()->hasRole('admin');
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.user-info-modal');
    }
}
