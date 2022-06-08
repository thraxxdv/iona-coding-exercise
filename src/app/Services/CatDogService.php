<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;

class CatDogService {

    private string $dogUrl;
    private string $catUrl;

    public function __construct() {
        $this->dogUrl = 'https://api.thedogapi.com/v1';
        $this->catUrl = 'https://api.thecatapi.com/v1';
    }

    public function getBreeds(string | null $breed = null, int | null $page = 0, int | null $limit = null)
    {
        if (!$breed) {
            return $this->getAllBreeds($page, $limit);
        }
    }

    public function getAllBreeds(int | null $page, int | null $limit)
    {
        $params = [
            'page' => $page,
            'limit' => $limit
        ];
        
        return $this->fetchApiData("/breeds", $params);
    }
    
    public function fetchApiData(string $path, $params)
    {
        $page = $params['page'];
        $limits = !empty($params['limit']) ? $this->limitSplitter($params['limit']) : null;

        $responses = Http::pool(function (Pool $pool) use ($path, $page, $limits){
            return [
                $pool->get($this->dogUrl . $path, [
                    'page' => $page,
                    'limit' => is_array($limits) ? $limits['dog'] : null
                ]),
                $pool->get($this->catUrl . $path, [
                    'page' => $page,
                    'limit' => is_array($limits) ? $limits['cat'] : null
                ]),
            ];
        });

        if ($responses[0]->ok() && $responses[1]->ok()) {
            $dogs = $responses[0]->collect();
            $cats = $responses[1]->collect();
            return $dogs->merge($cats);
        } else {
            throw new Exception("Error while fetching data", 500);
        }
    }

    public function limitSplitter(int $digit)
    {
        $half = $digit / 2;
        return [
            'dog' => ceil($half),
            'cat' => floor($half)
        ];
    }
}