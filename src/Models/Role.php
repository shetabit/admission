<?php

namespace Shetabit\Admission\Models;

use Shetabit\Admission\Contracts\RoleInterface as RoleContract;
use Shetabit\Admission\Traits\{HasPermissions, HasAssociationRelations};
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasPermissions;
    use HasAssociationRelations;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'roles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Find a role by its name.
     *
     * @param string $name
     *
     * @return RoleContract
     */
    public static function findByName(string $name) : RoleContract
    {
        return static::where('name', $name)->first();
    }

    /**
     * Find a role by its id.
     *
     * @param string $name
     *
     * @return RoleContract
     */
    public static function findById(int $id) : RoleContract
    {
        return static::where('id', $id)->first();
    }

    /**
     * Find or create role by its name.
     *
     * @param string $name
     *
     * @return RoleContract
     */
    public static function findOrCreate(string $name) : RoleContract
    {
        $role = static::where('name', '=', $name)->first();

        if (empty($role)) {
            return static::query()->create(['name' => $name]);
        }

        return $role;
    }

    /**
     * Determine if the user may perform the given permission.
     *
     * @param $permission
     *
     * @return bool
     */
    public function hasPermissionTo($permission) : bool
    {
        $permissionModel= $this->getPermissionModel();

        if (is_string($permission)) {
            $permission = $permissionModel::findByName($permission);
        } else if (is_int($permission)) {
            $permission = $permissionModel::findById($permission);
        }

        return empty($permission) ? false : $this->permissions->contains('id', $permission->id);
    }
}
