<?php
    ini_set("soap.wsdl_cache_enabled", "0");
    ini_set('memory_limit','12000M');
    ini_set('max_execution_time', 180000);//3000
    require __DIR__ . '/../vendor/autoload.php';    
    use DI\Container;
    use Slim\Factory\AppFactory;
    use Psr\Http\Message\ServerRequestInterface as Request;
    use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
    $container=new Container();
    AppFactory::setContainer($container);
    $app = AppFactory::create();
    $app->setBasePath((function () {
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $uri = (string) parse_url('http://a' . $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        if (stripos($uri, $_SERVER['SCRIPT_NAME']) === 0) {
            return $_SERVER['SCRIPT_NAME'];
        }
        if ($scriptDir !== '/' && stripos($uri, $scriptDir) === 0) {
            return $scriptDir;
        }
        return '';
    })());
    //middleware
    $app->addBodyParsingMiddleware();
    $app->addRoutingMiddleware();
    //la suguiente linea cambia en produccion, vaya a la documentacion ._.
    $app->addErrorMiddleware(true, true, true);
    $beforeMiddleware = function (Request $request, RequestHandler $handler) {
        $response = $handler->handle($request);
        $existingContent = (string) $response->getBody();
        $response = new Response();
        $response->getBody()->write('BEFORE' . $existingContent);
        return $response;
    };
    $afterMiddleware = function ($request, $handler) {
        $response = $handler->handle($request);
        return $response
        ->withHeader('Content-Type','application/json')
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    };
    //$app->add($beforeMiddleware);
    $app->add($afterMiddleware);
    $app->add(function (Request $request, RequestHandler $handler) {
        return $handler->handle($request);
    });
    //llamamos archivos configuracion
    require __DIR__ . '/../config/settings.php'; 
    require __DIR__ . '/../config/dependencies.php'; 
    //llamamos las rutas.
    require __DIR__ . '/../App/routes.php';
    $app->run();
?>