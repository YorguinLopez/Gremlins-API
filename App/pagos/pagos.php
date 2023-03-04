<?php 
    namespace App\pagos;
    use Psr\Http\Message\ResponseInterface as Response;
    use Psr\Http\Message\ServerRequestInterface as Request;
    use Psr\Container\ContainerInterface;
    use Psr\Http\Message\UploadedFileInterface;
    class pagos {
        private $db;
        private $firebird;
        public function __construct(ContainerInterface $container) {
            $this->db = $container->get('db');
            //$this->firebird = $container->get('db2');
        }
        public function listarClientes(Request $request,Response $response): Response{
            $clientes=$this->db->select("SELECT * FROM clientes ORDER BY idcliente ASC;");
            $response->getBody()->write(json_encode($clientes));
            return $response;
        }
        public function actualizarAntiguos(Request $request,Response $response, $args): Response{
            $whereArray = array();
            if(isset($args['idusuario']) AND !is_null($args['idusuario']) AND $args['idusuario'] != 0){
                array_push($whereArray,"(usuario = '".$args['idusuario']."' OR idcliente = ".$args['idusuario'].")");
            }
            if(isset($args['fechaini']) AND !is_null($args['fechaini']) AND $args['fechaini']!=0){
                if(isset($args['fechafin']) AND !is_null($args['fechafin']) AND $args['fechafin']!=0){
                    array_push($whereArray,"(DATE(fechainipago) BETWEEN DATE('".$args['fechaini']."') AND DATE('".$args['fechafin']."'))");
                }else{
                    array_push($whereArray,"DATE(fechainipago) = DATE('".$args['fechaini']."')");
                } 
            }
            if(!isset($args['forzar']) OR $args['forzar'] == 0){
                array_push($whereArray, "(estado_pago != 1 OR estado_pago IS NULL)");
            }
            $where = '';
            if(sizeof($whereArray)>0){
            $where = implode(' AND ',$whereArray);
            }
            if(!empty($where)){
                $where = ' WHERE '.$where;
            }
            $query = "SELECT idcliente FROM clientes ".$where." ORDER BY iter_act ASC LIMIT 15;";
            $clientes=$this->db->select($query);
            var_dump($query);
            //return $response;
            $rta =array('respuesta' => array(), 'procesados' => 0);
            for ($i=0; $i < sizeof($clientes) ; $i++) { 
                $cli = $clientes[$i];
                //echo $cli['idcliente'].'</br>';
                var_dump($cli['idcliente']);
                $respPago = $this->consultaResultadoPago(array('id_comercio'=>30208,'id_pago'=>$cli['idcliente']),false);
                var_dump($respPago);
                array_push($rta['respuesta'],$respPago);
                if($respPago == null){
                    echo "se interrumpe conexión por error de conexion a zonapagos<br>";
                    break;
                }
                var_dump('------------------------------------');
                $rta['procesados']++;
            }
            //$rta = $procesados;
            $response->getBody()->write(json_encode($rta['procesados']));  
            return $response;
        }
        public function verificarPago(Request $request,Response $response, $args): Response{
            $verificacionPago = $this->consultarPago($args['idpago'],true);
            $response->getBody()->write(json_encode($verificacionPago));
            return $response;
        }
        private function consultarPago($idpago,$error = false){
            $verificaPagoData=array(
                'int_id_comercio'=>30208,
                'str_usr_comercio'=>'Aguasbarranca',
                'str_pwd_Comercio'=>'Aguasbarranca30208.',
                'str_id_pago'=>$idpago,
                'int_no_pago'=>-1
            );
            try {
                $verificacionPago =\Httpful\Request::post('https://www.zonapagos.com/Apis_CicloPago/api/VerificacionPago')->sendsJson()->body($verificaPagoData)->send();
                $verificacionPago = json_decode($verificacionPago, true);
                $deterror=null;
            } catch (\Throwable $th) {
                $deterror = $th->getMessage();
                if($error == true){
                    $verificacionPago = $deterror;
                }else{
                    $verificacionPago = null;
                }
            }
            return $verificacionPago;
        }
        public function verificarPendiente(Request $request,Response $response): Response{
            $params=$request->getParsedBody();
            $clientes= $this->db->select("SELECT * FROM clientes WHERE (DATE(fechainipago) BETWEEN DATE(NOW()) - '5 days'::INTERVAL AND DATE(NOW())) AND nfactura='".$params['nfactura']."' ORDER BY iter_act DESC LIMIT 15;");
            //$clientes= $this->db->select("SELECT * FROM clientes WHERE (DATE(fechainipago) BETWEEN DATE(NOW()) - '5 days'::INTERVAL AND DATE(NOW())) AND nfactura='".$params['nfactura']."' AND pago_terminado != 1 ORDER BY fechainipago DESC LIMIT 15;");
            //$clientes= $this->db->select("SELECT * FROM clientes WHERE (DATE(fechainipago) BETWEEN DATE(NOW()) - '3 days'::INTERVAL AND DATE(NOW())) AND nfactura='".$params['nfactura']."' AND (estado_pago IN (1,4001,999,200,9999) OR estado_pago IS NULL) ORDER BY fechainipago DESC LIMIT 10;");
            if(sizeof($clientes)>0){
                $dataConsultaResultadoPago=array(
                    'idcomercio'=>30208,
                    'id_pago'=>$clientes[0]['idcliente']
                );
                $dataConsulta=$this->consultaResultadoPago($dataConsultaResultadoPago,false);
                if(isset($dataConsulta['estado_pago'])){
                    $telefono='<b>3502118839 - 018000413787 Opción 1</b>';
                    $email='<b>recaudos@aguasdebarrancabermeja.gov.co</b>';
                    switch ($dataConsulta['estado_pago']) {
                        case 4001:
                            $retorno=array('continuar'=>false,'mensaje'=>true,'header'=>'PAGO EN VERIFICACION','message'=>'En este momento su Número de Referencia o Factura <b>'.$params['nfactura'].'</b> presenta un proceso de pago cuya transacción se encuentra <b>PENDIENTE</b> de recibir confirmación por parte de su entidad financiera, por favor espere unos minutos y vuelva a consultar más tarde para verificar si su pago fue confirmado de forma exitosa. Si desea mayor información sobre el estado actual de su operación puede comunicarse a nuestras líneas de atención al cliente '.$telefono.' o enviar un correo electrónico a '.$email);
                        break;
                        case 200:
                            $retorno=array('continuar'=>false,'mensaje'=>true,'header'=>'PAGO EN PROCESO','message'=>'Ya existe un pago en proceso, espere unos minutos y vuelva a intentar mas tarde.');
                        break;
                        case 999:
                            $retorno=array('continuar'=>false,'mensaje'=>true,'header'=>'PAGO EN VERIFICACION', 'message'=>'En este momento su Factura <b>'.$params['nfactura'].'</b> presenta un proceso de pago cuya transacción se encuentra <b>PENDIENTE</b> de recibir confirmación por parte de su entidad financiera, por favor espere unos minutos y vuelva a consultar más tarde para verificar si su pago fue confirmado de forma exitosa. Si desea mayor información sobre el estado actual de su operación puede comunicarse a nuestras líneas de atención al cliente '.$telefono.'  o enviar un correo electrónico a '.$email.'  y preguntar por el estado de la transacción: <b>'.$dataConsulta['cod_transaccion'].'</b>');
                        break;
                        case 1:
                            $retorno=array('continuar'=>false,'mensaje'=>true,'header'=>'FACTURA PAGADA','message'=>'La factura <b>'.$params['nfactura'].'</b> ya cuenta con un pago registrado.');
                        break;
                        default:
                            $retorno=array('continuar'=>true);
                        break;
                    }
                }elseif($dataConsulta==null){
                    $retorno=array('continuar'=>false,'message'=>true,'header'=>'ERROR','El servidor de pagos no responde.');
                }else{
                    //$retorno=array('continuar'=>true);
                    $retorno=array('continuar'=>false,'mensaje'=>true,'header'=>'PAGO EN VERIFICACION','message'=>'En este momento su Número de Referencia o Factura '.$params['nfactura'].' presenta un proceso de pago cuya transacción se encuentra PENDIENTE de recibir confirmación por parte de su entidad financiera, por favor espere unos minutos y vuelva a consultar más tarde para verificar si su pago fue confirmado de forma exitosa. Si desea mayor información sobre el estado actual de su operación puede comunicarse a nuestras líneas de atención al cliente '.$telefono.' o enviar un correo electrónico a '.$email);

                }
            }else{
                //continua con el proceso
                $retorno=array('continuar'=>true);
            }
            $response->getBody()->write(json_encode($retorno));
            return $response;
        }
        public function iniciarPago(Request $request,Response $response): Response{
            $params=$request->getParsedBody();
            $data = json_decode($params['data'],true);
            try {
                $respuesta = \Httpful\Request::post($params['url'])->sendsJson()->body($params['data'])->send();
                $respuesta = json_decode($respuesta, true);
                $actualizado = $this->db->update("UPDATE clientes SET str_url = '".$respuesta['str_url']."' WHERE idcliente = ".$data['InformacionPago']['str_id_pago'].";");
            } catch (\Throwable $th) {
                //throw $th;
                $respuesta=null;
            }
            $response->getBody()->write(json_encode($respuesta));
            return $response;
        }
        public function titular(Request $request,Response $response): Response{
            $params=$request->getParsedBody();
            $titular=$this->db->select("SELECT * FROM titulares WHERE usuario='".$params['usuario']."';");
            $response->getBody()->write(json_encode($titular));
            return $response;
        }
        public function actualizar(Request $request,Response $response): Response{
            $params=$request->getParsedBody();
            $cliente=$this->db->procedure('pu_clientes',array_values($params));
            if($cliente['result']>0){
                $response->getBody()->write(json_encode(array('success'=>true,'severity'=>'success','summary'=>'EXITO','detail'=>'Cliente Insertado Correctamente.','result'=>$cliente['result'])));
            }else{
                $response->getBody()->write(json_encode($cliente));  
            }
            return $response;
        }
        public function guardar(Request $request,Response $response): Response{
            $params=$request->getParsedBody();
            $cliente=$this->db->procedure('pi_clientes',array_values($params));
            if($cliente['result']>0){
                $response->getBody()->write(json_encode(array('success'=>true,'severity'=>'success','summary'=>'EXITO','detail'=>'Cliente Insertado Correctamente.','result'=>$cliente['result'])));
            }else{
                $response->getBody()->write(json_encode($cliente));  
            }
            return $response;
        }
        public function tipoDocumento(Request $request,Response $response): Response{
            $params=$request->getParsedBody();
            $clientes=$this->db->select("SELECT idtnit AS value, tipodoc AS label FROM tipodoc;");
            $response->getBody()->write(json_encode($clientes));
            return $response;
        }
        public function explodeUrl(Request $request,Response $response, $args): Response{
            $response->getBody()->write(json_encode(explode('|','| 3772 | | 200 | 1002 | | 12500 | | | 123456789 | Cristina | Vargas | 319632555648 | soporte9@zonavirtual.com | opcion 11 | opcion 12 | opcion 13 | | | | | ; 31 | 3773 | 1 | 1 | 1 | 12500 | 12500 | 13 | camisa | 123456789 | Cristina | Vargas | 319632555648 | soporte9@zonavirtual.com | opcion 11 | opcion 12 | opcion 13 | | | 9/11/2018 12:58:41 PM | 29 | 18092100031 | 2701 | 1022 | BANCO UNION COLOMBIANO | 1468228 | 3 | ;')));  
            return $response;
        }
        public function actualizarPagosPendientes(Request $request,Response $response, $args): Response{
            $clientes=$this->db->select("SELECT idcliente FROM clientes WHERE (DATE(fechainipago) BETWEEN DATE(NOW()) - '5 days'::INTERVAL AND DATE(NOW())) AND (estado_pago != 1 OR estado_pago IS NULL) ORDER BY iter_act ASC LIMIT 15;");
            //$clientes=$this->db->select("SELECT idcliente FROM clientes WHERE (DATE(fechainipago) BETWEEN DATE(NOW()) - '5 days'::INTERVAL AND DATE(NOW()))  AND pago_terminado != 1 ORDER BY iter_act ASC LIMIT 15;");
            //$clientes=$this->db->select("SELECT idcliente FROM clientes WHERE (DATE(fechainipago) BETWEEN DATE(NOW()) - '3 days'::INTERVAL AND DATE(NOW()))  AND (estado_pago IN(4001,200,999, 9999) OR estado_pago IS NULL) ORDER BY iter_act ASC LIMIT 15;");
            $procesados = 0;
            $rta = array('respuestas' => array(),'procesados' => 0);
            for ($i=0; $i < sizeof($clientes) ; $i++) { 
                $cli = $clientes[$i];
                var_dump($cli['idcliente']);
                //echo $cli['idcliente'].'<br>';
                $respPago = $this->consultaResultadoPago(array('id_comercio'=>30208,'id_pago'=>$cli['idcliente']),false);
                array_push($rta['respuestas'], $respPago);
                var_dump($respPago);
                if($respPago == null){
                    echo "se interrumpe conexión por error de conexion a zonapagos<br>";
                    break;
                }
                $rta['procesados']++;
                var_dump('--------------------------------------');
            }
            $response->getBody()->write(json_encode($rta['procesados']));  
            return $response;
        }
        private function consultaResultadoPago(array $params, bool $dev = false){
            $verificacionPago = $this->consultarPago($params['id_pago'],false);
            $iteracion = $this->db->update("UPDATE clientes SET iter_act = iter_act + 1, fecha_iter=NOW() WHERE idcliente = ".$params['id_pago'].";");
            //return $verificacionPago;
            if($verificacionPago == null){
                $errorData=array(
                    'idcliente'=>$params['id_pago'],
                    'det_error_verif_pago'=>'RESPUESTA NULL - '.$deterror,
                    'est_verif_pago'=>-1,
                    'error_verif_pago'=>null
                );
                $error=$this->db->procedure('pu_error_clientes',array_values($errorData));
                if($error['result']==-1){
                    var_dump($error);
                }
                return null;
            }elseif($verificacionPago['int_estado']==1 AND $verificacionPago['int_error']==0){
                //exito
                //$verificacionPago['str_res_pago']=trim($verificacionPago['str_res_pago']);
                $pagos = explode(';',substr($verificacionPago['str_res_pago'], 0));
                foreach($pagos AS $res_pago){
                    if(!empty($res_pago)){
                        $exp_res_pago = explode('|', substr($res_pago, 0));
                        foreach($exp_res_pago as &$valor) {
                            // Ser reformatean los registro del aray para retirar caracteres ocultos. (+)
                            $valor = trim(str_replace('+', '', $valor));
                        }
                        unset($valor);
                        $time_fecha_pago = strtotime($exp_res_pago[19]);
                        $fecha_pago=date('Y-m-d H:i:s',$time_fecha_pago);
                        $res_pago=array(
                            'idcliente'=>trim($params['id_pago']),
                            'data_zonapagos'=>trim($verificacionPago['str_res_pago']),
                            'ped_numero'=>intval($exp_res_pago[0]),//asignado y devuelto por zonapagos
                            'n_pago'=>(!is_numeric($exp_res_pago[1])?0:intval($exp_res_pago[1])),
                            'pago_parcial'=>intval($exp_res_pago[2]),
                            'pago_terminado'=>intval($exp_res_pago[3]),
                            'estado_pago'=>intval($exp_res_pago[4]),
                            'valor_pagado'=>(is_numeric($exp_res_pago[5])?floatval($exp_res_pago[5]):0),
                            'fechafinpago'=>date('Y-m-d H:i:s',strtotime(str_replace('/', '-' ,$exp_res_pago[19]))), 
                            'id_forma_pago'=>intval($exp_res_pago[20]),
                            'sincronizado'=>false
                        );
                        $exp_res_pago_adic=array_slice($exp_res_pago,21);
                        switch ($res_pago['id_forma_pago']) {
                            case 29:
                                //PSE
                                $resp_pago_adic=array(
                                    'codigo_banco'=>intval($exp_res_pago_adic[2]),
                                    'nombre_banco'=>$exp_res_pago_adic[3],
                                    'numero_tarjeta'=>null,
                                    'franquicia'=>null,
                                    'cod_transaccion'=>$exp_res_pago_adic[4],
                                    'ciclo_transaccion'=>intval($exp_res_pago_adic[5]),
                                    'cod_aprobacion'=>null 
                                );
                            break;
                            case 32:
                                //TARJETA CREDITO
                                $resp_pago_adic=array(
                                    'codigo_banco'=>null,
                                    'nombre_banco'=>null,
                                    'numero_tarjeta'=>intval($exp_res_pago_adic[1]),
                                    'franquicia'=>$exp_res_pago_adic[2],
                                    'cod_transaccion'=>null,
                                    'ciclo_transaccion'=>null,
                                    'cod_aprobacion'=>$exp_res_pago_adic[3]
                                );
                            break;
                            default:
                                $resp_pago_adic=array(
                                    'codigo_banco'=>null,
                                    'nombre_banco'=>null,
                                    'numero_tarjeta'=>null,
                                    'franquicia'=>null,
                                    'cod_transaccion'=>null,
                                    'ciclo_transaccion'=>null,
                                    'cod_aprobacion'=>null
                                );
                            break;
                        }
                        $ProcData=array_merge($res_pago,$resp_pago_adic);
                        if($ProcData['estado_pago']==1){
                            //revisamos si el pago esta terminado y el estado es ok.
                            $clientes=$this->db->select("SELECT * FROM clientes WHERE idcliente=".$params['id_pago']);
                            ///obtenemos el nfactura de aguas.
                            if(sizeof($clientes)>0){
                                $cliente=$clientes[0];
                                $ws_params=array(
                                    'factura'=>$cliente['nfactura'],
                                    'valorpago'=>$ProcData['valor_pagado'],
                                    'fechapago'=>date('m/d/Y H:i',strtotime($ProcData['fechafinpago'])),
                                    'entidadrecaudo'=>16,
                                    'estado'=>0,
                                    'formapago'=>0,
                                    'usuario'=>'sislogarec',
                                    'password'=>'PS*eym3457oT',
                                    'jornada'=>1,
                                );
                                //enviamos actualizacion del estado la factura a aguas.
                                try {
                                    $registroRecaudo=json_decode(\Httpful\Request::post('https://www.aguasdebarrancabermeja.app/rest/public/index.php/sisloga/grabarecaudo')->sendsJson()->body(json_encode($ws_params))->send(),true);
                                    $ProcData['sincronizado'] = true;
                                } catch (\Throwable $th) {
                                    var_dump('ERROR ACTUALIZACION ==>');
                                    //var_dump($th);
                                    $registroRecaudo = null;
                                }
                            }else{
                            //echo 'no se encuentra cliente.';
                            }
                        }else{
                            //echo 'pago rechazado o incompleto';
                        }
                        break;
                    }
                    $cliente=$this->db->procedure('pu_clientes',array_values($ProcData));
                    if($cliente['result'] == -1){
                        var_dump($cliente);
                    }
                }
                return $ProcData;
            }else{
                //error
                $errorData=array(
                    'idcliente'=>$params['id_pago'],
                    'det_error_verif_pago'=>$verificacionPago['str_detalle'],
                    'est_verif_pago'=>$verificacionPago['int_estado'],
                    'error_verif_pago'=>$verificacionPago['int_error'],
                );
                $error=$this->db->procedure('pu_error_clientes',array_values($errorData));
                if($error['result']==-1){
                    var_dump($error);
                }
                return $error;
            }
        }
        public function resultadoPago(Request $request,Response $response, $args): Response{
            $params=array(
                'id_comercio'=>intval($_GET['id_comercio']),
                'id_pago'=>intval($_GET['id_pago'])
            );
            $this->consultaResultadoPago($params,false);
            $response->getBody()->write(json_encode($params));  
            return $response;
        }
    }
    ?>

