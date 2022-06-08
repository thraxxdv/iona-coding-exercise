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
            $params['q'] = $breed;
            return $this->getBreedImages($params);
        }
    }

    public function indexHttpHandler(array $params)
    {
        return $this->getAnimals($params);
    }

    public function getAnimals(array $params)
    {
        $animals = $this->fetchApiData("/images/search", $params);
        return $animals->map(function($item, $value){
            return [
                'id' => $item['id'],
                'url' => $item['url'],
                'width' => $item['width'],
                'height' => $item['height']
            ];
        });;
    }

    public function getAllBreeds(array $params): Collection
    {
        return $this->fetchApiData("/breeds", $params);
    }
    

    public function getBreedImages(array $params)
    {
        $breedId = $this->getIdByBreedName($params['q']);
        $params['breed_ids'] = $breedId;
        $images = $this->fetchApiData("/images/search", $params, false);
        return $images->map(function($item, $value){
            return [
                'id' => $item['id'],
                'url' => $item['url'],
                'width' => $item['width'],
                'height' => $item['height']
            ];
        });
    }

    public function getIdByBreedName(string $breed)
    {
        $breeds = $this->fetchApiData("/breeds/search", ['q' => $breed, 'limit' => null]);
        if ($breeds->isEmpty()) {
            abort(204, "No results found.");
        } else {
            $ids = $breeds->pluck('id');
            return $ids[0];
        }
    }

    public function fetchApiData(string $path, array $params, $splitLimit = true)
    {
        $responses = Http::pool(function (Pool $pool) use ($path, $params, $splitLimit){

            $dogParams = $params;
            $catParams = $params;
            $apiSplitLimits = $this->limitSplitter($params['limit']);
            $dogParams['limit'] = $splitLimit ? $apiSplitLimits['dog'] : $params['limit'];
            $catParams['limit'] = $splitLimit ? $apiSplitLimits['cat'] : $params['limit'];

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