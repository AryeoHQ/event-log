<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Comments;

use Illuminate\Contracts\Support\Jsonable;

final class JsonPayload implements Jsonable
{
    /** @var array<string, mixed> */
    private readonly array $data;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->data, $options);
    }
}
