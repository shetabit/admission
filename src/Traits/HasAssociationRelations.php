<?php

namespace Shetabit\Admission\Traits;

use App\Models\Permission;
use App\Models\Role;

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
            if ($model instanceof Role) {
                $type = 'rolable';
            } else if ($model instanceof Permission) {
                $type = 'permissionable';
            }

            return $model
                ->morphedByMany($relationModel, $type)
                ->withPivot('from', 'until')
                ->withTimestamps();
        });
    }
}
