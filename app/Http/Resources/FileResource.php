<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class FileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'url' => Storage::disk('public')->url($this->path),
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'created_at' => $this->created_at,
        ];
    }
}