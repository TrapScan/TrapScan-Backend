<?php

namespace App\Jobs;

use App\Mail\TrapCatch;
use App\Models\Inspection;
use App\Models\Project;
use App\Models\Trap;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class SendCatchNotificationToCoordinators implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $inspection;
    protected $project;
    protected $user;
    protected $trap;
    protected $usersToNotify;

    /**
     * Create a new job instance.
     *
     * @param Collection $usersToNotify
     * @param Inspection $inspection
     * @param Project $project
     * @param User $inspectionUser
     * @param Trap $trap
     */
    public function __construct(Array $usersToNotify, Inspection $inspection, Project $project, User $inspectionUser, Trap $trap)
    {
        $this->inspection = $inspection->withoutRelations();
        $this->trap = $trap->withoutRelations();
        $this->project = $project->withoutRelations();
        $this->user = $inspectionUser->withoutRelations();
        $this->usersToNotify = $usersToNotify;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
    }
}
