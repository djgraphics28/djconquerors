<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'amount'         => (float) $this->amount,
            'status'         => $this->status,
            'paid_date'      => $this->paid_date ? \Carbon\Carbon::parse($this->paid_date)->toDateString() : null,
            'transaction_id' => $this->transaction_id,
            'created_at'     => $this->created_at->toISOString(),
            'updated_at'     => $this->updated_at->toISOString(),
        ];
    }
}
