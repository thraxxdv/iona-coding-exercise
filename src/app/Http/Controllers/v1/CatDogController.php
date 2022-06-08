<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Validation\CatDogValidatedRequest;
use App\Services\CatDogService;

class CatDogController extends Controller
{
    private $catDogService;
    
    public function __construct() {
        $this->catDogService = new CatDogService();
    }

    public function getBreeds(CatDogValidatedRequest $request, string | null $breed = null)
    {
        return [
            'page' => $request->page,
            'limit' => $request->limit,
            'results' => $this->catDogService->getBreedsHttpHandler($breed, $request->validated())
        ];
    }

    public function index(CatDogValidatedRequest $request)
    {
        return [
            'page' => $request->page,
            'limit' => $request->limit,
            'results' => $this->catDogService->indexHttpHandler($request->validated())
        ];
    }

    public function getImage(string $image)
    {
        return $this->catDogService->getImageHttpHandler($image);
    }
}