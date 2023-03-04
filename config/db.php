<?php
const ERRORES=array(
    '42P01'=>array('success'=>false,'result'=>-1,'icon'=>'pi pi-exclamation-triangle','header'=>'ERROR 42P01 - TABLA NO EXISTE','message'=>'La tabla a la que intenta acceder no existe, Comuniquese con soporte'),
    '42601'=>array('success'=>false,'result'=>-1,'icon'=>'pi pi-exclamation-triangle','header'=>'ERROR 42601 - ERROR SINTAXIS','message'=>'Error de sintaxis en la consulta, Comuniquese con soporte'),
    '42703'=>array('success'=>false,'result'=>-1,'icon'=>'pi pi-exclamation-triangle','header'=>'ERROR 42703 - COLUMNA NO EXISTE','message'=>'Error en Base de datos, Columna invocada no existe.'),
    '23505'=>array('success'=>false,'result'=>-1,'icon'=>'pi pi-exclamation-triangle','header'=>'ERROR 42703 - ERROR CAMPO UNICO','message'=>null),
);
class db{
    private $options=[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
    ];
    private $db;
    function __construct($settings){
       $this->db=new PDO($settings['DB_DSN'].":host=" . $settings['DB_HOST'] . ";dbname=" . $settings['DB_NAME'].";port=".$settings['DB_PORT'], $settings['DB_USER'], $settings['DB_PASSWORD'],$this->options);
    }
    private function procesarError($except){
        if(isset(ERRORES[$except->getCode()])){
            //en este swith se van a manejar las opciones especiales de algunos errores.
            $response=ERRORES[$except->getCode()];
            $response['response']=$except;
            switch ($except->getCode()) {
                case '23505':
                    //error llave unica, se intenta sacar el text de la llave unica que se esta duplicando.
                    try {
                        $errorText=$except->getMessage();
                        $explodedText=explode('DETAIL:  ',$errorText);
                        $errorText=$explodedText[1];
                        $explodedText=explode('.',$errorText);
                        $response['message']=strtoupper($explodedText[0]);
                    } catch (\Throwable $th) {
                        $response['message']=$except->getMessage();
                    }
                break;
                default:

                break;
            }
            
        }else{
            $response=array('success'=>false,'result'=>-1,'icon'=>'pi pi-exclamation-triangle','header'=>'ERROR - '.$except->getCode(),'message'=>$except->getMessage(),'response'=>$except);
        }
        return $response;
    }
    public function select($query){
        try {
            $statement=$this->db->prepare($query);
            $statement->execute();
            return $statement->fetchAll();
		} catch (PDOException $e) {
            return $this->procesarError($e);
		} 
    }
    public function procedure(String $procedure,array $values,bool $skipResult=false){
        try {
            if($skipResult==false){
                array_push($values,null);//agrega el ultimo valor que será el result
            }
            $queryParams=[];
            foreach ($values as $val) {
                $queryParams[]='?';
            }
            $statement=$this->db->prepare("CALL ".$procedure."(".implode(',',$queryParams).")");
            for ($i=0,$x=1; $i < sizeof($values); $i++,$x++) { 
                if($values[$i]==='null'){
                    $values[$i]=null;
                }
                $statement->bindParam($x, $values[$i], \PDO::PARAM_STR|\PDO::PARAM_INPUT_OUTPUT, 2000);
            }
            $statement->execute();
            return $statement->fetch();
		} catch (PDOException $e) {

            return $this->procesarError($e);
		} 
    }
    public function update($query){
        try {
            $statement=$this->db->prepare($query);
            $statement->execute();
            return $statement->rowCount();
		} catch (PDOException $e) {
            //var_dump($e);
            return $this->procesarError($e);
		} 
    }
    public function delete($query){
        try {
            $statement=$this->db->prepare($query);
            $statement->execute();
            return $statement->rowCount();
		} catch (PDOException $e) {
            return $this->procesarError($e);
		} 
    }
    public function insert($query){
        try {
            $statement=$this->db->prepare($query);
            $statement->execute();
            return $this->db->lastInsertId();
		} catch (PDOException $e) {
            return $this->procesarError($e);
        }
    }
}
?>