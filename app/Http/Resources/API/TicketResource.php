<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'ticket_number' => $this->ticket_number,
            'subject'       => $this->subject,
            'description'   => $this->description,
            'category'      => $this->category,
            'priority'      => $this->priority,
            'status'        => $this->status,
            'resolved_at'   => $this->resolved_at?->toISOString(),
            'attachments'   => $this->getMedia('attachments')->map(fn ($m) => [
                'id'       => $m->id,
                'file_url' => $m->getFullUrl(),
                'name'     => $m->file_name,
                'mime'     => $m->mime_type,
            ]),
            'comments'      => TicketCommentResource::collection($this->whenLoaded('publicComments')),
            'comment_count' => $this->whenLoaded('publicComments', fn () => $this->publicComments->count()),
            'created_at'    => $this->created_at->toISOString(),
            'updated_at'    => $this->updated_at->toISOString(),
        ];
    }
}
