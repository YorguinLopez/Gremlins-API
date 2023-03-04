<?php 
namespace App\microservicios;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\UploadedFileInterface;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class zonapagos {
    private $db;
    public function __construct(ContainerInterface $container) {
        $this->db = $container->get('db');
    }
    public function estadoPago(Request $request,Response $response): Response{
        $pagos=$this->db->select("SELECT estpago AS value, CONCAT(estpago,' - ',descestpago) AS label FROM zonapagos_est_pago;");
        array_unshift($pagos,array('value' => 0,'label' => '0 - SIN ESTADO'));
        $response->getBody()->write(json_encode($pagos));
        return $response;
    }
    public function formaPago(Request $request,Response $response): Response{
        $formaPagos=$this->db->select("SELECT forma_pago AS value, CONCAT(forma_pago,' - ',desc_forma_pago) AS label FROM zonapagos_forma_pago ORDER BY forma_pago ASC;");
        $response->getBody()->write(json_encode($formaPagos));
        return $response;
    }
    public function listarPagos(Request $request,Response $response): Response{
        $params = $request->getParsedBody();
        if( !empty($params['fechaini']) AND !empty($params['fechafin']) ){
            $where =" WHERE DATE(fechainipago) BETWEEN '".$params['fechaini']."' AND '".$params['fechafin']."' ";
        }else if(!empty($params['fechaini'])){
            $where = "WHERE DATE(fechainipago)  = '".$params['fechaini']."'";
        }else{
            $where = null;
        }
        $pagos = $this->db->select("SELECT c.idcliente, c.usuario, c.nfactura, CONCAT(zep.estpago,' - ',zep.descestpago) AS testadopago, (CASE WHEN c.estado_pago IS NULL THEN 0 ELSE c.estado_pago END) AS estado_pago, c.ped_numero, td.idtnit, td.tipodoc, c.numdocc, c.nombresc, c.apellidosc, c.celularc, c.emailc, c.fechainipago, DATE(fechainipago) AS finipago, c.fechafinpago, DATE(fechafinpago) AS ffinpago, c.valor, c.valor_pagado, c.n_pago, c.id_forma_pago, concat(zfp.forma_pago,' - ',zfp.desc_forma_pago) AS tformapago, c.cod_transaccion, c.cod_aprobacion, concat(c.codigo_banco, ' - ', c.nombre_banco) AS tbanco, c.franquicia, c.numero_tarjeta, c.fecha_iter, c.iter_act, c.pago_terminado FROM clientes c LEFT JOIN tipodoc td ON c.tipodocc = td.idtnit LEFT JOIN  zonapagos_est_pago zep ON zep.estpago = c.estado_pago LEFT JOIN zonapagos_forma_pago zfp ON zfp.forma_pago = c.id_forma_pago ".$where." ORDER BY finipago DESC");
        $response->getBody()->write(json_encode($pagos));
        return $response;
    }
}
?>

