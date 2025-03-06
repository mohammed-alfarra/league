<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\League;
use App\Models\GameMatch;
use App\Models\Team;
use App\Services\FixtureService;
use Illuminate\Support\Facades\DB;

class LeagueController extends Controller
{
    /**
     * Display the league simulation page.
     */
    public function index()
    {
        // Get active league or create one if it doesn't exist
        $league = League::first() ?? $this->initializeLeague();

        return view('simulation', [
            'league' => $league
        ]);
    }

    /**
     * Initialize a new league with default teams.
     */
    public function initialize()
    {
        $league = $this->initializeLeague();

        return response()->json([
            'success' => true,
            'message' => 'League initialized successfully',
            'data' => $this->getLeagueData($league)
        ]);
    }

    /**
     * Play the next week of matches.
     */
    public function playNextWeek()
    {
        $league = League::first();

        if (!$league) {
            return response()->json([
                'success' => false,
                'message' => 'League not found'
            ], 404);
        }

        if ($league->is_finished) {
            return response()->json([
                'success' => false,
                'message' => 'League is already finished',
                'data' => $this->getLeagueData($league)
            ]);
        }

        $league->playNextWeek();

        return response()->json([
            'success' => true,
            'message' => 'Week ' . $league->current_week . ' played successfully',
            'data' => $this->getLeagueData($league)
        ]);
    }

    /**
     * Play all remaining matches in the league.
     */
    public function playAllWeeks()
    {
        $league = League::first();

        if (!$league) {
            return response()->json([
                'success' => false,
                'message' => 'League not found'
            ], 404);
        }

        if ($league->is_finished) {
            return response()->json([
                'success' => false,
                'message' => 'League is already finished',
                'data' => $this->getLeagueData($league)
            ]);
        }

        $league->playAllWeeks();

        return response()->json([
            'success' => true,
            'message' => 'All matches played successfully',
            'data' => $this->getLeagueData($league)
        ]);
    }

    /**
     * Update a match result manually.
     */
    public function updateMatch(Request $request)
    {
        $validated = $request->validate([
            'match_id' => 'required|integer|exists:matches,id',
            'home_goals' => 'required|integer|min:0|max:10',
            'away_goals' => 'required|integer|min:0|max:10'
        ]);

        $league = League::first();

        if (!$league) {
            return response()->json([
                'success' => false,
                'message' => 'League not found'
            ], 404);
        }

        $success = $league->updateMatchResult(
            $validated['match_id'],
            $validated['home_goals'],
            $validated['away_goals']
        );

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update match'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Match result updated successfully',
            'data' => $this->getLeagueData($league)
        ]);
    }

    /**
     * Initialize a new league with default teams.
     */
    private function initializeLeague()
    {
        // Delete existing league
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        League::truncate();
        GameMatch::truncate();
        Team::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');


        // Create default teams
        $teams = [
            ['name' => 'Manchester United', 'strength' => 85, 'home_advantage' => 12],
            ['name' => 'Liverpool', 'strength' => 87, 'home_advantage' => 10],
            ['name' => 'Arsenal', 'strength' => 80, 'home_advantage' => 11],
            ['name' => 'Chelsea', 'strength' => 82, 'home_advantage' => 13]
        ];

        foreach ($teams as $teamData) {
            Team::create($teamData);
        }

        // Create league
        $league = League::create([
            'name' => 'Insider Champions League',
            'current_week' => 0,
            'total_weeks' => 0,
            'is_finished' => false
        ]);

        // Generate fixtures
        $fixtureService = new FixtureService();
        $fixtureService->generateFixtures($league);

        return $league;
    }

    /**
     * Get league data for API response.
     */
    private function getLeagueData(League $league)
    {
        $teams = $league->teams()->pluck('id', 'name')->toArray();

        return [
            'league' => [
                'name' => $league->name,
                'currentWeek' => $league->current_week,
                'totalWeeks' => $league->total_weeks,
                'isFinished' => $league->is_finished
            ],
            'teams' => $teams,
            'leagueTable' => $league->getLeagueTable(),
            'weekMatches' => $league->getCurrentWeekMatches(),
            'predictions' => $league->getPredictions()
        ];
    }
}
