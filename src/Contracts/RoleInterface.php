<?php

namespace Shetabit\Admission\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface RoleInterface extends FindInterface
{
    /**
     * A role may be given various permissions.
     *
     * @return MorphToMany
     */
    public function permissions() : MorphToMany;

    /**
     * Determine if the user may perform the given permission.
     *
     * @param $permission
     * @return bool
     */
    public function hasPermissionTo($permission) : bool;
}
