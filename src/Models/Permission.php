<?php

namespace Shetabit\Admission\Models;

use Shetabit\Admission\Contracts\PermissionInterface as PermissionContract;
use Shetabit\Admission\Contracts\PermissionInterface;
use Shetabit\Admission\Traits\HasAssociationRelations;
use Illuminate\Database\Eloquent\{Model, Relations\MorphTo, Relations\MorphToMany, Relations\Relation};

class Permission extends Model implements PermissionInterface
{
    use HasAssociationRelations;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'permissions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'action',
        'entity_type', 'entity_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'entity_id' => 'integer'
    ];

    /**
     * Add relationships that this model allows to access them
     *
     * @param $relationModel
     */
    public static function providesAccessTo($relationModel, $relationName = 'permissions')
    {
        $relationModel::addDynamicRelation(
            $relationName,
            function ($model) use ($relationModel) {
                return $model->morphMany(config('admission.models.permission'), 'entity');
            }
        );
    }

    /**
     * Get related roles.
     *
     * @return mixed
     */
    public function roles() : MorphToMany
    {
        return $this
            ->morphedByMany(config('admission.models.role'), 'permissionable')
            ->withPivot('from', 'until')
            ->withTimestamps();
    }

    /**
     * Get the own entities model.
     *
     * @return MorphTo
     */
    public function entity() : MorphTo
    {
        return $this->morphTo('entity');
    }

    /**
     * @param $name
     * @param $modelName
     */
    public static function addEntity($name, $modelName)
    {
        Relation::morphMap([$name => $modelName]);
    }

    /**
     * @param array $entities
     */
    public static function addEntities(array $entities)
    {
        foreach ($entities as $name => $modelName) {
            static::addEntity($name, $modelName);
        }
    }

    /**
     * Find a permission by its name.
     *
     * @param string $name
     *
     * @return PermissionContract
     */
    public static function findByName(string $name) : PermissionContract
    {
        return static::getPermissions(['name' => $name])->first();
    }

    /**
     * Find a permission by its id (and optionally guardName).
     *
     * @param int $id
     *
     * @return PermissionContract
     */
    public static function findById(int $id) : PermissionContract
    {
        return static::where('id', '=', $id)->first();
    }

    /**
     * Find or create permission by its name (and optionally guardName).
     *
     * @param string $name
     * @return PermissionContract
     */
    public static function findOrCreate(string $name) : PermissionContract
    {
        $permission = static::findByName($name);

        if (! $permission) {
            $permission = static::query()->create(['name' => $name]);
        }

        return $permission;
    }
}
