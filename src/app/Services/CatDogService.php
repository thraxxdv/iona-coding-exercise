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

    public function getBreedsHttpHandler(string | null $breed = null, int | null $page = null, int | null $limit = null )
    {
        if (!$breed) {
            return $this->getAllBreeds($page, $limit);
        } else {
            $params['q'] = $breed;
            // return $this->getBreedImages($params);
        }
    }

    public function getAllBreeds($page, $limit)
    {
        
        $apiSplitLimits = $this->limitSplitter($limit);

        $responses = Http::pool(function (Pool $pool) use ($limit, $page, $apiSplitLimits){
            return [
                $pool->get($this->dogUrl . "/breeds", [
                    'page' => $page,
                    'limit' => !empty($limit) ? $apiSplitLimits['dog'] : null
                ]),
                $pool->get($this->catUrl . "/breeds", [
                    'page' => $page,
                    'limit' => !empty($limit) ? $apiSplitLimits['cat'] : null
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

    /**
     * Function to split 'limit' parameter between cat and dog api
     * putting the ceiling value to the dog and floor value to the cat.
     * Returns null values when digit is empty.
     */
    public function limitSplitter(int | null $digit = null): array
    {
        $half = $digit == 1 && !empty($digit) ? 0.5 : $digit / 2;
        return empty($digit) ? [ 'dog' => null, 'cat' => null ] : [ 'dog' => ceil($half), 'cat' => floor($half)];
    }
}