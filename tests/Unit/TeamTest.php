<?php

namespace Tests\Unit;

use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test team creation.
     */
    public function test_team_creation(): void
    {
        $team = Team::create([
            'name' => 'Manchester United',
            'strength' => 85,
            'home_advantage' => 12
        ]);

        $this->assertEquals('Manchester United', $team->name);
        $this->assertEquals(85, $team->strength);
        $this->assertEquals(12, $team->home_advantage);
        $this->assertEquals(0, $team->points);
        $this->assertEquals(0, $team->played);
    }

    /**
     * Test updating team stats after a match.
     */
    public function test_update_stats(): void
    {
        $team = Team::create([
            'name' => 'Manchester United',
            'strength' => 85,
            'home_advantage' => 12
        ]);

        // Win scenario
        $team->updateStats(3, 1);
        $this->assertEquals(1, $team->played);
        $this->assertEquals(1, $team->won);
        $this->assertEquals(0, $team->drawn);
        $this->assertEquals(0, $team->lost);
        $this->assertEquals(3, $team->points);
        $this->assertEquals(3, $team->goals_for);
        $this->assertEquals(1, $team->goals_against);
        $this->assertEquals(2, $team->getGoalDifference());

        // Draw scenario
        $team->updateStats(2, 2);
        $this->assertEquals(2, $team->played);
        $this->assertEquals(1, $team->won);
        $this->assertEquals(1, $team->drawn);
        $this->assertEquals(0, $team->lost);
        $this->assertEquals(4, $team->points);
        $this->assertEquals(5, $team->goals_for);
        $this->assertEquals(3, $team->goals_against);
        $this->assertEquals(2, $team->getGoalDifference());

        // Loss scenario
        $team->updateStats(1, 3);
        $this->assertEquals(3, $team->played);
        $this->assertEquals(1, $team->won);
        $this->assertEquals(1, $team->drawn);
        $this->assertEquals(1, $team->lost);
        $this->assertEquals(4, $team->points);
        $this->assertEquals(6, $team->goals_for);
        $this->assertEquals(6, $team->goals_against);
        $this->assertEquals(0, $team->getGoalDifference());
    }

    /**
     * Test resetting team stats.
     */
    public function test_reset_stats(): void
    {
        $team = Team::create([
            'name' => 'Manchester United',
            'strength' => 85,
            'home_advantage' => 12
        ]);

        $team->updateStats(3, 1);
        $this->assertEquals(3, $team->points);

        $team->resetStats();
        $this->assertEquals(0, $team->points);
        $this->assertEquals(0, $team->played);
        $this->assertEquals(0, $team->won);
        $this->assertEquals(0, $team->drawn);
        $this->assertEquals(0, $team->lost);
        $this->assertEquals(0, $team->goals_for);
        $this->assertEquals(0, $team->goals_against);
    }
}