<?php

namespace App\Http\Controllers;

use App\Managers\AdminDashboardManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function __construct(
        private AdminDashboardManager $adminDashboardManager,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $stats = $this->adminDashboardManager->getDashboardStats();

        return response()->json(['dashboard' => $stats]);
    }
}
