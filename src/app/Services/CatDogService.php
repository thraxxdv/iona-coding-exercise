<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class CatDogService {

    private string $dogUrl;
    private string $catUrl;

    public function __construct() {
        $this->dogUrl = 'https://api.thedogapi.com/v1';
        $this->catUrl = 'https://api.thecatapi.com/v1';
    }

    public function getBreedsHttpHandler(string | null $breed = null, array $params )
    {
        if (!$breed) {
            return $this->getAllBreeds($params);
        } else {
        }
    }

    public function getAllBreeds(array $params): Collection
    {
        $apiSplitLimits = $this->limitSplitter($params['limit']);

        $responses = Http::pool(function (Pool $pool) use ($params, $apiSplitLimits){
            return [
                $pool->get($this->dogUrl . "/breeds", [
                    'page' => $params['page'],
                    'limit' => $apiSplitLimits['dog']
                ]),
                $pool->get($this->catUrl . "/breeds", [
                    'page' => $params['page'],
                    'limit' => $apiSplitLimits['cat']
                ]),
            ];
        });

        if ($responses[0]->ok() && $responses[1]->ok()) {
            $dogs = $responses[0]->collect();
            $cats = $responses[1]->collect();
            return $dogs->merge($cats);
        } else {
            abort(500, "An unknown error occured while fetching data.");
        }
    }

    public function limitSplitter(int $digit = null): array
    {
        $half = $digit == 1 ? 0.5 : $digit / 2;
        return [ 'dog' => ceil($half), 'cat' => floor($half)];
    }
}