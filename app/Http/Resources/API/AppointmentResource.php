<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'user_id'          => $this->user_id,
            'host_user_id'     => $this->host_user_id,
            'host'             => $this->whenLoaded('host', fn () => [
                'id'    => $this->host->id,
                'name'  => $this->host->name,
                'email' => $this->host->email,
            ]),
            'start_time'       => $this->start_time->toISOString(),
            'end_time'         => $this->end_time->toISOString(),
            'status'           => $this->status,
            'notes'            => $this->notes,
            'venue'            => $this->venue,
            'is_sure_investor' => (bool) $this->is_sure_investor,
            'created_at'       => $this->created_at->toISOString(),
        ];
    }
}
