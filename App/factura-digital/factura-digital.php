<?php 

namespace App\facturaDigital;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\UploadedFileInterface;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class facturaDigital {
    private $db;
    private $firebird;
    private $mail;
    private $remitente;
    private $password;
    private $nombre;
    public function __construct(ContainerInterface $container) {
        $this->db = $container->get('db');
        //$this->nombre = 'AGUAS DE BARRANCABERMEJA S.A E.S.P.';
        $this->remitente = 'aguas.facturaciondigital@gmail.com';
        $this->password = 'aguas2021';
        $this->mail = new PHPMailer(true);
        //configuración inicial mail
        $this->mail->SMTPDebug = 0;
        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.gmail.com';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = $this->remitente;
        $this->mail->Password = $this->password;
        $this->mail->SMTPSecure = 'tls';
        $this->mail->Port = 587;
        $this->mail->setFrom($this->remitente);
        //$this->firebird = $container->get('db2');
    }
    public function email($destino,string $asunto,string $mensaje){
        if(gettype($destino) == 'array'){
            foreach ($destino as $dest) {
                $this->mail->addAddress($dest);
            }
        }else{
            $this->mail->addAddress($destino);
        }
        $this->mail->isHTML(true);
        $this->mail->Subject = $asunto;
        $this->mail->Body    = $mensaje;
        $this->mail->AltBody = '';
        $this->mail->CharSet = 'UTF-8';
        if ($this->mail->send()) {
            return true;
        }else{
            return false;
        }
    }
    public function nomenclaturas(Request $request,Response $response): Response{
        $nomenclaturas=$this->db->select("SELECT abreviatura AS value,nombre AS label,complementos FROM nomenclatura ORDER BY nombre ASC;");
        $response->getBody()->write(json_encode($nomenclaturas));
        return $response;
    }
    public function usos(Request $request,Response $response): Response{
        $params=$request->getParsedBody();
        $usos=$this->db->select("SELECT iduso AS value, uso,CONCAT(uso,' ',descripcion) AS label FROM usos ORDER BY uso ASC;");
        $response->getBody()->write(json_encode($usos));
        return $response;
    }
    public function barrios(Request $request,Response $response): Response{
        $params=$request->getParsedBody();
        $barrios=$this->db->select("SELECT idbarrio AS value, barrio AS label FROM barrios b ORDER BY b.barrio ASC;");
        $response->getBody()->write(json_encode($barrios));
        return $response;
    }
    public function verificarInscripcionFactura(Request $request,Response $response): Response{
        $params=$request->getParsedBody();
        $verificacion = $this->db->select("SELECT idfactreg, idusuario, email, codgen, activo, validado FROM fact_dig_inscripciones WHERE idusuario = '".$params['idusuario']."' AND fechafin IS NULL AND anulado = false ORDER BY validado ASC;");
        $response->getBody()->write(json_encode($verificacion));
        return $response;
    }
    public function verificarActualizacionSuscripcion(Request $request,Response $response): Response{
        $params=$request->getParsedBody();
        $verificacion = $this->db->select("SELECT COUNT(idsusc) AS total FROM fact_dig_act_suscriptores WHERE idusuario='".$params['idusuario']."' GROUP BY idusuario;");
        $response->getBody()->write(json_encode($verificacion));
        return $response;
    }
    public function tipoDocumento(Request $request,Response $response): Response{
        $tipoDoc=$this->db->select("SELECT idtnit AS value, tipodoc AS label FROM tipodoc;");
        $response->getBody()->write(json_encode($tipoDoc));
        return $response;
    }
    public function verificar(Request $request,Response $response): Response{
        $params=$request->getParsedBody();
        $validar=$this->db->update("UPDATE fact_dig_inscripciones SET validado = true, activo = true, fechavalidado = NOW() WHERE idfactreg = ".$params['idfactreg'].";");
        $response->getBody()->write(json_encode($validar));
        return $response;
    }
    public function getSuscripcion(Request $request,Response $response): Response{
        $params=$request->getParsedBody();
        $suscripcion = $this->db->select("SELECT *,celular AS ccelular FROM fact_dig_act_suscriptores WHERE idusuario = '".$params['idusuario']."';");
        $response->getBody()->write(json_encode($suscripcion));
        return $response;
    }
    public function actualizarSuscripcion(Request $request,Response $response): Response{
        $params=$request->getParsedBody();
        $actualizar=$this->db->procedure('pi_fact_dig_suscriptores',array_values($params));
        if( $actualizar['result'] > 0  ){
            $response->getBody()->write(json_encode(array('success'=>true,'severity'=>'success','summary'=>'EXITO','detail'=>'Suscripción Registrada Correctamente.','result'=>$actualizar['result'])));
        }else{
            $response->getBody()->write(json_encode($actualizar));  
        }
        return $response;
    }
    public function reenviar(Request $request,Response $response): Response{
        $params=$request->getParsedBody();
        $asunto = "AGUAS DE BARRANCABERMEJA S.A E.S.P - VERIFICACIÓN FACTURACIÓN DIGITAL";
        $mensaje = "Cordial Saludo <br> El código de verificación para validar el envio de factura digital a tu correo es <b>".$params['codgen']."</b>";
        $enviado = $this->email($params['email'], $asunto, $mensaje);
        $response->getBody()->write(json_encode($enviado));  
        return $response;
    }
    public function inscripcion(Request $request,Response $response): Response{
        $params=$request->getParsedBody();
        //unset($params['observa']);
        //var_dump($params);
        $inscripcion=$this->db->procedure('pi_fact_dig_inscripciones',array_values($params));
        if($inscripcion['result'] > 0 ){
            $asunto = "AGUAS DE BARRANCABERMEJA S.A E.S.P - VERIFICACIÓN FACTURACIÓN DIGITAL";
            $mensaje = "Cordial Saludo <br> El código de verificación para validar el envio de factura digital a tu correo es <b>".$params['codgen']."</b>";
            $enviado = $this->email($params['email'], $asunto, $mensaje);
            $response->getBody()->write(json_encode(array('success'=>true,'severity'=>'success','summary'=>'EXITO','detail'=>'Inscripción Registrada Correctamente.','email'=>$enviado,'result'=>$inscripcion['result'])));
        }else{
            $response->getBody()->write(json_encode($inscripcion));  
        }
        return $response;
    }
}
?>

