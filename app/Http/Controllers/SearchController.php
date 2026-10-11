<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Return live search suggestions (products & brands).
     */
    public function suggestions(Request $request, SearchService $searchService): JsonResponse
    {
        $query = (string) $request->query('q', '');

        $results = $searchService->getSuggestions($query, 6, 6);

        return response()->json($results);
    }
}
