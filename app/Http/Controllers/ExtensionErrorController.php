<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExtensionErrorsRequest;
use App\Managers\ExtensionErrorManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExtensionErrorController extends Controller
{
    public function __construct(
        protected ExtensionErrorManager $errors
    ) {}

    /**
     * Receive a batch of error reports from the browser extension.
     *
     * Unauthenticated reports are accepted on purpose — the most valuable
     * reports are the ones from a broken auth or startup path. The bearer
     * token is used when present, purely to attribute the report.
     */
    public function store(StoreExtensionErrorsRequest $request): JsonResponse
    {
        $userId = $this->resolveUserId($request);

        $stored = $this->errors->record($request->validated('errors'), $userId);

        return response()->json([
            'message' => 'Reports received',
            'stored' => $stored,
        ], 202);
    }

    /**
     * Admin triage view: what is failing, how often, for how many people.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'nullable|integer|min:1|max:90',
            'limit' => 'nullable|integer|min:1|max:500',
        ]);

        return response()->json([
            'data' => $this->errors->triageReport(
                (int) $request->input('days', 7),
                (int) $request->input('limit', 100)
            ),
        ]);
    }

    private function resolveUserId(Request $request): ?int
    {
        if (Auth::check()) {
            return (int) Auth::id();
        }

        $user = Auth::guard('sanctum')->user();

        return $user?->id;
    }
}
