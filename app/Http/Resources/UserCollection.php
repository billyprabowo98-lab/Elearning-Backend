<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserCollection extends ResourceCollection
{
    public $collects = UserResource::class;

    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'data'    => $this->collection,
            'meta'    => [
                'current_page' => $this->currentPage(),
                'per_page'     => $this->perPage(),
                'total'        => $this->total(),
                'last_page'    => $this->lastPage(),
                'from'         => $this->firstItem(),
                'to'           => $this->lastItem(),
            ],
            'links' => [
                'first' => $this->url(1),
                'last'  => $this->url($this->lastPage()),
                'prev'  => $this->previousPageUrl(),
                'next'  => $this->nextPageUrl(),
            ],
        ];
    }
}
