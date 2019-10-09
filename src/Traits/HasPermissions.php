<?php

namespace Shetabit\Admission\Traits;

use Illuminate\Database\Eloquent\Relations\{MorphToMany, Relation};
use Illuminate\Support\Collection;
use Shetabit\Admission\Contracts\PermissionInterface as PermissionContract;
use Shetabit\Admission\Contracts\PermissionInterface;
use Shetabit\Admission\PermissionRegistrar;

trait HasPermissions
{
    /**
     * Use to keep an instance of eloquent permission model.
     *
     * @var PermissionContract|null
     */
    private $permissionModel;

    /**
     * Boot the HasPermissions trait.
     *
     * @return void
     */
    public static function bootHasPermissions()
    {
        static::deleting(
            function ($model) {
                $isSoftDelete = method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting();

                if ($isSoftDelete) {
                    return;
                }

                $model->permissions()->detach();
            }
        );
    }

    /**
     * Get an instance of permission's eloquent model
     *
     * @return PermissionContract
     */
    public function getPermissionModel() : PermissionContract
    {
        if (! isset($this->permissionModel)) {
            $this->permissionModel = app(PermissionRegistrar::class)->getPermissionModel();
        }

        return $this->permissionModel;
    }

    /**
     * Get related permissions.
     *
     * @return MorphToMany
     */
    public function permissions() : MorphToMany
    {
        return $this
            ->morphToMany(config('admission.models.permission'), 'permissionable')
            ->withPivot('forbid',  'own', 'from', 'until')
            ->withTimestamps();
    }

    /**
     * Get all of the model's allowed permissions
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDirectPermissions()
    {
        return $this
            ->permissions()
            ->where(function ($query) {
                $query->whereNull('forbid')->orWhere('forbid', '=', false);
            })
            ->get();
    }

    /**
     * Retrieve all the permissions the model has via roles.
     *
     * @return mixed
     */
    public function getPermissionsViaRoles()
    {
        $relationships = ['roles', 'roles.permissions'];

        if (method_exists($this, 'loadMissing')) {
            $this->loadMissing($relationships);
        } else {
            $this->load($relationships);
        }

        return $this->roles->flatMap(function ($role) {
            return $role->permissions;
        })->sort()->values();
    }

    /**
     * Return all the permissions the model has, both directly and via roles.
     *
     * @return mixed
     */
    public function getPermissions()
    {
        $permissions = $this->permissions;

        if ($this->roles) {
            $permissions = $permissions->merge($this->getPermissionsViaRoles());
        }

        return $permissions->sort()->values();
    }

