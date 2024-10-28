<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TeamMemberAssignmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $childrenIds;
    protected $teamId;

    public function __construct(array $childrenIds, int $teamId)
    {
        $this->childrenIds = $childrenIds;
        $this->teamId = $teamId;
        $this->queue = 'team_member_assignment';
    }

    public function handle(): void
    {
        // Assign children to the team
        User::whereIn('id', $this->childrenIds)->chunk(500, function ($users) {
            info($this->teamId);
            $users->each->assignedTeam($this->teamId);
        });
    }
}
