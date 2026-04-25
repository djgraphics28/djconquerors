<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiDashboardController extends Controller
{
    /**
     * GET /api/dashboard
     * Aggregated stats for the authenticated user's dashboard.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user()->load(['team', 'managerLevel']);

        return response()->json([
            'status' => true,
            'data'   => [
                'user'  => new UserResource($user),
                'stats' => [
                    'invested_amount'    => (float) $user->invested_amount,
                    'total_team_members' => $user->total_team_members,
                    'direct_team_count'  => $user->direct_team_count,
                    'team_investment'    => (float) $user->team_investment,
                ],
                'leaderboard_rank' => $this->getLeaderboardRank($user),
            ],
        ]);
    }

    /**
     * GET /api/leaderboard
     * Top 50 users by total team investment.
     * ?metric=team_investment|team_size|personal_investment (default: team_investment)
     */
    public function leaderboard(Request $request): JsonResponse
    {
        $metric = $request->query('metric', 'team_investment');

        $users = User::where('is_active', true)
            ->with(['managerLevel'])
            ->get(['id', 'name', 'riscoin_id', 'invested_amount', 'date_joined', 'assistant_id'])
            ->map(function ($u) use ($metric) {
                return [
                    'id'             => $u->id,
                    'name'           => $u->name,
                    'initials'       => $u->initials(),
                    'avatar_url'     => $u->getFirstMediaUrl('avatar') ?: null,
                    'riscoin_id'     => $u->riscoin_id,
                    'invested_amount' => (float) $u->invested_amount,
                    'total_team_members' => $u->total_team_members,
                    'team_investment' => (float) $u->team_investment,
                    'manager_level'  => $u->managerLevel?->level,
                    '_sort_key'      => match ($metric) {
                        'team_size'           => $u->total_team_members,
                        'personal_investment' => (float) $u->invested_amount,
                        default               => (float) $u->team_investment,
                    },
                ];
            })
            ->sortByDesc('_sort_key')
            ->values()
            ->take(50)
            ->map(function ($u, $index) {
                unset($u['_sort_key']);
                $u['rank'] = $index + 1;
                return $u;
            });

        return response()->json([
            'status' => true,
            'data'   => $users,
        ]);
    }

    /**
     * Compute the authenticated user's current leaderboard rank by team investment.
     */
    private function getLeaderboardRank(User $authUser): ?int
    {
        $rank = User::where('is_active', true)
            ->get(['id', 'invested_amount', 'riscoin_id'])
            ->map(fn ($u) => ['id' => $u->id, 'ti' => (float) $u->team_investment])
            ->sortByDesc('ti')
            ->values()
            ->search(fn ($u) => $u['id'] === $authUser->id);

        return $rank !== false ? $rank + 1 : null;
    }
}
