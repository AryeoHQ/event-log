<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Categories;

use Illuminate\Http\Request;

final class JsonResource extends \Illuminate\Http\Resources\Json\JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
        ];
    }
}
