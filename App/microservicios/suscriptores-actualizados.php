<?php 
namespace App\microservicios;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\UploadedFileInterface;

use Fpdf\Fpdf;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
class suscriptoresActualizados {
    private $db;
    private $mail;
    private $remitente;
    private $password;
    private $nombre;
    public function __construct(ContainerInterface $container) {
        $this->db = $container->get('db');
    }
    public function actualizados(Request $request,Response $response): Response{
        $suscriptores = $this->db->select("SELECT s.*,td.tipodoc AS ttipodoc, CONCAT(u.nombres, ' ', u.apellidos) AS ususys, b.barrio AS tbarrio, (CASE s.encalidad WHEN 'P' THEN 'PROPIETARIO' WHEN 'A' THEN 'ARRENDATARIO' WHEN 'F' THEN 'FAMILIAR' ELSE null END) AS tencalidad FROM fact_dig_act_suscriptores s JOIN tipodoc td ON s.tipodoc = td.idtnit JOIN barrios b ON b.idbarrio = s.barrio LEFT JOIN ususys u ON u.idusr = s.idusr ORDER BY fechareg DESC;");
        $response->getBody()->write(json_encode($suscriptores));
        return $response;
    }
    public function ususys(Request $request,Response $response): Response{
        $ususys = $this->db->select("SELECT u.idusr AS value,CONCAT(u.nombres, ' ', u.apellidos,' (',COUNT(s.idsusc),')') AS label FROM ususys u LEFT JOIN fact_dig_act_suscriptores s ON s.idusr = u.idusr GROUP BY u.idusr ORDER BY COUNT(s.idsusc) DESC;");
        $response->getBody()->write(json_encode($ususys));
        return $response;
    }
    public function tipodoc(Request $request,Response $response): Response{
        $tipodoc = $this->db->select("SELECT idtnit AS value,tipodoc AS label FROM tipodoc;");
        $response->getBody()->write(json_encode($tipodoc));
        return $response;
    }
    public function encalidad(Request $request,Response $response): Response{
        $encalidad = array(
            array('label' => 'PROPIETARIO', 'value' => 'P'),
            array('label' => 'ARRENDATARIO', 'value' => 'A'),
            array('label' => 'FAMILIAR', 'value' => 'F')
        );
        $response->getBody()->write(json_encode($encalidad));
        return $response;
    }
}
?>
            
