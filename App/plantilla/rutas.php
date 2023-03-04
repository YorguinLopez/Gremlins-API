<?php 
    require __DIR__ . '/usuarios.php';
    require __DIR__ . '/servicios.php';
    $app->group('/plantilla',function($app){
        $app->group('/usuarios',function($app){
            $app->post('/modulos', \App\plantilla\usuarios::class.':modulos')->setName('modulos');
            $app->post('/eliminar', \App\plantilla\usuarios::class.':eliminar')->setName('eliminar');
            $app->post('/guardar', \App\plantilla\usuarios::class.':guardar')->setName('guardar');
            $app->post('/listar', \App\plantilla\usuarios::class.':listar')->setName('listar');
            $app->post('/permisos', \App\plantilla\usuarios::class.':permisos')->setName('permisos');
            $app->post('/login', \App\plantilla\usuarios::class.':login')->setName('login');
            $app->post('/perfiles', \App\plantilla\usuarios::class.':perfiles')->setName('perfiles');
            $app->group('/perfil',function($app){
                $app->post('/actualizar', \App\plantilla\usuarios::class.':actualizarPerfil')->setName('actualizarPerfil');
            });
        });   
        $app->group('/servicios',function($app){
            $app->post('/listar', \App\plantilla\servicios::class.':listar')->setName('listar');
        });
    });
?>