<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Insider Champions League</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        /* Global Styles */
        body {
            font-family: 'Roboto', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            padding-top: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .league-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .league-header h1 {
            color: #0d47a1;
            margin-bottom: 10px;
        }

        .league-info {
            font-size: 1.2rem;
            color: #555;
        }

        /* Controls */
        .controls {
            margin-bottom: 25px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        /* League Table */
        .table-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .table-title {
            background-color: #0d47a1;
            color: #fff;
            padding: 15px;
            margin: 0;
            font-size: 1.5rem;
        }

        .league-table {
            width: 100%;
            border-collapse: collapse;
        }

        .league-table th {
            background-color: #e3f2fd;
            font-weight: bold;
            text-align: center;
            padding: 12px;
            border-bottom: 2px solid #1565c0;
        }

        .league-table td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #e0e0e0;
        }

        /* Match Results */
        .match-results {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .match-title {
            background-color: #1565c0;
            color: #fff;
            padding: 15px;
            margin: 0;
            font-size: 1.5rem;
        }

        .match-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .match-item {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .match-result {
            font-weight: bold;
            font-size: 1.2rem;
            padding: 5px 15px;
            background-color: #e3f2fd;
            border-radius: 4px;
            cursor: pointer;
        }

        /* Spinner */
        .spinner-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
    </style>
</head>
<body>
    <div id="app">
        <div class="container">
            <div class="league-header">
                <h1>Insider Champions League</h1>
                <div class="league-info">
                    Week @{{ league.currentWeek }} of @{{ league.totalWeeks }}
                    <span v-if="league.isFinished" class="badge bg-success">Completed</span>
                    <span v-else class="badge bg-primary">In Progress</span>
                </div>
            </div>
            
            <div v-if="message" :class="'alert alert-' + (messageType === 'error' ? 'danger' : messageType)">
                @{{ message }}
            </div>
            
            <div class="controls">
                <button @click="initLeague" class="btn btn-warning" :disabled="isLoading">
                    <i class="fas fa-redo"></i> Reset League
                </button>
                <button @click="playNextWeek" class="btn btn-primary" :disabled="isLoading || league.isFinished">
                    <i class="fas fa-play"></i> Play Next Week
                </button>
                <button @click="playAllWeeks" class="btn btn-success" :disabled="isLoading || league.isFinished">
                    <i class="fas fa-fast-forward"></i> Play All
                </button>
            </div>
            
            <!-- League Table -->
            <div class="row">
                <div class="col-md-8">
                    <div class="table-container">
                        <h2 class="table-title">League Table</h2>
                        <table class="league-table">
                            <thead>
                                <tr>
                                    <th>Pos</th>
                                    <th class="text-start">Team</th>
                                    <th>P</th>
                                    <th>W</th>
                                    <th>D</th>
                                    <th>L</th>
                                    <th>GF</th>
                                    <th>GA</th>
                                    <th>GD</th>
                                    <th>Pts</th>
                                    <th v-if="showPredictions">Chance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(team, index) in leagueTable" :key="team.id">
                                    <td>@{{ index + 1 }}</td>
                                    <td class="text-start">@{{ team.name }}</td>
                                    <td>@{{ team.played }}</td>
                                    <td>@{{ team.won }}</td>
                                    <td>@{{ team.drawn }}</td>
                                    <td>@{{ team.lost }}</td>
                                    <td>@{{ team.goals_for }}</td>
                                    <td>@{{ team.goals_against }}</td>
                                    <td>@{{ team.goal_difference }}</td>
                                    <td><strong>@{{ team.points }}</strong></td>
                                    <td v-if="showPredictions" 
                                        :class="getPredictionClass(team.id)" 
                                        class="prediction-column">
                                        @{{ getPrediction(team.id) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="match-results">
                        <h2 class="match-title">Week @{{ league.currentWeek }} Matches</h2>
                        <ul class="match-list">
                            <li v-if="weekMatches.length === 0" class="match-item">
                                No matches played yet.
                            </li>
                            <li v-for="match in weekMatches" :key="match.id" class="match-item">
                                <div class="team-names">
                                    <span class="home-team">@{{ match.home_team }}</span>
                                    <span>vs</span>
                                    <span class="away-team">@{{ match.away_team }}</span>
                                </div>
                                <div class="match-result" @click="editMatch(match)">
                                    @{{ match.result }}
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Match Modal -->
    <div class="modal fade" id="editMatchModal" tabindex="-1" aria-labelledby="editMatchModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editMatchModalLabel">Edit Match Result</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" v-if="editingMatch">
                    <div class="row mb-3">
                        <div class="col-5 text-end">
                            <h5>@{{ editingMatch.home_team }}</h5>
                        </div>
                        <div class="col-2 text-center">
                            <h5>vs</h5>
                        </div>
                        <div class="col-5 text-start">
                            <h5>@{{ editingMatch.away_team }}</h5>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-5">
                            <div class="form-group">
                                <label for="homeGoals">Home Goals</label>
                                <input type="number" class="form-control" id="homeGoals" v-model="homeGoals" min="0" max="10">
                            </div>
                        </div>
                        <div class="col-2 text-center pt-4">
                            <span>-</span>
                        </div>
                        <div class="col-5">
                            <div class="form-group">
                                <label for="awayGoals">Away Goals</label>
                                <input type="number" class="form-control" id="awayGoals" v-model="awayGoals" min="0" max="10">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" @click="saveEditedMatch">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Vue.js -->
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
    
    <!-- Axios for API calls -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Setup CSRF Token for Axios
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Initialize Vue application
        const app = new Vue({
            el: '#app',
            data: {
                league: {
                    name: '{{ $league->name }}',
                    currentWeek: {{ $league->current_week }},
                    totalWeeks: {{ $league->total_weeks }},
                    isFinished: {{ $league->is_finished ? 'true' : 'false' }}
                },
                teams: [],
                leagueTable: [],
                weekMatches: [],
                predictions: {},
                isLoading: false,
                editingMatch: null,
                homeGoals: 0,
                awayGoals: 0,
                message: '',
                messageType: 'info'
            },
            methods: {
                /**
                 * Initialize the league
                 */
                initLeague: function() {
                    this.isLoading = true;
                    this.showMessage('Initializing league...', 'info');
                    
                    axios.post('{{ route("api.init-league") }}')
                        .then(response => {
                            console.log(response);
                            
                            this.updateLeagueData(response.data.data);
                            this.showMessage('League initialized successfully', 'success');
                        })
                        .catch(error => {
                            console.error('Error initializing league:', error);
                            this.showMessage('Failed to initialize league', 'error');
                        })
                        .finally(() => {
                            this.isLoading = false;
                        });
                },
                
                /**
                 * Play next week's matches
                 */
                playNextWeek: function() {
                    if (this.league.isFinished) {
                        this.showMessage('League is already finished', 'info');
                        return;
                    }
                    
                    this.isLoading = true;
                    this.showMessage('Playing next week...', 'info');
                    
                    axios.post('{{ route("api.play-next-week") }}')
                        .then(response => {
                            this.updateLeagueData(response.data.data);
                            this.showMessage(`Week ${this.league.currentWeek} played successfully`, 'success');
                        })
                        .catch(error => {
                            console.error('Error playing next week:', error);
                            this.showMessage('Failed to play next week', 'error');
                        })
                        .finally(() => {
                            this.isLoading = false;
                        });
                },
                
                /**
                 * Play all remaining weeks
                 */
                playAllWeeks: function() {
                    if (this.league.isFinished) {
                        this.showMessage('League is already finished', 'info');
                        return;
                    }
                    
                    this.isLoading = true;
                    this.showMessage('Playing all remaining weeks...', 'info');
                    
                    axios.post('{{ route("api.play-all-weeks") }}')
                        .then(response => {
                            this.updateLeagueData(response.data.data);
                            this.showMessage('All matches played successfully', 'success');
                        })
                        .catch(error => {
                            console.error('Error playing all weeks:', error);
                            this.showMessage('Failed to play all weeks', 'error');
                        })
                        .finally(() => {
                            this.isLoading = false;
                        });
                },
                
                /**
                 * Edit match result
                 */
                editMatch: function(match) {
                    this.editingMatch = match;
                    this.homeGoals = match.home_goals;
                    this.awayGoals = match.away_goals;
                    
                    // Show edit modal
                    const editModal = new bootstrap.Modal(document.getElementById('editMatchModal'));
                    editModal.show();
                },
                
                /**
                 * Save edited match result
                 */
                saveEditedMatch: function() {
                    this.isLoading = true;
                    
                    const matchData = {
                        match_id: this.editingMatch.id,
                        home_goals: parseInt(this.homeGoals),
                        away_goals: parseInt(this.awayGoals)
                    };
                    
                    axios.post('{{ route("api.update-match") }}', matchData)
                        .then(response => {
                            this.updateLeagueData(response.data.data);
                            this.showMessage('Match result updated successfully', 'success');
                            
                            // Close the modal
                            const editModal = bootstrap.Modal.getInstance(document.getElementById('editMatchModal'));
                            editModal.hide();
                        })
                        .catch(error => {
                            console.error('Error updating match:', error);
                            this.showMessage('Failed to update match result', 'error');
                        })
                        .finally(() => {
                            this.isLoading = false;
                            this.editingMatch = null;
                        });
                },
                
                /**
                 * Update league data from API response
                 */
                updateLeagueData: function(data) {
                    this.league = data.league;
                    this.teams = data.teams;
                    this.leagueTable = data.leagueTable;
                    this.weekMatches = data.weekMatches;
                    this.predictions = data.predictions || {};
                },
                
                /**
                 * Show message to the user
                 */
                showMessage: function(text, type = 'info') {
                    this.message = text;
                    this.messageType = type;
                    
                    // Clear message after 3 seconds
                    setTimeout(() => {
                        this.message = '';
                    }, 3000);
                },
                
                /**
                 * Get prediction for a team
                 */
                getPrediction: function(teamId) {
                    return this.predictions[teamId] ? this.predictions[teamId] + '%' : 'N/A';
                },
                
                /**
                 * Get CSS class for prediction value
                 */
                getPredictionClass: function(teamId) {
                    const prediction = this.predictions[teamId] || 0;
                    
                    if (prediction >= 70) {
                        return 'text-success';
                    } else if (prediction >= 30) {
                        return 'text-warning';
                    } else {
                        return 'text-danger';
                    }
                }
            },
            mounted: function() {
                // Load initial league data if needed
                    this.initLeague();
            },
            computed: {
                /**
                 * Check if predictions should be shown
                 */
                showPredictions: function() {
                    return this.league.currentWeek >= this.league.totalWeeks - 3;
                }
            }
        });
    </script>
    
    <footer class="mt-5 py-3 text-center text-muted">
        <div class="container">
            <p>Insider Champions League &copy; 2025. All rights reserved.</p>
            <p class="small">A football league simulation project.</p>
        </div>
    </footer>
</body>
</html>