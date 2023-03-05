<?php
    require __DIR__ . '/grem.php';
    $app->group('/grem',function($app) {
        $app->get('/login/{email}', \App\gremlins\grem::class.':login')->setName('login');
    });
?>