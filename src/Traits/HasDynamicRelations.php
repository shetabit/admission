<?php

namespace Shetabit\Admission\Traits;

trait HasDynamicRelations
{
    /**
     * Store the relations
     *
     * @var array
     */
    private static $dynamicRelations = [];

    /**
     * Add a new relation
     *
     * @param $name
     * @param \Closure $closure
     */
    public static function addDynamicRelation($name, \Closure $closure)
    {
        static::$dynamicRelations[$name] = $closure;
    }

    /**
     * Determine if a relation exists in dynamic relationships list
     *
     * @param $name
     *
     * @return bool
     */
    public static function hasDynamicRelation($name)
    {
        return array_key_exists($name, static::$dynamicRelations);
    }

    /**
     * If the key exists in relations then return call to relation or else
     * return the call to the parent
     *
     * @param $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (static::hasDynamicRelation($name)) {
            // check the cache first
            if ($this->relationLoaded($name)) {
                return $this->relations[$name];
            }

            // load the relationship
            return $this->getRelationshipFromMethod($name);
        }

        return parent::__get($name);
    }

    /**
     * If the method exists in relations then return the relation or else
     * return the call to the parent
     *
     * @param $name
     * @param $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (static::hasDynamicRelation($name)) {
            return call_user_func(static::$dynamicRelations[$name], $this);
        }

        return parent::__call($name, $arguments);
    }
}
