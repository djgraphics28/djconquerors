<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                            => $this->id,
            'name'                          => $this->name,
            'email'                         => $this->email,
            'email_verified_at'             => $this->email_verified_at?->toISOString(),
            'is_email_verified'             => ! is_null($this->email_verified_at),
            'riscoin_id'                    => $this->riscoin_id,
            'inviters_code'                 => $this->inviters_code,
            'invested_amount'               => (float) $this->invested_amount,
            'birth_date'                    => $this->birth_date?->toDateString(),
            'phone_number'                  => $this->phone_number,
            'gender'                        => $this->gender,
            'occupation'                    => $this->occupation,
            'is_active'                     => (bool) $this->is_active,
            'date_joined'                   => $this->date_joined?->toDateString(),
            'last_login_at'                 => $this->last_login_at?->toISOString(),
            'bonchat_id'                    => $this->bonchat_id,
            'team_id'                       => $this->team_id,
            'primary_language'              => $this->primary_language,
            'secondary_language'            => $this->secondary_language,
            'support_team'                  => $this->support_team,
            'support_group'                 => $this->support_group,
            'assistant_id'                  => $this->assistant_id,
            'avatar_url'                    => $this->getFirstMediaUrl('avatar') ?: null,
            'initials'                      => $this->initials(),
            // Computed (may be expensive for lists — use sparingly)
            'age'                           => $this->age,
            'months_and_days_since_joined'  => $this->months_and_days_since_joined,
            'total_team_members'            => $this->when(
                $request->routeIs('api.team.*') || $request->routeIs('api.dashboard'),
                fn () => $this->total_team_members
            ),
            'direct_team_count'             => $this->when(
                $request->routeIs('api.team.*') || $request->routeIs('api.dashboard'),
                fn () => $this->direct_team_count
            ),
            'team_investment'               => $this->when(
                $request->routeIs('api.team.*') || $request->routeIs('api.dashboard'),
                fn () => (float) $this->team_investment
            ),
            // Relationships
            'team'                          => $this->whenLoaded('team', fn () => [
                'id'   => $this->team->id,
                'name' => $this->team->name,
            ]),
            'manager_level'                 => $this->whenLoaded('managerLevel', fn () => $this->managerLevel?->level),
            'permissions'                   => $this->when(
                $request->routeIs('api.user.profile'),
                fn () => $this->getAllPermissions()->pluck('name')
            ),
            'roles'                         => $this->when(
                $request->routeIs('api.user.profile'),
                fn () => $this->getRoleNames()
            ),
        ];
    }
}
