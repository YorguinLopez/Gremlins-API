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
class facturaDigitalAdmin {
    private $db;
    private $mail;
    private $remitente;
    private $password;
    private $nombre;
    public function __construct(ContainerInterface $container) {
        $this->db = $container->get('db');
        $this->remitente = 'aguas.facturaciondigital@gmail.com';
        $this->password = 'aguas2021';
    }
    private function mailConfig(){
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
    }
    public function listarInscripciones(Request $request,Response $response): Response{
        $inscripciones=$this->db->select(" SELECT ins.idfactreg, ins.idusuario, ins.fechareg, DATE(ins.fechareg) AS dfechareg, nit.tipodoc, ins.ndocumento, ins.pnombre, ins.onombre, ins.papellido, ins.oapellido, ins.celular, ins.email, ins.encalidad, (CASE WHEN ins.encalidad='P' THEN 'PROPIETARIO' ELSE 'ARRENDATARIO' END) AS tencalidad, (CASE WHEN ins.validado = true THEN 'VALIDADO' ELSE 'SIN VALIDAR' END) AS tvalidado , fechavalidado, DATE(fechavalidado) AS dfechavalidado, ins.activo, (CASE WHEN ins.activo = TRUE THEN 'ACTIVO' ELSE 'INACTIVO' END) AS tactivo, ins.fechafin, DATE(ins.fechafin) AS dfechafin, anulado, (CASE WHEN anulado = true THEN 'ANULADO' ELSE 'NO APLICA' END) AS tanulado, DATE(fechaanulado) AS dfechaanulado, fechaanulado, razonanulado  FROM fact_dig_inscripciones ins LEFT JOIN tipodoc nit ON nit.idtnit = ins.tipodoc ORDER BY ins.fechareg DESC; ");
        $response->getBody()->write(json_encode($inscripciones));
        return $response;
    }
    public function listarEnvios(Request $request,Response $response): Response{
        $params = $request->getParsedBody();
        $where = (!empty($params['periodo']) ? "WHERE env.periodo = '".$params['periodo']."'": null );
        $envios = $this->db->select(" SELECT env.idhist,env.periodo,ins.idusuario,env.enviado,(CASE WHEN env.enviado = true THEN 'ENVIADO' ELSE 'NO ENVIADO' END) AS tenviado,env.fechaenvio,DATE(env.fechaenvio) AS dfechaenvio,env.factura,ins.ndocumento, ins.pnombre, ins.papellido, ins.email FROM fact_dig_inscripciones ins JOIN fact_dig_hist_envios env ON env.idfactreg = ins.idfactreg ".$where." ORDER BY env.fechaenvio DESC; ");
        $response->getBody()->write(json_encode($envios));
        return $response;
    }
    public function anular(Request $request,Response $response): Response{
        $params=$request->getParsedBody();
        $anular=$this->db->procedure('pu_anular_fact_dig_inscripciones',array_values($params));
        if( $anular['result'] > 0  ){
            $response->getBody()->write(json_encode(array('success'=>true,'severity'=>'success','summary'=>'EXITO','detail'=>'Inscripcion Anulada Correctamente.','result'=>$anular['result'])));
        }else{
            $response->getBody()->write(json_encode($anular));  
        }
        return $response;
    }
    public function email($destino,string $asunto,string $mensaje,$adjuntos = null){
        $this->mailConfig();
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
        if(gettype($adjuntos) == 'array'){
            foreach ($adjuntos as $adj) {
                $this->mail->addAttachment($adj);
            }
        }elseif($adjuntos != null){
            $this->mail->addAttachment($adjuntos);
        }
        $enviado = $this->mail->send();
        if ($enviado == true) {
            return true;
        }else{
            return $enviado;
        }
    }
    public function enviarFacturas(Request $request,Response $response, $args): Response{
        //envia las facturas actuales generadas siempre y cuando no esten canceladas.
        $params=$request->getParsedBody();
        //$periodoRef = date('Ym');
        if(isset($args['periodo']) AND !empty($args['periodo'])){
            $periodoRef = $args['periodo'];
            //produccion
            $usuarios = $this->db->select("SELECT idfactreg, pnombre, papellido, email, idusuario,ultperenv FROM fact_dig_inscripciones WHERE activo = true AND anulado = false AND (ultperenv::int4 < {$args['periodo']} OR ultperenv IS NULL) ORDER BY idusuario ASC;");
            //pruebas
            //$usuarios = $this->db->select("SELECT idfactreg, pnombre, papellido, email_resp AS email, idusuario,'202106' AS ultperenv FROM fact_dig_inscripciones WHERE activo = true ORDER BY idusuario ASC;");
            $logs = array('log' => array(), 'errores' => 0, 'enviados' => 0);
            $meses = array('01'=>'Enero','02' => 'Febrero','03' => 'Marzo', '04' => 'Abril', '05' => 'Mayo', '06' => 'Junio','07' => 'Julio','08' => 'Agosto','09' => 'Septiembre','10' => 'Octubre','11' => 'Noviembre','12' => 'Diciembre');
            try {
                foreach ($usuarios as $usuario) {
                    //codigo consulta datos factura
                    $factura = json_decode(\Httpful\Request::get('https://www.aguasdebarrancabermeja.app/rest/public/index.php/sisloga/consultafactura/'.$usuario['idusuario'])->send(),true);
                    //si el ultimo periodo != null o el periodo es menor al de la factura la envio ,del o contrario la ignora la busqueda del detalle y no enviará factura
                    if(sizeof($factura) > 0 AND (intval($usuario['ultperenv']) < intval($factura[0]['LLAVE_PERIODO']) OR  is_null($usuario['ultperenv'])) ){
                        $factura = $factura[0];
                        $factura['NUMERO'] = round($factura['NUMERO']);
                    }else {
                        $factura = null;
                    }
                    if(!is_null($factura) AND $factura['ESTADO'] == 'P' AND ( is_null($usuario['ultperenv']) OR $factura['LLAVE_PERIODO'] > $usuario['ultperenv'] ) AND $factura['LLAVE_PERIODO'] == $periodoRef){
                        $mes = $meses[substr($factura['LLAVE_PERIODO'],4,2)];
                        //$filename = $this->generarFactura($factura, $detfactura);
                        $asunto = 'FACTURA ACUEDUCTO Y ALCANTARILLADO DE '.strtoupper($mes).' N° '.$factura['NUMERO'] ;
                        $mensaje = file_get_contents(__DIR__.'/assets/plantilla_email_fact.html');
                        //reemplazamoos valores
                        $reemplazos = array(
                            array('key' => 'usuario', 'value' =>$usuario['idusuario']),
                            array('key' => 'pnombre', 'value' =>$usuario['pnombre']),
                            array('key' => 'papellido', 'value' =>$usuario['papellido']),
                            array('key' => 'nfactura', 'value' =>$factura['NUMERO']),
                            array('key' => 'mes', 'value' =>$mes),
                            array('key' => 'valor', 'value' =>number_format(round($factura['TOTALAPAGAR']) ,0)),
                            
                        );
                        foreach ($reemplazos as $reemp) {
                            //var_dump($reemp);
                            $mensaje = str_replace('{{'.$reemp['key'].'}}', $reemp['value'], $mensaje);
                        }
                        //var_dump($mensaje);
                        $histParams =  array(
                            'idfactreg' => $usuario['idfactreg'], 
                            'factura' => $factura['NUMERO'],
                            'periodo' => $factura['LLAVE_PERIODO'], 
                            'enviado' => false,
                            'error' => null
                        );
                        $enviado = $this->email($usuario['email'], $asunto, $mensaje, null);
                        if($enviado === true){
                            $histParams['enviado'] = true;
                            array_push($logs['log'], $histParams);
                            $logs['enviados']++;
                        }else{
                            $histParams['error'] = json_encode($enviado);
                            array_push($logs['log'], $histParams);
                            $logs['errores']++;
                        }
                    $hist = $this->db->procedure('pi_fact_dig_hist_envios', array_values($histParams));
                    //$hist = array('result' => 0); 
                    if($hist['result'] == -1){
                            //var_dump($hist);
                        }
                    }
                }
                $response->getBody()->write(json_encode($logs));
            } catch (\Throwable $th) {
                //throw $th;
                $log = fopen(__DIR__.'/log.txt', 'w+');
                fwrite($log, $th);
                fwrite($log, '\n');
                fclose($log);
                $response->getBody()->write(json_encode(array('success'=>false,'severity'=>'error','summary'=>'TIEMPO DE RESPUESTA DE GESTOR DE CORREOS EXPIRADO.','detail'=>'Se han enviado '.$logs['enviados'].' de '.sizeof($usuarios).' facturas disponibles, vuelva a ejecutar para completar el proceso.','result'=>$anular['result'])));
            }
            
        }else{
            $response->getBody()->write(json_encode(array('success'=>false,'severity'=>'error','summary'=>'ERROR','detail'=>'No hay facturas pendientes por enviar en el periodo seleccionado.','result'=>$anular['result'])));
        }
        return $response;
    }
    private function generarFactura(array $factura, array $detfactura){
        $meses = array('0', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic');
        $meses_comp = array('0', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
        $fechaactual = getdate();
        /***** Periodo Actual  *******/
        $actual = (integer) substr($factura['LLAVE_PERIODO'],4,2);
        $mes_periodo = $meses_comp[$actual];
        $anio = substr($factura['LLAVE_PERIODO'],0,4);
        /* tenemos que generar una instancia de la clase */
        $pdf = new Fpdf('P','mm',array(217, 280));
        //$pdf=new PDF_MC_Table('L','mm','A4');
        $pdf->AddPage();
        $pdf->Image(__DIR__.'/assets/f_factura.png', $x=0, $y=0, $w=217, $h=280, $type='png', $link='');
        /* Seleccionamos el tipo, estilo y tamaño de la letra a utilizar */
        // $pdf->SetFont('Helvetica Fuente)', 'B (Tipo B negrilla)', 14 (Tamaño));
        /* ANOTACIÓN DE COPIA WEB */
        $pdf->SetFont('Helvetica', 'B', 14);
        $pdf->SetXY(26, 26);
        $pdf->Cell(40,10,"** COPIA WEB **",0,0,'C');
        /* Cell: Para crear una celda con contenido puede recibir 8 parámetros: (ancho, alto, texto, bordo, línea, alineación, relleno,  link). */
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->SetXY(164, 11);
        $pdf->Cell(20,10, round($factura['NUMERO']),0,0,'C');
        $pdf->SetFont('Helvetica', 'B', 14);
        $pdf->SetXY(164, 18);
        $pdf->Cell(20,10,$factura['USUARIO'],0,0,'C');
        /* Datos del Suscriptor y Fechas de pago  */
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY(30, 37);
        $pdf->Cell(60,0, utf8_decode($factura['NOMBRE']),0,0,'L');
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->SetXY(148, 37);
        $pdf->Cell(35,0,strftime("%d/%m/%Y", strtotime($factura['FECHAEMISION'])),0,0,'C');
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY(30, 44);
        $pdf->Cell(60,0,$factura['DIR'],0,0,'L');
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->SetXY(148, 44);
        $pdf->Cell(35,0,($fechaactual>$factura['FECHAVENCE'] AND $factura['ATRASOS']>0)?' ** INMEDIATO ** ':strftime("%d/%m/%Y", strtotime($factura['FECHAVENCE'])),0,0,'C');
        $pdf->SetFont('Helvetica', 'B', 14);
        $pdf->SetXY(186, 44);
        $pdf->Cell(20,0,$factura['ATRASOS'],0,0,'C');
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY(30, 50);
        $pdf->Cell(30,0,$mes_periodo.' '.$anio ,0,0,'L');
        $pdf->SetXY(65, 50);
        $pdf->Cell(12,0,$factura['ESTRATO'],0,0,'C');
        $pdf->SetXY(80, 50);
        $pdf->Cell(30,0,$factura['USONOMBRE'],0,0,'C');
        $pdf->SetXY(148, 50);
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(35,0,($fechaactual>$factura['FECHACORTE'] AND $factura['ATRASOS']>0)?' ** INMEDIATO ** ':strftime("%d/%m/%Y", strtotime($factura['FECHACORTE'])),0,0,'C');
        /* Datos del Ultimo Pago y Ubicación */
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY(48, 57);
        $pdf->Cell(20,0, ($factura['VALORPAGO']>0)? " $ " .number_format($factura['VALORPAGO'],0,',','.'):'',0,0,'C');
        $pdf->SetXY(96, 57);
        $pdf->Cell(28,0,($factura['FECHAPAGO']!='')?strftime("%d/%m/%Y", strtotime($factura['FECHAPAGO'])):'',0,0,'C');
        $pdf->SetXY(138, 57);
        $pdf->Cell(10,0,$factura['CICLO'],0,0,'C');
        $pdf->SetXY(158, 57);
        $pdf->Cell(40,0,$factura['RUTA'],0,0,'L');  
        /* Información de Lectura y Consumos */
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY(46, 64);
        $pdf->Cell(40,0,strftime("%d-%b-%Y", strtotime($factura['PERIODOI'])),0,0,'L'); 
        $pdf->SetXY(106, 64);
        $pdf->Cell(20,0,$factura['LANTERIOR'],0,0,'R');
        $pdf->SetXY(172, 64);
        $pdf->Cell(10,0,$factura['PROMEDIO'],0,0,'C');
        $pdf->SetXY(46, 71);
        $pdf->Cell(40,0,strftime("%d-%b-%Y", strtotime($factura['PERIODOF'])),0,0,'L');
        $pdf->SetXY(106, 71);
        $pdf->Cell(20,0,$factura['LACTUAL'],0,0,'R');
        /* Número de medidor */
        $pdf->SetXY(52, 80); 
        $pdf->Cell(40,0,$factura['NMEDIDOR'],0,0,'L');
        $pdf->SetXY(106, 80);
        $pdf->Cell(20,0,round($factura['CONSUMO']),0,0,'C');
        /*$pdf->SetXY(62, 88);
        $pdf->Cell(10,0,$factura['PROMEDIO'],0,0,'C');*/
        $pdf->SetXY(116, 81);
        $pdf->Cell(30,0,'',0,0,'C');
        /* Causal de no Lectura  y Calculo de Consumo*/
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetXY(56, 88); 
        $pdf->Cell(40,0,' ',0,0,'L');
        $pdf->SetXY(122, 88);
        $pdf->Cell(10,0,' ',0,0,'C');
        $x = 133;
        $arrayconsumos = array(round($factura['CONSUMO']), $factura['CON1'], $factura['CON2'], $factura['CON3'], $factura['CON4'], $factura['CON5'], $factura['CON6']);
        $max = max($arrayconsumos);
        $pdf->line(134,86.1,203,86.1);
        $pdf->line(134,86.3,203,86.3);
        for ($i=7; $i>0; $i--) {
            if($i==7){
                $mes = 'Act';
                $R = 179; $G = 211; $B = 66;
                $BR = 100; $BG = 130; $BB = 0;
                $alto = ($arrayconsumos[0]>0)?($arrayconsumos[0]/$max):0;
            }else{
                $R = $G = $B = 150;
                $BR = 120; $BG = 120; $BB = 120;
                $mes = $meses[$actual];
                $alto = ($arrayconsumos[($i)]>0)?($arrayconsumos[($i)]/$max):0;
            }
            $pdf->SetFillColor($R, $G, $B); //Color de Relleno
            $pdf->SetDrawColor($BR, $BG, $BB); //Color de Borde
            $pdf->Rect($x+2,81,7,-14*$alto,'DF');
            $pdf->SetXY($x+1.5, 84);
            $pdf->Cell(8,0,($i==7)?round($factura['CONSUMO']):$factura['CON'.($i)],0,0,'C');
            $pdf->SetXY($x+1.5, 89);
            $pdf->Cell(8,0,$mes,0,0,'L');
            $x=$x+10;
            $actual--;
            ($actual==0)?$actual=12:'';
        }
        $pdf->SetDrawColor(0,0,0); //Color de Borde
        /* Costos de acueducto y alcantarillado */
        $pdf->SetXY(96, 89.5);
        // $pdf->Cell(30,12,number_format($factura['VLRCARGOF'], 0, ',', '.'),0,0,'C');
        $pdf->Cell(30,12,number_format(7887, 0, ',', '.'),0,0,'C');
        $pdf->SetXY(160, 89.5);  
        $pdf->Cell(40,12,number_format(1631, 0, ',', '.'),0,0,'C');
        $pdf->SetXY(96, 96.5);
        // $pdf->Cell(30,12,number_format($factura['CFALCANT'], 0, ',', '.'),0,0,'C');
        $pdf->Cell(30,12,number_format(4920, 0, ',', '.'),0,0,'C');
        $pdf->SetXY(160, 96.5);  
        $pdf->Cell(40,12,number_format(1473, 0, ',', '.'),0,0,'C');
        /* Liquidación de la factura de acueducto y alcantarillado */
        /******   Detalle factura    *******/
        $tamano = count($detfactura);
        $d = 126;
        $total_mes = 0;
        $total_saldo = 0;
        $total_sub_con = 0;
        $total_con = 0;
        for ($i=0; $i<$tamano; $i++) {
            $total_mes = $total_mes + $detfactura[$i]->MES;
            $total_saldo = $total_saldo +  $detfactura[$i]->REFACTURADO;
            $total_sub_con += ($detfactura[$i]->SOBREPRECIO>0)?$detfactura[$i]->SOBREPRECIO:($detfactura[$i]->SUBSIDIO);
            $pdf->SetXY(8, $d);
            $pdf->Cell(44,0, $detfactura[$i]->XNOMBRE,0,0,'L');
            $pdf->SetXY(82, $d);
            $pdf->Cell(24,0,number_format($detfactura[$i]->MES,0,',','.'),0,0,'R');
            $pdf->SetXY(105, $d);
            $pdf->Cell(22,0,($detfactura[$i]->SOBREPRECIO>0) ? number_format($detfactura[$i]->SOBREPRECIO,0,',', '.') : number_format(($detfactura[$i]->SUBSIDIO),0,',','.'),0,0,'R');
            $pdf->SetXY(126, $d);
            $pdf->Cell(30,0, number_format($detfactura[$i]->REFACTURADO,0,',','.'),0,0,'R');
            $pdf->SetXY(170, $d);
            $pdf->Cell(32,0, number_format($detfactura[$i]->TOTAL,0,',','.'),0,0,'R');
            $d = $d + 4.5;
        }
        /******     Totales    **********/
        $pdf->line(8,179.5,208,180.5);
        $pdf->line(8,180,208,181);
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->SetXY(8, 185);
        $pdf->Cell(60,0,'TOTALES',0,0,'C');
        $pdf->SetXY(82, 185);
        $pdf->Cell(24,0, number_format($total_mes, 0, ',', '.'),0,0,'R');
        $pdf->SetXY(105, 185);
        $pdf->Cell(22,0, number_format($total_sub_con, 0, ',', '.'),0,0,'R');
        $pdf->SetXY(126, 185);
        $pdf->Cell(30,0, number_format($total_saldo, 0, ',', '.'),0,0,'R');
        $pdf->SetXY(170, 185);
        $pdf->Cell(32,0, number_format($factura['TOTALAPAGAR'], 0, ',', '.'),0,0,'R');
        /*********    Observación de lectura    ************/
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->SetXY(8, 217);
        $pdf->Cell(32,0, utf8_decode(' OBSERVACIÓN: '),0,0,'L');
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY(45, 213.5);
        // $pdf->Cell(230,20, utf8_decode('Esta es la observación de la lectura y esta parametrizada para x cantidad de cáracteres en una o 2 lineas del espacio de impresión '),0,0,'L');
        //$pdf->Multicell(164,6, utf8_decode('Esta es la observación de la lectura y esta parametrizada para x cantidad de cáracteres en una o 2 lineas del espacio de impresión y con un máximo de 198 cáracteres de longitud y tamaño de letra 10.'),0,'L');
        /*********    Acuerdo de Pago     ************/
        /*$pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY(47, 202);
        $pdf->Cell(20,0,'01/01/1900',0,0,'R');
        $pdf->SetXY(64, 202);
        $pdf->Cell(20,0,'0',0,0,'R');
        $pdf->SetXY(82, 202);
        $pdf->Cell(20,0,'0',0,0,'R');
        $pdf->SetXY(106, 202);
        $pdf->Cell(20,0,'0',0,0,'C');
        $pdf->SetXY(122, 202);
        $pdf->Cell(20,0,'0',0,0,'R');
        $pdf->SetXY(144, 202);
        $pdf->Cell(20,0,'0',0,0,'C');
        $pdf->SetXY(161, 202);
        $pdf->Cell(20,0,'0',0,0,'R');
        $pdf->SetXY(189, 202);
        $pdf->Cell(14,0,'0',0,0,'R');*/
        /* Desprendible de Pago */
        $nfactura = str_pad(round($factura['NUMERO']), 8, "0", STR_PAD_LEFT);
        $valor = str_pad(round($factura['TOTALAPAGAR']), 10, "0", STR_PAD_LEFT);
        $mesf = substr($factura['FECHAVENCE'],5,2);
        $diaf = substr($factura['FECHAVENCE'],8,2);
        /*** Código de Barras ****/
        switch ($factura['ESTADO']) {
            case 'A':
                $pdf->SetFont('Helvetica', '', 21);
                $pdf->SetXY(10, 240);        
                $pdf->Cell(120,0,'FACTURA ANULADA',0,0,'C');
                $pdf->SetXY(10, 250);
                $pdf->Cell(120,0,'CIERRE DE PERIODO',0,0,'C');
            break;
            case 'C':
                $pdf->SetFont('Helvetica', 'B', 18);
                $pdf->SetXY(12, 240);
                $pdf->Cell(120,10,'FACTURA CANCELADA ',0,0,'C');
                $pdf->SetFont('Helvetica', '', 16);
                $pdf->SetXY(12, 250);
                $pdf->Cell(120,10,'FECHA: '.strftime("%d/%m/%Y", strtotime($factura['FECHAPAGO'])). " -- VALOR: $ ".number_format($factura['VALORPAGO'],0,',','.'),0,0,'C');
            break;
            case 'P':
                $pdf->SetFont('Helvetica', 'B', 9);
                $pdf->SetXY(10, 232);
                // $pdf->Cell(120,0,'FAVOR DEJAR ESTE ESPACIO LIBRE DE FIRMAS Y SELLOS',0,0,'C');
                // Valores Iniciales
                $fontSize  = 8;
                $marge     = 4;        // Between barcode and hri in pixel
                $x         = 70;       // Centro del código de barras en x
                $y          = 250;     // Centro del código de barras en y
                $height    = 18;       // Altura del código de barras 1D; module size in 2D
                $width     = 0.24;     // Ancho de código de barras 1D; not use in 2D
                $angle     = 0;        // Rotacion en grados
                $labelcode = " (415)7709998244696(8020)".$nfactura."(3900)".$valor."(96)".$anio.$mesf.$diaf; // Etiqueta Código
                $code      = "41577099982446968020".$nfactura."3900".$valor."96".$anio.$mesf.$diaf;          //Código
                $type      = 'code128';
                $black     = '000000'; // Color en hexa
                $data =$pdf->fpdf($pdf, $black, $x, $y, $angle, $type, array('code'=>$code), $width, $height);
                //$data = Barcode::fpdf($pdf, $black, $x, $y, $angle, $type, array('code'=>$code), $width, $height);
                // -------------------------------------------------- //
                //                      HRI              //
                // -------------------------------------------------- //
                $pdf->SetFont('Arial','',$fontSize);
                $pdf->SetTextColor(0, 0, 0);
                //$pdf->SetXY(32, 244);
                //$len = $pdf->GetStringWidth($data['hri']);
                //Barcode::rotate(-$len / 2, ($data['height'] / 2) + $fontSize + $marge, $angle, $xt, $yt);
                $pdf->TextWithRotation($x - 47, $y + 12, $labelcode, $angle);
                break;
                default:
                $pdf->SetFont('Helvetica', '', 18);
                $pdf->SetXY(16, 240);
                $pdf->Cell(100,10,'FACTURA NO APTA PARA RECAUDO',0,0,'C');
            break;
        }
        /****  Datos ****/
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetXY(167, 239);
        $pdf->Cell(40,0,'$ '.number_format($factura['TOTALAPAGAR'],0,',','.'),0,0,'R');
        $pdf->SetXY(167, 245);
        $pdf->Cell(40,0,round($factura['NUMERO']),0,0,'R');
        $pdf->SetXY(167, 251);
        $pdf->Cell(40,0,($fechaactual>$factura['FECHAVENCE'] AND $factura['ATRASOS']>0)?'** INMEDIATO **':strftime("%d/%m/%Y", strtotime($factura['FECHAVENCE'])),0,0,'R');
        $pdf->SetXY(167, 257);
        $pdf->Cell(40,0,$factura['USUARIO'],0,0,'R');
        // $pdf->SetXY(167, 254);
        // $pdf->Cell(40,0,$factura['RUTA'],0,0,'R');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetXY(167, 262);
        $pdf->Cell(40,0,$mes_periodo.' '.$anio,0,0,'R');
        //$pdf->Cell(60,7,"Dirección",1,0,'C');
        //$pdf->Ln(15);//ahora salta 15 lineas
        //$pdf->SetTextColor('255','0','0');//para imprimir en rojo
        //$pdf->Multicell(190,7," Esta es la prueba \n del multicell",1,'R');
        $filename = "FACT_".$factura['USUARIO'].'_'.$factura['NUMERO'].'.pdf';
        $pdf->Output('F',$filename, 'D');
        //$pdf->Output($filename, 'I');
        return $filename;
    //exit;
    }
}
?>
            
