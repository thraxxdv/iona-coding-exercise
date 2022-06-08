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
        return $this->fetchApiData("/breeds", $params);
    }

    public function fetchApiData(string $path, array $params)
    {
        $responses = Http::pool(function (Pool $pool) use ($path, $params){

            $dogParams = $params;
            $catParams = $params;
            $apiSplitLimits = $this->limitSplitter($params['limit']);
            $dogParams['limit'] = $apiSplitLimits['dog'];
            $catParams['limit'] = $apiSplitLimits['cat'];

            return [
                $pool->get($this->dogUrl . $path, $dogParams),
                $pool->get($this->catUrl . $path, $catParams),
            ];
        });

        if ($responses[0]->ok() && $responses[1]->ok()) {
            $dogs = $responses[0]->collect();
            $cats = $responses[1]->collect();
            $animals = $dogs->merge($cats);
            return $animals;
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