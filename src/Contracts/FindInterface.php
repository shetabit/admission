<?php

namespace Shetabit\Admission\Contracts;

interface FindInterface
{
    /**
     * Find a permission by its name.
     *
     * @param string $name
     *
     * @return FindInterface
     */
    public static function findByName(string $name) : self;

    /**
     * Find a permission by its id.
     *
     * @param int $id
     *
     * @return FindInterface
     */
    public static function findById(int $id) : self;

    /**
     * Find or Create a permission by its name and guard name.
     *
     * @param string $name
     *
     * @return FindInterface
     */
    public static function findOrCreate(string $name) : self;
}
