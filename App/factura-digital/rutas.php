<?php 
    require __DIR__ . '/factura-digital.php';
    $app->group('/factura-digital',function($app){
        $app->post('/inscripcion', \App\facturaDigital\facturaDigital::class.':inscripcion')->setName('inscripcion');
        $app->get('/tipoDocumento', \App\facturaDigital\facturaDigital::class.':tipoDocumento')->setName('TipoDocumento');
        $app->post('/verificar', \App\facturaDigital\facturaDigital::class.':verificar')->setName('verificar');
        $app->post('/reenviar', \App\facturaDigital\facturaDigital::class.':reenviar')->setName('reenviar');
        $app->post('/actualizar-suscripcion', \App\facturaDigital\facturaDigital::class.':actualizarSuscripcion')->setName('actualizarSuscripcion');
        $app->post('/verificar-inscripcion-factura', \App\facturaDigital\facturaDigital::class.':verificarInscripcionFactura')->setName('verificarInscripcionFactura');
        $app->post('/verificar-actualizacion-suscriptor', \App\facturaDigital\facturaDigital::class.':verificarActualizacionSuscripcion')->setName('verificarActualizacionSuscripcion');
        $app->post('/getSuscripcion', \App\facturaDigital\facturaDigital::class.':getSuscripcion')->setName('getSuscripcion');

    });
    $app->post('/nomenclaturas', \App\facturaDigital\facturaDigital::class.':nomenclaturas')->setName('nomenclaturas');
    $app->post('/barrios', \App\facturaDigital\facturaDigital::class.':barrios')->setName('barrios');
    $app->post('/usos', \App\facturaDigital\facturaDigital::class.':usos')->setName('usos');

?>