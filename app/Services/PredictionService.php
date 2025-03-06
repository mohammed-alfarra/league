<?php

namespace App\Services;

use App\Models\League;
use App\Models\Prediction;

class PredictionService
{
    /**
     * Calculate championship predictions for teams in a league.
     */
    public function calculatePredictions(League $league): void
    {
        // Clear existing predictions
        Prediction::where('league_id', $league->id)->delete();
        
        $teams = $league->teams();
        $remainingMatches = $league->matches()->where('played', false)->get();
        
        // Get current points for each team
        $currentPoints = [];
        $maxPossiblePoints = [];
        $teamStrengths = [];
        
        foreach ($teams as $team) {
            $teamId = $team->id;
            $currentPoints[$teamId] = $team->points;
            $maxPossiblePoints[$teamId] = $team->points;
            $teamStrengths[$teamId] = $team->strength;
        }
        
        // Calculate max possible points for each team
        foreach ($remainingMatches as $match) {
            $homeTeamId = $match->home_team_id;
            $awayTeamId = $match->away_team_id;
            
            // Each team can get max 3 points per match
            $maxPossiblePoints[$homeTeamId] += 3;
            $maxPossiblePoints[$awayTeamId] += 3;
        }
        
        // Find maximum current points
        $maxCurrentPoints = max($currentPoints);
        
        // Calculate base prediction based on current points and remaining matches
        $predictions = [];
        
        foreach ($teams as $team) {
            $teamId = $team->id;
            $pointsToLeader = $maxCurrentPoints - $currentPoints[$teamId];
            
            // If team cannot mathematically win, set probability to 0
            if ($maxPossiblePoints[$teamId] < $maxCurrentPoints) {
                $predictions[$teamId] = 0;
                continue;
            }
            
            // Default probability based on current position and team strength
            $baseProbability = 100 - ($pointsToLeader * 15);
            
            // Adjust by team strength (higher strength = better chances)
            $strengthFactor = $teamStrengths[$teamId] / 100;
            $adjustedProbability = $baseProbability * $strengthFactor;
            
            // Ensure positive value
            $predictions[$teamId] = max(1, $adjustedProbability);
        }
        
        // Special case: If one team is mathematically guaranteed to win
        $leaderCannotBeCaught = true;
        $leaderId = array_search($maxCurrentPoints, $currentPoints);
        
        foreach ($teams as $team) {
            $teamId = $team->id;
            if ($teamId != $leaderId && $maxPossiblePoints[$teamId] >= $maxCurrentPoints) {
                $leaderCannotBeCaught = false;
                break;
            }
        }
        
        if ($leaderCannotBeCaught && $leaderId !== false) {
            // Leader has 100%, others 0%
            foreach ($teams as $team) {
                $teamId = $team->id;
                $predictions[$teamId] = ($teamId == $leaderId) ? 100 : 0;
            }
        } else {
            // Normalize predictions to sum to 100%
            $totalProbability = array_sum($predictions);
            
            if ($totalProbability > 0) {
                foreach ($predictions as $teamId => $probability) {
                    $predictions[$teamId] = round(($probability / $totalProbability) * 100, 1);
                }
            }
        }
        
        // Save predictions to database
        foreach ($predictions as $teamId => $probability) {
            Prediction::create([
                'league_id' => $league->id,
                'team_id' => $teamId,
                'probability' => $probability
            ]);
        }
    }

    /**
     * Get predictions for a league.
     */
    public function getPredictions(League $league): array
    {
        $predictions = Prediction::where('league_id', $league->id)->get();
        $result = [];
        
        foreach ($predictions as $prediction) {
            $result[$prediction->team_id] = $prediction->probability;
        }
        
        return $result;
    }
}