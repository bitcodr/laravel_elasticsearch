<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'query' => $this->query,
            'reference' => $this->reference,
            'suggestion' => SearchSuggestionCollection::collection($this->suggestion),
            'did_you_mean' => [
                'designers' => ProductDesignersCollection::collection($this->designers),
                'names' => ProductNamesCollection::collection($this->names)
            ],
            'products' => ProductsCollection::collection($this->products),
        ];
    }
}
