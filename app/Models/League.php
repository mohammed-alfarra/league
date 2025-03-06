<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\FixtureService;
use App\Services\PredictionService;

class League extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'current_week',
        'total_weeks',
        'is_finished',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_finished' => 'boolean',
    ];

    /**
     * Get all matches in this league.
     */
    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }

    /**
     * Get all teams in this league.
     */
    public function teams()
    {
        // Get all teams that participate in matches in this league
        $teamIds = $this->matches()
            ->select('home_team_id', 'away_team_id')
            ->get()
            ->flatMap(function ($match) {
                return [$match->home_team_id, $match->away_team_id];
            })
            ->unique();

        return Team::whereIn('id', $teamIds)->get();
    }

    /**
     * Start or restart the league.
     */
    public function start(): void
    {
        // Reset league data
        $this->update([
            'current_week' => 0,
            'is_finished' => false,
        ]);

        // Reset team stats
        foreach ($this->teams() as $team) {
            $team->resetStats();
        }

        // Delete all existing matches
        $this->matches()->delete();

        // Generate new fixtures
        $fixtureService = new FixtureService();
        $fixtureService->generateFixtures($this);

        // Update total weeks
        $totalWeeks = $this->matches()->max('week');
        $this->update(['total_weeks' => $totalWeeks]);
    }

    /**
     * Play next week's matches.
     */
    public function playNextWeek(): array
    {
        if ($this->is_finished) {
            return [];
        }

        $this->current_week += 1;
        $this->save();

        $matches = $this->matches()->where('week', $this->current_week)->get();

        foreach ($matches as $match) {
            $match->playMatch();
        }

        // Check if the league is finished
        if ($this->current_week >= $this->total_weeks) {
            $this->is_finished = true;
            $this->save();
        }

        // Update championship predictions if we're in the last 3 weeks
        if ($this->current_week >= $this->total_weeks - 3) {
            $this->updatePredictions();
        }

        return $matches->toArray();
    }

    /**
     * Play all remaining weeks.
     */
    public function playAllWeeks(): array
    {
        $allMatches = [];

        while (!$this->is_finished) {
            $weekMatches = $this->playNextWeek();
            $allMatches[$this->current_week] = $weekMatches;
        }

        return $allMatches;
    }

    /**
     * Update championship predictions.
     */
    public function updatePredictions(): void
    {
        $predictionService = new PredictionService();
        $predictionService->calculatePredictions($this);
    }

    /**
     * Get matches for the current week.
     */
    public function getCurrentWeekMatches(): array
    {
        if ($this->current_week === 0) {
            return [];
        }

        return $this->matches()
            ->where('week', $this->current_week)
            ->get()
            ->toArray();
    }

    /**
     * Get matches for a specific week.
     */
    public function getMatchesByWeek(int $week): array
    {
        return $this->matches()
            ->where('week', $week)
            ->get()
            ->toArray();
    }

    /**
     * Get remaining matches (not played yet).
     */
    public function getRemainingMatches(): array
    {
        return $this->matches()
            ->where('played', false)
            ->get()
            ->toArray();
    }

    /**
     * Get league table - sorted by points, goal difference, goals scored.
     */
    public function getLeagueTable(): array
    {
        $teams = $this->teams();
        $table = $teams->toArray();

        // Add goal difference to each team
        foreach ($table as &$teamData) {
            $teamData['goal_difference'] = $teamData['goals_for'] - $teamData['goals_against'];
        }

        // Sort by points (desc), goal difference (desc), goals scored (desc)
        usort($table, function ($a, $b) {
            // Compare points
            if ($a['points'] !== $b['points']) {
                return $b['points'] <=> $a['points'];
            }
            
            // If points are equal, compare goal difference
            if ($a['goal_difference'] !== $b['goal_difference']) {
                return $b['goal_difference'] <=> $a['goal_difference'];
            }
            
            // If goal difference is equal, compare goals scored
            return $b['goals_for'] <=> $a['goals_for'];
        });

        return $table;
    }

    /**
     * Update match result manually.
     */
    public function updateMatchResult(int $matchId, int $homeGoals, int $awayGoals): bool
    {
        $match = $this->matches()->find($matchId);
        
        if (!$match) {
            return false;
        }

        // Get the teams
        $homeTeam = $match->homeTeam;
        $awayTeam = $match->awayTeam;

        // Reset all team stats
        foreach ($this->teams() as $team) {
            $team->resetStats();
        }

        // Set the result for this match
        $match->setResult($homeGoals, $awayGoals);

        // Replay all matches that have been played to recalculate stats
        $playedMatches = $this->matches()->where('played', true)->get();
        
        foreach ($playedMatches as $playedMatch) {
            $playedMatch->homeTeam->updateStats(
                $playedMatch->home_goals,
                $playedMatch->away_goals
            );
            
            $playedMatch->awayTeam->updateStats(
                $playedMatch->away_goals,
                $playedMatch->home_goals
            );
        }

        // Update predictions if we're in the prediction phase
        if ($this->current_week >= $this->total_weeks - 3) {
            $this->updatePredictions();
        }

        return true;
    }

    /**
     * Get championship predictions.
     */
    public function getPredictions(): array
    {
        $predictionService = new PredictionService();
        return $predictionService->getPredictions($this);
    }
}