<?php

namespace App\Http\Controllers;

use App\Managers\AnalyticsManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnalyticsController extends Controller
{
    public function __construct(protected AnalyticsManager $analytics) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'nullable|integer|min:1|max:365',
        ]);

        $days = (int) $request->input('days', 30);

        return response()->json($this->analytics->build(Auth::user(), $days));
    }
}
