<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuideResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'slug'        => $this->slug,
            'description' => $this->description,
            'order'       => $this->order,
            'category'    => $this->whenLoaded('option', fn () => [
                'id'   => $this->option->id,
                'name' => $this->option->name,
            ]),
            'items'       => $this->whenLoaded('items', fn () =>
                $this->items->sortBy('order')->values()->map(fn ($item) => [
                    'id'      => $item->id,
                    'title'   => $item->title,
                    'content' => $item->content,
                    'order'   => $item->order,
                ])
            ),
            'created_at'  => $this->created_at->toISOString(),
        ];
    }
}
