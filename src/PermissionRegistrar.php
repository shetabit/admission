<?php

namespace Shetabit\Admission;

use Illuminate\Contracts\Auth\Access\{Authorizable, Gate};
use Shetabit\Admission\Contracts\PermissionInterface;
use Shetabit\Admission\Contracts\RoleInterface;

class PermissionRegistrar
{
    protected $gate;
    protected $permissionModel;
    protected $roleModel;

    /**
     * PermissionRegistrar constructor.
     *
     * @param Gate $gate
     */
    public function __construct(Gate $gate)
    {
        $this->gate = $gate;
        $this->permissionModel = config('admission.models.permission');
        $this->roleModel = config('admission.models.role');
    }

    /**
     * Register permissions into gate.
     */
    public function registerPermissions()
    {
        $this->gate->before(
            function (Authorizable $user, string $ability, $params) {
                if (method_exists($user, 'hasPermissionTo')) {
                    return $user->hasPermissionTo($ability, $params) ?: null;
                }
            }
        );
    }

    /**
     * Set permission's eloquent model
     *
     * @param $permissionModel
     * @return $this
     */
    public function setPermissionModel($permissionModel)
    {
        $this->permissionModel = $permissionModel;

        return $this;
    }

    /**
     * Get an instance of the permission class.
     *
     * @return PermissionInterface
     */
    public function getPermissionModel() : PermissionInterface
    {
        return app($this->permissionModel);
    }

    /**
     * Set role's eloquent model
     *
     * @param $roleModel
     * @return $this
     */
    public function setRoleModel($roleModel)
    {
        $this->permissionModel = $roleModel;

        return $this;
    }

    /**
     * Get an instance of the role class.
     *
     * @return RoleInterface
     */
    public function getRoleModel(): RoleInterface
    {
        return app($this->roleModel);
    }
}
