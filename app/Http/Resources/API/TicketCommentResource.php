<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'ticket_id'  => $this->ticket_id,
            'body'       => $this->body,
            'author'     => $this->whenLoaded('user', fn () => [
                'id'       => $this->user->id,
                'name'     => $this->user->name,
                'initials' => $this->user->initials(),
                'avatar_url' => $this->user->getFirstMediaUrl('avatar') ?: null,
            ]),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
