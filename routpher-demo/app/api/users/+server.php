<?php

use App\Core\Response;
use App\Core\DB;

return [
    'get' => function($req) {
        $users = DB::table('users')
            ->limit(100)
            ->get();

        // Remove password field
        $users = array_map(function($user) {
            unset($user['password']);
            return $user;
        }, $users);

        Response::json($users);
    }
];
