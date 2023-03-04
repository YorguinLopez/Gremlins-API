<?php
    use Psr\Container\ContainerInterface;
    require __DIR__ . '/db.php'; 
    $container->set('db',function(ContainerInterface $cont){
        $settings=$cont->get('db_settings');
        return new db($settings);
    });
    $container->set('db2',function(ContainerInterface $cont){
        $settings=$cont->get('db2_settings');
        return new db($settings);
    });
?>