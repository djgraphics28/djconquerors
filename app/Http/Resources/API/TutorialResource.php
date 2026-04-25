<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TutorialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'description'   => $this->description,
            'video_url'     => $this->video_url,
            'thumbnail_url' => $this->thumbnail_url,
            'video_type'    => $this->video_type,
            'is_published'  => (bool) $this->is_published,
            'created_at'    => $this->created_at->toISOString(),
        ];
    }
}
