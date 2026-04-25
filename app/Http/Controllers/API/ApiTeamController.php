<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiTeamController extends Controller
{
    /**
     * GET /api/team
     * Summary stats + direct team members for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user()->load(['invites']);

        $directMembers = $user->invites()
            ->with(['team', 'managerLevel'])
            ->latest('date_joined')
            ->paginate(20);

        return response()->json([
            'status' => true,
            'data'   => [
                'stats' => [
                    'total_team_members' => $user->total_team_members,
                    'direct_team_count'  => $user->direct_team_count,
                    'team_investment'    => (float) $user->team_investment,
                ],
                'direct_members' => UserResource::collection($directMembers)->response()->getData(true),
            ],
        ]);
    }

    /**
     * GET /api/team/genealogy
     * Return the full downline tree (nested structure), max 5 levels deep.
     */
    public function genealogy(Request $request): JsonResponse
    {
        $user = $request->user();
        $tree = $this->buildTree($user, 1, 5);

        return response()->json([
            'status' => true,
            'data'   => $tree,
        ]);
    }

    /**
     * Recursively build the team tree up to $maxDepth levels.
     */
    private function buildTree($user, int $currentDepth, int $maxDepth): array
    {
        $node = [
            'id'             => $user->id,
            'name'           => $user->name,
            'initials'       => $user->initials(),
            'avatar_url'     => $user->getFirstMediaUrl('avatar') ?: null,
            'riscoin_id'     => $user->riscoin_id,
            'invested_amount' => (float) $user->invested_amount,
            'is_active'      => $user->is_active,
            'date_joined'    => $user->date_joined?->toDateString(),
            'level'          => $currentDepth,
            'children'       => [],
        ];

        if ($currentDepth < $maxDepth) {
            $invites = $user->invites()->get();
            foreach ($invites as $invite) {
                $node['children'][] = $this->buildTree($invite, $currentDepth + 1, $maxDepth);
            }
        }

        return $node;
    }

    /**
     * GET /api/team/assistant
     * Return the assistant assigned to the authenticated user.
     */
    public function assistant(Request $request): JsonResponse
    {
        $user      = $request->user();
        $assistant = $user->assistant_id
            ? \App\Models\User::select('id', 'name', 'email', 'phone_number')
                              ->find($user->assistant_id)
            : null;

        return response()->json([
            'status' => true,
            'data'   => $assistant ? new UserResource($assistant) : null,
        ]);
    }
}
