<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameMatch extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'home_team_id',
        'away_team_id',
        'home_goals',
        'away_goals',
        'week',
        'played',
        'league_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'played' => 'boolean',
    ];

    /**
     * Get the home team for this match.
     */
    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    /**
     * Get the away team for this match.
     */
    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    /**
     * Get the league this match belongs to.
     */
    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    /**
     * Play this match and update team statistics.
     */
    public function playMatch(): array
    {
        if ($this->played) {
            return [$this->home_goals, $this->away_goals];
        }

        // Calculate team strengths with home advantage
        $homeStrength = $this->homeTeam->strength + $this->homeTeam->home_advantage;
        $awayStrength = $this->awayTeam->strength;

        // Calculate goal scoring probabilities
        $totalStrength = $homeStrength + $awayStrength;
        $homeScoreProbability = $homeStrength / $totalStrength * 1.5; // Home teams score more
        $awayScoreProbability = $awayStrength / $totalStrength;

        // Generate random goals (0-6 range)
        $homeGoals = $this->generateGoals($homeScoreProbability);
        $awayGoals = $this->generateGoals($awayScoreProbability);

        // Update match result
        $this->update([
            'home_goals' => $homeGoals,
            'away_goals' => $awayGoals,
            'played' => true,
        ]);

        // Update team statistics
        $this->homeTeam->updateStats($homeGoals, $awayGoals);
        $this->awayTeam->updateStats($awayGoals, $homeGoals);

        return [$homeGoals, $awayGoals];
    }

    /**
     * Generate goals based on scoring probability.
     */
    private function generateGoals(float $scoreProbability): int
    {
        $scoringPower = $scoreProbability * 10; // Scale up for better distribution
        
        // Poisson-like distribution for realistic scores
        $random = mt_rand(0, 100) / 100;
        
        if ($random < 0.05) {
            return 0; // 5% chance for 0 goals
        } elseif ($random < 0.20) {
            return 1; // 15% chance for 1 goal
        } elseif ($random < 0.45) {
            return mt_rand(1, 2); // 25% chance for 1-2 goals
        } elseif ($random < 0.80) {
            return mt_rand(1, 3); // 35% chance for 1-3 goals
        } elseif ($random < 0.95) {
            return mt_rand(2, 4); // 15% chance for 2-4 goals
        } else {
            return mt_rand(3, 6); // 5% chance for 3-6 goals (rare high-scoring games)
        }
    }

    /**
     * Set match result manually.
     */
    public function setResult(int $homeGoals, int $awayGoals): void
    {
        $this->update([
            'home_goals' => $homeGoals,
            'away_goals' => $awayGoals,
            'played' => true,
        ]);
    }

    /**
     * Get the match result as a string.
     */
    public function getResult(): string
    {
        if (!$this->played) {
            return "Not played";
        }
        
        return "{$this->home_goals} - {$this->away_goals}";
    }

    /**
     * Get the match data as an array.
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        $array['home_team'] = $this->homeTeam->name;
        $array['away_team'] = $this->awayTeam->name;
        $array['result'] = $this->getResult();
        
        return $array;
    }
}