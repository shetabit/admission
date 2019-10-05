<?php

namespace Shetabit\Admission\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface PermissionInterface
{
    /**
     * A permission can be applied to roles.
     *
     * @return MorphToMany
     */
    public function roles() : MorphToMany;

    /**
     * Find a permission by its name.
     *
     * @param string $name
     *
     * @return PermissionInterface
     */
    public static function findByName(string $name) : self;

    /**
     * Find a permission by its id.
     *
     * @param int $id
     *
     * @return PermissionInterface
     */
    public static function findById(int $id) : self;

    /**
     * Find or Create a permission by its name and guard name.
     *
     * @param string $name
     *
     * @return PermissionInterface
     */
    public static function findOrCreate(string $name) : self;
}
