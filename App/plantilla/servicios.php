<?php 
namespace App\plantilla;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\UploadedFileInterface; 
 class servicios{
    private $db;
    public function __construct(ContainerInterface $container) {
        $this->db = $container->get('db');
    }
    public function listar(Request $request,Response $response): Response{
        $params=$request->getParsedBody();
        $servicios=$this->db->select("SELECT * FROM servicios");
        $response->getBody()->write(json_encode($servicios));
        return $response;
    }
}
?>

