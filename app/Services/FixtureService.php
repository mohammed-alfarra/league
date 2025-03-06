<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\League;
use App\Models\Team;

class FixtureService
{
    /**
     * Generate fixtures for a league.
     */
    public function generateFixtures(League $league): void
    {
        $teams = Team::all()->toArray();
        $teamCount = count($teams);
        
        if ($teamCount < 2) {
            throw new \Exception('At least two teams are required to generate fixtures');
        }
        
        // Each team plays every other team twice (home and away)
        $totalWeeks = ($teamCount - 1) * 2;
        
        // Generate first half (home matches)
        $this->generateRoundRobinFixtures($league, $teams, 1, false);
        
        // Generate second half (return matches, swap home and away)
        $this->generateRoundRobinFixtures($league, $teams, $teamCount, true);
        
        // Update league total weeks
        $league->update(['total_weeks' => $totalWeeks]);
    }

    /**
     * Generate round-robin fixtures for one half of the season.
     */
    private function generateRoundRobinFixtures(League $league, array $teams, int $startWeek, bool $swapHomeAway): void
    {
        $teamCount = count($teams);
        $matchesPerWeek = (int)($teamCount / 2);
        $weeks = $teamCount - 1; // Number of weeks for one round
        
        // Create a copy of teams array for rotation
        $teamsForRotation = $teams;
        
        for ($week = 0; $week < $weeks; $week++) {
            $weekNumber = $startWeek + $week;
            
            for ($i = 0; $i < $matchesPerWeek; $i++) {
                $homeIdx = $i;
                $awayIdx = $teamCount - 1 - $i;
                
                if ($teamsForRotation[$homeIdx]['id'] === $teamsForRotation[$awayIdx]['id']) {
                    continue; // Skip if same team (shouldn’t happen with proper team count)
                }
                
                $homeTeamId = $swapHomeAway 
                    ? $teamsForRotation[$awayIdx]['id'] 
                    : $teamsForRotation[$homeIdx]['id'];
                $awayTeamId = $swapHomeAway 
                    ? $teamsForRotation[$homeIdx]['id'] 
                    : $teamsForRotation[$awayIdx]['id'];
                
                GameMatch::create([
                    'league_id' => $league->id,
                    'home_team_id' => $homeTeamId,
                    'away_team_id' => $awayTeamId,
                    'week' => $weekNumber,
                    'played' => false,
                    'home_goals' => 0,
                    'away_goals' => 0,
                ]);
            }
            
            // Rotate teams for the next week
            $this->rotateTeams($teamsForRotation);
        }
    }

    /**
     * Rotate teams (except first team) for round-robin scheduling.
     */
    private function rotateTeams(array &$teams): void
    {
        $teamCount = count($teams);
        
        if ($teamCount < 2) {
            return;
        }
        
        // Keep first team fixed, rotate others
        $firstTeam = array_shift($teams);
        array_push($teams, $firstTeam);
    }
}