<?php
$container->set('db_settings',function(){
    return [
        'DB_HOST'=>'localhost',
        'DB_DSN'=>'pgsql',
        'DB_NAME'=>'pasarelapagos',
        'DB_USER'=>'postgres',
        'DB_PASSWORD'=>'MasAguas(2020',
        'DB_PORT'=>'5432'
    ];
});
$container->set('db2_settings',function(){
    return [
        'DB_HOST'=>'localhost',
		'DB_DSN'=>'firebird',
        'DB_NAME'=>'c:/app/comercial/servicio/datos/servicio.gdb;charset=utf8',
        'DB_USER'=>'SYSDBA',
        'DB_PASSWORD'=>'masterkey'
    ];
});
?>