    /**
     * Get all of the model's forbidden permissions
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDirectForbiddenPermissions()
    {
        return $this
            ->permissions()
            ->where(function ($query) {
                $query->where('forbid', '=', true);
            })
            ->get();
    }

    /**
     * Determine if the model has any of the given permissions.
     *
     * @param array ...$permissions
     *
     * @return bool
     * @throws \Exception
     */
    public function hasAnyPermission(...$permissions): bool
    {
        if (is_array($permissions[0])) {
            $permissions = $permissions[0];
        }

        foreach ($permissions as $permission) {
            if ($this->hasPermissionTo($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the model has all of the given permissions.
     *
     * @param array ...$permissions
     *
     * @return bool
     * @throws \Exception
     */
    public function hasAllPermissions(...$permissions): bool
    {
        if (is_array($permissions[0])) {
            $permissions = $permissions[0];
        }

        foreach ($permissions as $permission) {
            if (! $this->hasPermissionTo($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if the model as given direct permissions.
     *
     * @param string|int|PermissionInterface $permission
     *
     * @return bool
     */
    public function hasDirectPermission($permission): bool
    {
        $permissionModel= $this->getPermissionModel();

        // check by permission's name
        if (is_string($permission)) {
            $permission = $permissionModel->where('name', '=', $permission)->first();
            if (! $permission) {
                return false;
            }
        }

        // check by permission's id
        if (is_int($permission)) {
            $permission = $permissionModel->where('id', '=', $permission)->first();
            if (! $permission) {
                return false;
            }
        }

        if (! $permission instanceof PermissionContract) {
            return false;
        }

        // check by permission's model
        return $this->permissions->contains('id', $permission->id);
    }

    /**
     * Determine if a model has some permissions via its roles.
     *
     * @param PermissionInterface $permission
     *
     * @return mixed
     */
    public function hasPermissionViaRole(PermissionContract $permission)
    {
        return $this->hasRole($permission->roles);
    }

    /**
     * Determine if the model has permission to run an action over given entity.
     *
     * @param $action
     * @param null $entity
     *
     * @return bool
     */
    public function hasPermissionTo($action, $entity = null) : bool
    {
        $entity = array_pop($entity);

        $permission = $this
            ->getPermissionModel()
            ->where('action', '=', $action)
            ->when(
                !empty($entity),
                function ($query) use ($entity) {
                    $query->where(function ($query) use ($entity) {
                        $entityClassName = is_object($entity) ? get_class($entity) : $entity;
                        $entityName = array_search($entityClassName, Relation::$morphMap);

                        $query
                            ->where('name', '=', $entityClassName)
                            ->orWhere('name', '=', $entityName);
                    });

                    if (is_object($entity) && !empty($entity->id)) {
                        $query->where(
                            function($query) use ($entity) {
                                $query
                                    ->whereNull('entity_id')
                                    ->orWhere('entity_id', $entity->id);
                            }
                        );
                    }
                }
            )->first();

        if (empty($permission)) {
            return false;
        }

        return $this->hasDirectPermission($permission) || $this->hasPermissionViaRole($permission);
    }

    /**
     * Grant the given permission(s) to a role.
     *
     * @param string|array|PermissionContract|Collection $permissions
     *
     * @return $this
     */
    public function givePermissionTo(...$permissions)
    {
        $permissions = collect($permissions)
            ->flatten()
            ->map(
                function ($permission) {
                    return empty($permission) ? false : $this->getStoredPermission($permission);
                }
            )
            ->filter(
                function ($permission) {
                    return $permission instanceof PermissionContract;
                }
            )
            ->map
            ->id
            ->all();

        $model = $this->getModel();

        if ($model->exists) {
            $this->permissions()->sync($permissions, false);
            $model->load('permissions');
        } else {
            $class = \get_class($model);
            $class::saved(
                function ($object) use ($permissions, $model) {
                    static $modelLastFiredOn;
                    if ($modelLastFiredOn !== null && $modelLastFiredOn === $model) {
                        return;
                    }
                    $object->permissions()->sync($permissions, false);
                    $object->load('permissions');
                    $modelLastFiredOn = $object;
                }
            );
        }

        return $this;
    }

    /**
     * Remove all current permissions and set the given ones.
     *
     * @param string|array|PermissionInterface|Collection $permissions
     *
     * @return $this
     */
    public function syncPermissions(...$permissions)
    {
        $this->permissions()->detach();

        return $this->givePermissionTo($permissions);
    }

    /**
     * Revoke the given permissions
     *
     * @param $permission
     * @return $this
     */
    public function revokePermissionTo($permission)
    {
        $this->permissions()->detach($this->getStoredPermission($permission));

        $this->load('permissions');

        return $this;
    }

    /**
     * Retrieve entities that model has access to them
     *
     * @param $model
     * @param string $entityRelationName
     * @param string $relationName
     * @return mixed
     */
    public function entitiesHaveAccessTo($model, $relationName = 'users', $entityRelationName = 'permissions')
    {
        return $model::whereHas($entityRelationName , function ($query) use ($relationName) {
            $query->whereHas($relationName, function ($query)  use ($relationName) {
                $query->where($relationName.'.id', $this->getKey());
            });
        });
    }

    /**
     * Get available permissions' name.
     *
     * @return Collection
     */
    public function getPermissionNames(): Collection
    {
        return $this->permissions->pluck('name');
    }

    /**
     * @param string|array|PermissionInterface|Collection $permissions
     *
     * @return PermissionInterface|PermissionInterface[]|Collection
     */
    protected function getStoredPermission($permissions)
    {
        $permissionModel= $this->getPermissionModel();


        if (is_numeric($permissions)) {
            return $permissionModel->findById($permissions);
        }

        if (is_string($permissions)) {
            return $permissionModel->findByName($permissions);
        }

        if (is_array($permissions)) {
            return $permissionModel->whereIn('name', $permissions)->get();
        }

        return $permissions;
    }
}
