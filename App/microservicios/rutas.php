<?php 
    require __DIR__ . '/davivienda/zona-pagos.php';
    require __DIR__ . '/factura-digital-admin.php';
    require __DIR__ . '/suscriptores-actualizados.php';
    $app->group('/microservicios',function($app){
        $app->group('/davivienda',function($app){
            $app->group('/zona-pagos',function($app){ 
                $app->post('/listar-pagos', \App\microservicios\zonapagos::class.':listarPagos')->setName('listarPagos');
                $app->get('/estado-pago', \App\microservicios\zonapagos::class.':estadoPago')->setName('estadoPago');
                $app->get('/forma-pago', \App\microservicios\zonapagos::class.':formaPago')->setName('formaPago');
            });
        });
        $app->group('/factura-digital',function($app){ 
            $app->post('/listar-inscripciones', \App\microservicios\facturaDigitalAdmin::class.':listarInscripciones')->setName('listarInscripciones');
            $app->post('/listar-envios', \App\microservicios\facturaDigitalAdmin::class.':listarEnvios')->setName('listarEnvios');
            $app->post('/anular', \App\microservicios\facturaDigitalAdmin::class.':anular')->setName('anular');
            $app->get('/enviar-facturas/{periodo}', \App\microservicios\facturaDigitalAdmin::class.':enviarFacturas')->setName('enviarFacturas');
        });
        $app->group('/suscriptores',function($app){
            $app->post('/actualizados', \App\microservicios\suscriptoresActualizados::class.':actualizados')->setName('actualizados');
            $app->post('/tipodoc', \App\microservicios\suscriptoresActualizados::class.':tipodoc')->setName('tipodoc');
            $app->post('/ususys', \App\microservicios\suscriptoresActualizados::class.':ususys')->setName('ususys');
            $app->post('/encalidad', \App\microservicios\suscriptoresActualizados::class.':encalidad')->setName('encalidad');
        });
    });
    
?>