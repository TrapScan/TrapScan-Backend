<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    const ADMIN_ROLE = 'admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function projects() {
        return $this->belongsToMany(Project::class);
    }

    public function traps() {
        return $this->hasMany(Trap::class);
    }

    public function inspections() {
        return $this->hasMany(Inspection::class, 'recorded_by', 'id');
    }

    public function profile() {
        return $this->hasOne(Profile::class);
    }

    public function providers() {
        return $this->hasMany(Provider::class, 'user_id', 'id');
    }

//    public function inspectionsPerProject(Project $project = null) {
//        return $this->projects()->with(['traps.inspections' => function($query) {
//            $query->where('recorded_by', $this->id);
//        }])->get();
//    }

    /*
     * Returns the simple project and inspection count for each of a users projects
     */
    public function inspectionCountPerProject() {
        $project_ids = $this->projects->pluck('id');
        $user_id = $this->id;
        $data = Project::select('id', 'name')->whereIn('id', $project_ids)->withCount(['inspections' => function ($query) use($user_id) {
           $query->where('recorded_by', $user_id);
        }])->get();
        return $data;
    }

//User::with(['projects.traps.inspections' => function($query){$query->where('recorded_by', 1);}])->get()
    public function coordinatorOf() {
        return $this->projects()->wherePivot('coordinator', '=', true)->get();
    }

    public function isCoordinatorOf(Project $project) {
        return $this->projects()->wherePivot('coordinator', '=', true)
            ->where('project_id', $project->id)
            ->exists();
    }
}
