<?php

namespace BagistoPackages\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use BagistoPackages\Admin\Contracts\Role as RoleContract;

class Role extends Model implements RoleContract
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'permission_type',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    /**
     * Get the admins.
     */
    public function admins()
    {
        return $this->hasMany(AdminProxy::modelClass());
    }
}
