<?php

return [
    'login' => env('THROTTLE_LOGIN', '5,1'),
    'register' => env('THROTTLE_REGISTER', '3,1'),
];
