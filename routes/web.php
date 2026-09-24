<?php

return [
    'GET /' => 'HomeController@index',
    'GET /login' => 'AuthController@showLogin',
    'POST /login' => 'AuthController@login',
    'GET /booking' => 'BookingController@create',
    'POST /booking' => 'BookingController@store',
    'GET /admin' => 'AdminController@index',
];
