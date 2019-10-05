<?php

namespace Shetabit\Admission\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface PermissionInterface extends FindInterface
{
    /**
     * A permission can be applied to roles.
     *
     * @return MorphToMany
     */
    public function roles() : MorphToMany;

}
