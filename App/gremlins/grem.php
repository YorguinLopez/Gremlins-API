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
        
        function nFunc(Request $req, Response $res, $args): Response {

            return $res;
        }

        function login(Request $req, Response $res, $args): Response {
            $q = "SELECT * FROM usuarios WHERE email = '{$args['email']}'";
            $usuario = $this->db->select($q);
            $res->getBody()->write(json_encode($usuario));
            return $res;
        }

        function insCliente(Request $req, Response $res, $args): Response {
            $param =  $req->getParsedBody();
            try {
                $q = "INSERT INTO clientes (email,nombre,apellido,documento,telefono,direccion) 
                    VALUES (
                        '{$param['email']}',
                        '{$param['nombre']}',
                        '{$param['apellido']}',
                        '{$param['documento']}',
                        '{$param['telefono']}',
                        '{$param['direccion']}'
                    );";
                $r = $this->db->insert($q);
            } catch(Exception $e) {
                $r->$http_response_header(500);
                exit;
            }
            $res->getBody()->write(json_encode($r));
            return $res;
        }

        function updCliente(Request $req, Response $res, $args): Response {
            $param =  $req->getParsedBody();
            try {
                $q = "UPDATE clientes SET 
                    email = '{$param['email']}',
                    nombre = '{$param['nombre']}',
                    apellido = '{$param['apellido']}',
                    documento = '{$param['documento']}',
                    telefono = '{$param['telefono']}',
                    direccion = '{$param['direccion']}';";
                $r = $this->db->update($q);
            } catch(Exception $e) {
                $r->$http_response_header(500);
                exit;
            }
            $res->getBody()->write(json_encode($r));
            return $res;
        }


        function selCliente_doc(Request $req, Response $res, $args): Response {
            $q = "SELECT * FROM clientes WHERE documento = '{$args['documento']}'";
            $r = $this->db->select($q);
            $res->getBody()->write(json_encode($r));
            return $res;
        }

        function insProveedor(Request $req, Response $res, $args): Response {
            $param =  $req->getParsedBody();
            try {
                $q = "INSERT INTO proveedores (email,nombre,apellido,documento,telefono,direccion) 
                    VALUES (
                        '{$param['email']}',
                        '{$param['nombre']}',
                        '{$param['apellido']}',
                        '{$param['documento']}',
                        '{$param['telefono']}',
                        '{$param['direccion']}'
                    );";
                $r = $this->db->insert($q);
            } catch(Exception $e) {
                $r->$http_response_header(500);
                exit;
            }
            $res->getBody()->write(json_encode($r));
            return $res;
        }

        function updProveedor(Request $req, Response $res, $args): Response {
            $param =  $req->getParsedBody();
            try {
                $q = "UPDATE proveedores SET 
                    email = '{$param['email']}',
                    nombre = '{$param['nombre']}',
                    apellido = '{$param['apellido']}',
                    documento = '{$param['documento']}',
                    telefono = '{$param['telefono']}',
                    direccion = '{$param['direccion']}';";
                $r = $this->db->update($q);
            } catch(Exception $e) {
                $r->$http_response_header(500);
                exit;
            }
            $res->getBody()->write(json_encode($r));
            return $res;
        }

        function selProveedor_doc(Request $req, Response $res, $args): Response {
            $q = "SELECT * FROM proveedores WHERE documento = '{$args['documento']}'";
            $r = $this->db->select($q);
            $res->getBody()->write(json_encode($r));
            return $res;
        }

        function insProducto(Request $req, Response $res, $args): Response {
            $param =  $req->getParsedBody();
            try {
                $q = "INSERT INTO productos (detalle,pv,iva,existencia,proveedor) 
                    VALUES (
                        '{$param['detalle']}',
                        {$param['pv']},
                        {$param['iva']},
                        {$param['existencia']},
                        {$param['proveedor']}
                    );";
                $r = $this->db->insert($q);
            } catch(Exception $e) {
                $r->$http_response_header(500);
                exit;
            }
            $res->getBody()->write(json_encode($r));
            return $res;
        }

        function updProducto(Request $req, Response $res, $args): Response {
            $param =  $req->getParsedBody();
            try {
                $q = "UPDATE productos SET 
                    detalle = '{$param['detalle']}',
                    pv = {$param['pv']},
                    iva = {$param['iva']},
                    existencia = {$param['existencia']},
                    proveedor = {$param['proveedor']};";
                $r = $this->db->insert($q);
            } catch(Exception $e) {
                $r->$http_response_header(500);
                exit;
            }
            $res->getBody()->write(json_encode($r));
            return $res;
        }

        function selProducto_id(Request $req, Response $res, $args): Response {
            $q = "SELECT * FROM productos WHERE id = {$args['id']}";
            $usuario = $this->db->select($q);
            $res->getBody()->write(json_encode($usuario));
            return $res;
        }

        function insPedido(Request $req, Response $res, $args): Response {
            $param =  $req->getParsedBody();
            try {
                $q = "INSERT INTO clientes (cliente,producto,cantidad,vu,iva,transp,fechaentrega) 
                    VALUES (
                        '{$param['cliente']}',
                        '{$param['producto']}',
                        '{$param['cantidad']}',
                        '{$param['vu']}',
                        '{$param['iva']}',
                        '{$param['transp']}',
                        '{$param['fechaentrega']}'
                    );";
                $r = $this->db->insert($q);
            } catch(Exception $e) {
                $r->$http_response_header(500);
                exit;
            }
            $res->getBody()->write(json_encode($r));
            return $res;
        }

        function updPedido(Request $req, Response $res, $args): Response {
            $param =  $req->getParsedBody();
            try {
                $q = "UPDATE clientes set fechaentrega = '{$param['entrega']}' WHERE idpedido = {$param['idpedido']};";
                $r = $this->db->update($q);
            } catch(Exception $e) {
                $r->$http_response_header(500);
                exit;
            }
            $res->getBody()->write(json_encode($r));
            return $res;
        }

        function selPedido_cli(Request $req, Response $res, $args): Response {
            $q = "SELECT * FROM clientes WHERE documento = '{$args['documento']}'";
            $usuario = $this->db->select($q);
            $res->getBody()->write(json_encode($usuario));
            return $res;
        }

    }
?>

