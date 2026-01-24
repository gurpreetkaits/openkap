<?php

namespace App\Http\Controllers;

use App\Managers\ChangelogManager;
use Illuminate\Http\Request;

class ChangelogController extends Controller
{
    public function __construct(
        private ChangelogManager $changelogManager
    ) {}

    public function index(Request $request)
    {
        $weeksBack = 12;
        $selectedWeek = $request->query('week');

        $weeklyChangelogs = $this->changelogManager->getChangelogByWeek($weeksBack);

        // If a specific week is selected, filter to that week
        $currentWeekData = null;
        if ($selectedWeek) {
            $currentWeekData = $weeklyChangelogs->first(function ($week) use ($selectedWeek) {
                return $week['week_start']->format('Y-m-d') === $selectedWeek;
            });
        }

        // Default to first (most recent) week if no selection or not found
        if (! $currentWeekData && $weeklyChangelogs->isNotEmpty()) {
            $currentWeekData = $weeklyChangelogs->first();
            $selectedWeek = $currentWeekData['week_start']->format('Y-m-d');
        }

        return view('changelog', [
            'weeklyChangelogs' => $weeklyChangelogs,
            'currentWeekData' => $currentWeekData,
            'selectedWeek' => $selectedWeek,
        ]);
    }
}
