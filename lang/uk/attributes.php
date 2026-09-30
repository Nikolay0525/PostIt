<?php

/*
|--------------------------------------------------------------------------
| Entity Field Names
|--------------------------------------------------------------------------
|
| Field names that read differently depending on the entity, used in
| validation messages by requests with HasEntityAttributes. Names that are
| the same for every entity belong in validation.attributes.
|
*/

return [

    'user' => [
        'name' => 'ім’я',
    ],

    'group' => [
        'name' => 'назва групи',
        'slug' => 'адреса групи',
    ],

];
