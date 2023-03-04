<?php 
namespace App\plantilla;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\UploadedFileInterface; 
 class usuarios  {
    private $db;
    public function __construct(ContainerInterface $container) {
        $this->db = $container->get('db');
    }
    public function perfiles(Request $request,Response $response): Response{
        $params=$request->getParsedBody();
        $permisos=$this->db->select("SELECT idgruposus AS value, grupogu AS label, idgruposus, grupogu FROM gruposus ORDER BY grupogu ASC;");
        $response->getBody()->write(json_encode($permisos));
        return $response;
    }
    public function listar(Request $request,Response $response): Response{
        $params=$request->getParsedBody();
        $usuarios=$this->db->select("SELECT * FROM ususys ORDER BY usuario ASC;");
        $response->getBody()->write(json_encode($usuarios));
        return $response;
    }
    public function eliminar(Request $request,Response $response): Response{
        $params=$request->getParsedBody();
        $eliminar=$this->db->procedure('pd_ususys',array_values($params));
        if($eliminar['result'] > 0){
            $response->getBody()->write(json_encode(array('success'=>true,'severity'=>'success','summary'=>'EXITO','detail'=>'Usuario Eliminado Correctamente.','result'=>$eliminar['result'])));
        }else{
            $response->getBody()->write(json_encode($eliminar)); 
        }
        return $response;
    }
    public function guardar(Request $request,Response $response): Response{
        $params=$request->getParsedBody();
        $params['clave'] = (!empty($params['clave']) ? password_hash(trim($params['clave']), PASSWORD_BCRYPT) : null);
        $actualizar=$this->db->procedure('piu_ususys',array_values($params));
        if($params['idusr'] == null AND $actualizar['result'] > 0){
            //insert
            $response->getBody()->write(json_encode(array('success'=>true,'severity'=>'success','summary'=>'EXITO','detail'=>'Observación de ejecución insertado correctamente.','result'=>$actualizar['result'])));

        }elseif( $params['idusr'] != null AND $actualizar['result'] > 0 ){
            //update
            $response->getBody()->write(json_encode(array('success'=>true,'severity'=>'success','summary'=>'EXITO','detail'=>'Observación de ejecución actualizada correctamente.','result'=>$actualizar['result'])));

        }else{
            //error
            $response->getBody()->write(json_encode($actualizar));  
        }
        return $response;
    }
    public function actualizarPerfil(Request $request,Response $response): Response{
        $params=$request->getParsedBody();
        $updateFoto=null;
        if($params['foto']!=null){
            $updateFoto=",foto='".$params['foto']."'";
        }
        $query="UPDATE ususys SET nombres=UPPER('".$params['nombres']."'), apellidos=UPPER('".$params['apellidos']."'), fijo='".$params['fijo']."', celpersonal='".$params['celpersonal']."', cellaboral='".$params['cellaboral']."', email='".$params['email']."', fechaact=NOW(), genero='".$params['genero']."'".$updateFoto." WHERE idusr=".$params['idusr'].";";
        $actualizacion=$this->db->update($query);
        if($actualizacion==1){
            $response->getBody()->write(json_encode(array('success'=>true,'severity'=>'success','summary'=>'EXITO','detail'=>'Usuario actualizado correctamente.','response'=>$actualizacion)));
        }else{
            $response->getBody()->write(json_encode($actualizacion));
        }
        return $response;
    }
    public function login(Request $request,Response $response): Response{
        $params=$request->getParsedBody();
        $usuario=$this->db->select("SELECT us.*, gs.grupogu AS perfil FROM ususys us JOIN gruposus gs ON gs.idgruposus=us.grupo WHERE UPPER(us.usuario)='".strtoupper($params['usuario'])."';");
        if(sizeof($usuario)>0){
            $usuario=$usuario[0];
            //se verifica si el usuario esta activo.
            if($usuario['inactivo']==0){
                //usuario existe y se verifica contraseña.
                if (password_verify($params['clave'],$usuario['clave'])) {
                    //logea.
                    $response->getBody()->write(json_encode(array('success'=>true,'usuario'=>$usuario,'severity'=>'success','summary'=>'BIENVENIDO','detail'=>'Hola '.$usuario['nombres'].'!')));
                }else{
                    //contraseña erronea.
                    $response->getBody()->write(json_encode(array('success'=>false,'severity'=>'error','summary'=>'Error','detail'=>'Contraseña Incorrecta!')));
                }
            }else{
                //usuario inactivo.
                $response->getBody()->write(json_encode(array('success'=>false,'severity'=>'error','summary'=>'Error','detail'=>'Tu usuario se encuentra inactivo, comuniquese con el administrador')));
            }
        }else{
            //usuario no existe.
            switch ($params['tipo']) {
                case 'U':
                    $response->getBody()->write(json_encode(array('success'=>false,'severity'=>'error','summary'=>'Error','detail'=>'Usuario no registrado')));
                break;
                case 'C':
                    $response->getBody()->write(json_encode(array('success'=>false,'severity'=>'error','summary'=>'Error','detail'=>'Código no registrado')));
                break;
            }
        }
        return $response;
    }
    public function modulos(Request $request,Response $response): Response{
        $params=$request->getParsedBody();
        $permisos=$this->db->select("SELECT menu.*,perm.permisos FROM optmenu menu JOIN permisosgu perm ON perm.menu=menu.idoptmenu WHERE perm.grupogu=".$params['grupo']." AND item NOT LIKE '%.%' ORDER BY item ASC;");
        $response->getBody()->write(json_encode($permisos));
        return $response;
    }
    public function permisos(Request $request,Response $response): Response{
        $params=$request->getParsedBody();
        $query = "SELECT menu.*,perm.permisos FROM optmenu menu JOIN permisosgu perm ON perm.menu=menu.idoptmenu WHERE perm.grupogu=".$params['grupo']." AND menu.item LIKE '".$params['modulo'].".%' ORDER BY SUBSTR(menu.item,LENGTH('".$params['modulo']."')+2)::real ASC;";
        $permisos=$this->db->select($query);
        $response->getBody()->write(json_encode($permisos));
        return $response;
    }
}
?>

