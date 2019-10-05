<?php

namespace Shetabit\Admission\Traits;

use Shetabit\Admission\Contracts\{PermissionInterface, RoleInterface};

trait HasAssociationRelations
{
    use HasDynamicRelations;

    /**
     * Add dynamic association relations.
     *
     * @return mixed
     */
    public static function associatedBy($relationName, $relationModel)
    {
        static::addDynamicRelation($relationName, function ($model) use ($relationModel) {
            if ($model instanceof RoleInterface) {
                $type = 'rolable';
            } else if ($model instanceof PermissionInterface) {
                $type = 'permissionable';
            }

            return $model
                ->morphedByMany($relationModel, $type)
                ->withPivot('from', 'until')
                ->withTimestamps();
        });
    }
}
