<?php

namespace App\Http\Controllers;

use App\Models\ClusteringRun;
use App\Services\ClusteringService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly ClusteringService $clustering) {}

    public function index(): View
    {
        $stats = $this->clustering->dashboardStats();
        $latestRuns = ClusteringRun::with('creator')
            ->where('status', ClusteringRun::STATUS_SUKSES)
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard.index', compact('stats', 'latestRuns'));
    }
}
