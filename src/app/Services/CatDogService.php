<?php

namespace App\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class CatDogService {

    private string $dogUrl;
    private string $catUrl;
    private string $dogKey;
    private string $catKey;

    public function __construct() {
        $this->dogUrl = 'https://api.thedogapi.com/v1';
        $this->catUrl = 'https://api.thecatapi.com/v1';
        $this->dogKey = env('DOG_API_KEY');
        $this->catKey = env('CAT_API_KEY');
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

    /**
     * Returns an single associative array when corresponding
     * image is found on either api
     *
     * @param string $image
     * @return array
     */
    public function getImageHttpHandler(string $image): array
    {
        $image = $this->fetchApiData('/images' . "/" . $image, ['limit' => 1]);
        return $image->isEmpty() ? abort(400, "No results found.") : [
            'id' => $image['id'],
            'url' => $image['url'],
            'width' => $image['width'],
            'height' => $image['height']
        ];
    }

    /**
     * Function to get all breeds
     *
     * @param array $params
     * @return Collection
     */
    public function getAllBreeds(array $params): Collection
    {
        return $this->fetchApiData("/breeds", $params);
    }
    
    /**
     * Returns a collection of images according to
     * search term provided
     *
     * @param array $params
     * @return Collection
     */
    public function getBreedImages(array $params): Collection
    {
        // api only allows search images by breed id, and not by breed name directly
        // need to get breed id first by calling a separate endpoint
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

    /**
     * Undocumented function
     *
     * @param string $breed
     * @return int 
     * @return string
     */
    public function getIdByBreedName(string $breed): int | string
    {
        $breeds = $this->fetchApiData("/breeds/search", ['q' => $breed, 'limit' => null]);
        if ($breeds->isEmpty()) {
            abort(400, "No results found.");
        } else {
            $ids = $breeds->pluck('id');
            return $ids[0];
        }
    }

    /**
     * Method for fetching data from both APIs
     *
     * @param string $path URL path e.g. /v1/images/search
     * @param array $params array that will serve as URL parameters for the request
     * @param boolean $splitLimit when true, it splits the 'limit' parameter between the cat and dog
     * but when false it sets the specified limit to both APIs
     * @return Collection
     */
    public function fetchApiData(string $path, array $params, $splitLimit = true): Collection
    {
        try {
            if ($params['limit'] == 1 && $path == "/breeds/search") {
                $response = Http::withHeaders(['x-api-key' => $this->dogKey])->get($this->dogUrl . $path, $params);
                return $response->collect();
            } else {
                $responses = Http::pool(function (Pool $pool) use ($path, $params, $splitLimit) {
                    $dogParams = $params;
                    $catParams = $params;
                    $apiSplitLimits = $this->limitSplitter($params['limit']);
                    $dogParams['limit'] = $splitLimit ? $apiSplitLimits['dog'] : $params['limit'];
                    $catParams['limit'] = $splitLimit ? $apiSplitLimits['cat'] : $params['limit'];
                    
                    return [
                        $pool->withHeaders(['x-api-key' => $this->dogKey])->get($this->dogUrl . $path, $dogParams),
                        $pool->withHeaders(['x-api-key' => $this->catKey])->get($this->catUrl . $path, $catParams),
                    ];
                });
        
                $dogs = $responses[0]->ok() ? $responses[0]->collect() : collect([]);
                $cats = $responses[1]->ok() ? $responses[1]->collect() : collect([]);
                $animals = $dogs->merge($cats);
                return $animals;
            }
        } catch (\Throwable $th) {
            abort(500, "An error occured while fetching data.");
        }
    }
    
    /**
     * Splits limit into 2, giving the ceiling to the dog when there's a .5 
     * remaining, and giving the floor to the cat.
     *
     * @param integer|null $digit
     * @return array
     */
    public function limitSplitter(int $digit = null): array
    {
        $half = $digit == 1 ? 0.5 : $digit / 2;
        return [ 'dog' => ceil($half), 'cat' => floor($half)];
    }
}