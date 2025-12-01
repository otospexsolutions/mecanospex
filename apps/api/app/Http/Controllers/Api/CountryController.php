<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\JsonResponse;

class CountryController extends Controller
{
    /**
     * Get all active countries
     */
    public function index(): JsonResponse
    {
        $countries = Country::where('is_active', true)
            ->with('taxRates')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $countries,
        ]);
    }

    /**
     * Get a single country by code
     */
    public function show(string $code): JsonResponse
    {
        $country = Country::with('taxRates')
            ->findOrFail($code);

        return response()->json([
            'data' => $country,
        ]);
    }
}
