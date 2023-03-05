<?php
    require __DIR__ . '/grem.php';
    $app->group('/grem',function($app) {
        // Login
        $app->get('/login/{email}', \App\gremlins\grem::class.':login')->setName('login');
        // Clientes
        $app->post('/creacliente', \App\gremlins\grem::class.':insCliente')->setName('creacliente');
        $app->put('/updcliente', \App\gremlins\grem::class.':updCliente')->setName('updcliente');
        $app->get('/clientedoc/{documento}', \App\gremlins\grem::class.':selCliente_doc')->setName('clientedoc');
        // Proveedores
        $app->post('/creaproveedor', \App\gremlins\grem::class.':insProveedor')->setName('creaproveedor');
        $app->put('/updproveedor', \App\gremlins\grem::class.':updProveedor')->setName('updproveedor');
        $app->get('/proveedordoc/{documento}', \App\gremlins\grem::class.':selProveedor_doc')->setName('proveedordoc');
        // Productos
        $app->post('/creaproducto', \App\gremlins\grem::class.':insProducto')->setName('creaproducto');
        $app->post('/updproducto', \App\gremlins\grem::class.':updProducto')->setName('updproducto');
        $app->get('/producto/{id}', \App\gremlins\grem::class.':selProducto_id')->setName('producto');
        // Pedidos
        $app->post('/creapedido', \App\gremlins\grem::class.':insPedido')->setName('creapedido');
        $app->put('/updpedido', \App\gremlins\grem::class.':updPedido')->setName('updpedido');
        $app->get('/pedidocliente/{cliente}', \App\gremlins\grem::class.':selPedido_cli')->setName('pedidocliente');
    });
?>