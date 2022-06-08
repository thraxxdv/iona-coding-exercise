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

    public function getBreedsHttpHandler(string | null $breed = null, array $params)
    {
        if (!$breed) {
            return $this->getAllBreeds($params);
        } else {
            $params['q'] = $breed;
            return $this->getBreedImages($params);
        }
    }

    public function getAllBreeds(array $params)
    {
        return $this->fetchApiData("/breeds", $params);
    }

    public function getBreedId(string $breed)
    {
        $breeds = $this->fetchApiData("/breeds/search", ['q' => $breed]);
        if ($breeds->isEmpty()) {
            abort(204, "No results found.");
        } else {
            $ids = $breeds->pluck('id');
            return $ids[0];
        }
    }

    public function getBreedImages(array $params)
    {
        $id = $this->getBreedId($params['q']);
        $params['breed_id'] = $id;
        $images = $this->fetchApiData("/images/search", $params);
        return $images->map(function($item, $key){
            return [
                'id' => $item['id'],
                'url' => $item['url'],
                'width' => $item['width'],
                'height' => $item['height'],
            ];
        });
    }

    public function fetchApiData(string $path, array $params)
    {

        if (array_key_exists('limit', $params)) {
            $catParams = $params;
            $dogParams = $params;
            if ($path !== "/images/search") {
                $limits = $this->limitSplitter($params['limit']);
                $catParams['limit'] = $limits['cat'];
                $dogParams['limit'] = $limits['dog'];
            }
        } else {
            $catParams = $params;
            $dogParams = $params;
        }

        $responses = Http::pool(function (Pool $pool) use ($path, $dogParams, $catParams){
            return [
                $pool->get($this->dogUrl . $path, $dogParams),
                $pool->get($this->catUrl . $path, $catParams),
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
        $half = $digit == 1 ? 0.5 : $digit / 2;
        return [
            'dog' => ceil($half),
            'cat' => floor($half)
        ];
    }
}