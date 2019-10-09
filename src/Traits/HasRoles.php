<?php

namespace Shetabit\Admission\Traits;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use Shetabit\Admission\Contracts\RoleInterface as RoleContract;
use Shetabit\Admission\PermissionRegistrar;

trait HasRoles
{
    /**
     * Use to keep an instance of eloquent role model.
     *
     * @var RoleContract|null
     */
    private $roleModel;

    /**
     * Boot the HasRoles trait.
     *
     * @return void
     */
    public static function bootHasRoles()
    {
        static::deleting(
            function ($model) {
                $isSoftDelete = method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting();

                if ($isSoftDelete) {
                    return;
                }

                $model->roles()->detach();
            }
        );
    }

    /**
     * Get an instance of Role's eloquent model
     *
     * @return RoleContract
     */
    public function getRoleClass() : RoleContract
    {
        if (! isset($this->roleClass)) {
            $this->roleClass = app(PermissionRegistrar::class)->getRoleClass();
        }

        return $this->roleClass;
    }

    /**
     * Get related roles.
     *
     * @return mixed
     */
    public function roles() : MorphToMany
    {
        return $this
            ->morphToMany(config('admission.models.role'), 'rolable')
            ->withPivot('from', 'until')
            ->withTimestamps();
    }

    /**
     * Get roles
     *
     * @return mixed
     */
    public function getRoles()
    {
        return $this->roles()->get();
    }

    /**
     * Assign the given role to the model.
     *
     * @param array|string|\Spatie\Permission\Contracts\Role ...$roles
     *
     * @return $this
     */
    public function assignRole(...$roles)
    {
        $roles = collect($roles)
            ->flatten()
            ->map(
                function ($role) {
                    return empty($role) ? false : $this->getStoredRole($role);
                }
            )
            ->filter(
                function ($role) {
                    return $role instanceof RoleContract;
                }
            )
            ->map->id
            ->all();

        $model = $this->getModel();

        if ($model->exists) {
            $this->roles()->sync($roles, false);
            $model->load('roles');
        } else {
            $class = \get_class($model);
            $class::saved(
                function ($object) use ($roles, $model) {
                    static $modelLastFiredOn;
                    if ($modelLastFiredOn !== null && $modelLastFiredOn === $model) {
                        return;
                    }
                    $object->roles()->sync($roles, false);
                    $object->load('roles');
                    $modelLastFiredOn = $object;
                });
        }

        return $this;
    }

    /**
     * Revoke the given role from the model.
     *
     * @param string|RoleContract $role
     *
     * @return $this
     */
    public function removeRole($role)
    {
        $this->roles()->detach($this->getStoredRole($role));
        $this->load('roles');

        return $this;
    }

    /**
     * Remove all current roles and set the given ones.
     *
     * @param array|RoleContract|string ...$roles
     *
     * @return $this
     */
    public function syncRoles(...$roles)
    {
        $this->roles()->detach();

        return $this->assignRole($roles);
    }

    /**
     * Determine if the model has (one of) the given role(s).
     *
     * @param string|int|array|RoleContract|Collection $roles
     *
     * @return bool
     */
    public function hasRole($roles) : bool
    {
        // convert pipe separated rules to array
        if (is_string($roles) && false !== strpos($roles, '|')) {
            $roles = $this->convertPipeToArray($roles);
        }

        // check by role's name
        if (is_string($roles)) {
            return $this->roles->contains('name', $roles);
        }

        // check by role's id
        if (is_int($roles)) {
            return $this->roles->contains('id', $roles);
        }

        // check by role's
        if ($roles instanceof RoleContract) {
            return $this->roles->contains('id', $roles->id);
        }

        // check by array of rules
        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($this->hasRole($role)) {
                    return true;
                }
            }

            return false;
        }

        // check by collection of rules
        return $roles->intersect($this->roles)->isNotEmpty();
    }

    /**
     * Determine if the model has any of the given role(s).
     *
     * @param string|array|RoleContract|Collection $roles
     *
     * @return bool
     */
    public function hasAnyRole($roles) : bool
    {
        return $this->hasRole($roles);
    }

    /**
     * Determine if the model has all of the given role(s).
     *
     * @param string|Role|Collection $roles
     *
     * @return bool
     */
    public function hasAllRoles($roles) : bool
    {
        // convert pipe separated rules to array
        if (is_string($roles) && false !== strpos($roles, '|')) {
            $roles = $this->convertPipeToArray($roles);
        }

        // check by role's name
        if (is_string($roles)) {
            return $this->roles->contains('name', $roles);
        }

        // check by instance of role's model
        if ($roles instanceof RoleContract) {
            return $this->roles->contains('id', $roles->id);
        }

        // convert array of role's model to collection of their names
        $roles = collect($roles)->map(
            function ($role) {
                return $role instanceof RoleContract ? $role->name : $role;
            }
        );

        // check by collection of role's name
        return $roles->intersect($this->getRoleNames()) == $roles;
    }

    /**
     * @return Collection
     */
    public function getRoleNames() : Collection
    {
        return $this->roles->pluck('name');
    }

    /**
     * Retrieve role
     *
     * @param $role
     *
     * @return RoleContract
     */
    protected function getStoredRole($role) : RoleContract
    {
        $roleClass = $this->getRoleClass();

        if (is_numeric($role)) {
            return $roleClass->findById($role);
        }

        if (is_string($role)) {
            return $roleClass->findByName($role);
        }

        return $role;
    }

    /**
     * convert string with pipes into arrays
     *
     * @param string $pipeString
     * @return array|string
     */
    protected function convertPipeToArray(string $pipeString)
    {
        $pipeString = trim($pipeString);

        if (strlen($pipeString) <= 2) {
            return $pipeString;
        }

        $quoteCharacter = substr($pipeString, 0, 1);
        $endCharacter = substr($quoteCharacter, -1, 1);

        if ($quoteCharacter !== $endCharacter) {
            return explode('|', $pipeString);
        }

        if (! in_array($quoteCharacter, ["'", '"'])) {
            return explode('|', $pipeString);
        }

        return explode('|', trim($pipeString, $quoteCharacter));
    }
}
