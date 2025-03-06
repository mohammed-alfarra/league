<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'strength',
        'home_advantage',
        'points',
        'played',
        'won',
        'drawn',
        'lost',
        'goals_for',
        'goals_against',
    ];

    /**
     * Get the matches where this team is the home team.
     */
    public function homeMatches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'home_team_id');
    }

    /**
     * Get the matches where this team is the away team.
     */
    public function awayMatches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'away_team_id');
    }

    /**
     * Get all matches for this team.
     */
    public function getAllMatches()
    {
        return GameMatch::where('home_team_id', $this->id)
            ->orWhere('away_team_id', $this->id)
            ->get();
    }

    /**
     * Reset team statistics.
     */
    public function resetStats(): void
    {
        $this->update([
            'points' => 0,
            'played' => 0,
            'won' => 0,
            'drawn' => 0,
            'lost' => 0,
            'goals_for' => 0,
            'goals_against' => 0,
        ]);
    }

    /**
     * Update team statistics after a match.
     */
    public function updateStats(int $goalsScored, int $goalsConceded): void
    {
        $this->played += 1;
        $this->goals_for += $goalsScored;
        $this->goals_against += $goalsConceded;

        if ($goalsScored > $goalsConceded) {
            $this->won += 1;
            $this->points += 3;
        } elseif ($goalsScored === $goalsConceded) {
            $this->drawn += 1;
            $this->points += 1;
        } else {
            $this->lost += 1;
        }

        $this->save();
    }

    /**
     * Get the goal difference for this team.
     */
    public function getGoalDifference(): int
    {
        return $this->goals_for - $this->goals_against;
    }

    /**
     * Get the team's data as an array.
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        $array['goal_difference'] = $this->getGoalDifference();
        
        return $array;
    }
}