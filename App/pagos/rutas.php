<?php 
    require __DIR__ . '/pagos.php';
    $app->group('/pagos',function($app){
        $app->post('/tipo-documento', \App\pagos\pagos::class.':tipoDocumento')->setName('TipoDocumento');
        $app->post('/guardar', \App\pagos\pagos::class.':guardar')->setName('guardar');
        $app->post('/titular', \App\pagos\pagos::class.':titular')->setName('titular');
        $app->post('/iniciar-pago', \App\pagos\pagos::class.':iniciarPago')->setName('iniciarPago');
        $app->get('/verificar-pago/{idpago}', \App\pagos\pagos::class.':verificarPago')->setName('verificarPago');
        $app->post('/verificar-pendiente', \App\pagos\pagos::class.':verificarPendiente')->setName('verificarPendiente');
        $app->get('/resultadopago', \App\pagos\pagos::class.':resultadoPago')->setName('resultadoPago');
        $app->get('/listar-clientes', \App\pagos\pagos::class.':listarClientes')->setName('listarClientes');
        $app->get('/actualizar-pendientes', \App\pagos\pagos::class.':actualizarPagosPendientes')->setName('actualizarPagosPendientes');
        $app->get('/actualizar-antiguos/{idusuario}/{fechaini}/{fechafin}/{forzar}', \App\pagos\pagos::class.':actualizarAntiguos')->setName('actualizarAntiguos');

    });
?>