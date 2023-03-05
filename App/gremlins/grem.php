<?php 
    namespace App\gremlins;
    use Psr\Http\Message\ResponseInterface as Response;
    use Psr\Http\Message\ServerRequestInterface as Request;
    use Psr\Container\ContainerInterface;
    use Psr\Http\Message\UploadedFileInterface;
    
    class grem {
        private $db;

        function __construct(ContainerInterface $cont) {
            $this->db = $cont->get('db');
        }

        function login(Request $req, Response $res, $args): Response {
            $q = "SELECT * FROM usuarios WHERE email = '{$args['email']}'";
            $usuario = $this->db->select($q);
            $res->getBody()->write(json_encode($usuario));
            return $res;
        }
    }
?>