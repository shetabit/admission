<?php
return [
    'models' => [
        /*
         * When using the "HasPermissions" trait from this package, we need to know which
         * Eloquent model should be used to retrieve your permissions. Of course, it
         * is often just the "Permission" model but you may use whatever you like.
         *
         * The model you want to use as a Permission model needs to implement the
         * `Shetabit\Admission\Contracts\Permission` contract.
         */
        'permission' => Shetabit\Admission\Models\Permission::class,

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * Eloquent model should be used to retrieve your roles. Of course, it
         * is often just the "Role" model but you may use whatever you like.
         *
         * The model you want to use as a Role model needs to implement the
         * `Shetabit\Admission\Contracts\Role` contract.
         */
        'role' => Shetabit\Admission\Models\Role::class,
    ]
];
