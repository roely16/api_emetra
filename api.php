<?php  

    error_reporting(E_ERROR | E_PARSE);

    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
    header("Allow: GET, POST, OPTIONS, PUT, DELETE");

    class Api extends Rest{

        public $dbConn;

		public function __construct(){

			parent::__construct();

			$db = new Db();
			$this->dbConn = $db->connect();

        }

        // Registrar la boleta
        public function registrar_boleta(){

            $boleta =  (object) $this->param['boleta'];
            $infracciones = $this->param['infracciones'];

            try {
            
                // Registrar la boleta
                $query = "  INSERT INTO EMT_BOLETA (SERIE_BOLETA,NUM_BOLETA, TIPO_PLACA, NUM_PLACA, NUM_CIRCULACION, TIPO_VEHICULO, MARCA, COLOR, APELLIDOS, NOMBRE, NUM_LICENCIA, CLASE_LICENCIA, LUGAR_REMISION, HORAREMISION, FECHA_REMISION, COD_CHAPA, FECHA_USUARIO, NUM_DELEGACION, CONDUCTOR_AUSENTE, CONDUCTOR_NOFIRMA, OTROS) VALUES ('$boleta->SERIE_BOLETA', '$boleta->NUM_BOLETA', '$boleta->TIPO_PLACA', '$boleta->NUM_PLACA', '$boleta->NUM_CIRCULACION', '$boleta->TIPO_VEHICULO', '$boleta->MARCA', '$boleta->COLOR', '$boleta->APELLIDOS', '$boleta->NOMBRE', '$boleta->NUM_LICENCIA', '$boleta->CLASE_LICENCIA', '$boleta->LUGAR_REMISION', '$boleta->HORAREMISION', TO_DATE('$boleta->FECHA_REMISION', 'DD/MM/YYYY'), '$boleta->COD_CHAPA', SYSDATE, '$boleta->NUM_DELEGACION', '$boleta->CONDUCTOR_AUSENTE', '$boleta->CONDUCTOR_NOFIRMA', '$boleta->OTROS')";

                $stid = oci_parse($this->dbConn, $query);
                // oci_execute($stid);

                if (false === oci_execute($stid)) {

					$err = oci_error($stid);

					$str_error = "Se ha encontrado un problema al registrar la boleta.";

					$this->throwError($err["code"], $str_error);

                }

                // Registrar la dirección
                $query = "  INSERT INTO EMT_DIRECCION (NUM_BOLETA, TIPO_PLACA, NUM_PLACA, DIRECCION)
                            VALUES ('$boleta->NUM_BOLETA', '$boleta->TIPO_PLACA', '$boleta->NUM_PLACA', '$boleta->DIRECCION')";

                $stid = oci_parse($this->dbConn, $query);
                oci_execute($stid);

                // Registrar las infracciones
                foreach ($infracciones as $infraccion) {
                    
                    $infraccion = (object) $infraccion;

                    $query = "  INSERT INTO EMT_INFRACCION_X_BOLETA 
                            (ID_INFRACCION, CLASE_INFRACCION, NUM_BOLETA, TIPO_PLACA, NUM_PLACA, VALOR_INFRACCION) 
                            VALUES ('$infraccion->ID_INFRACCION', '$infraccion->CLASE_INFRACCION', '$boleta->NUM_BOLETA', '$boleta->TIPO_PLACA', '$boleta->NUM_PLACA', $infraccion->MONTO)";

                    $stid = oci_parse($this->dbConn, $query);
                    // oci_execute($stid);

                    if (false === oci_execute($stid)) {

                        $err = oci_error($stid);
    
                        $str_error = "Se ha encontrado un problema al registrar la infracción.";
    
                        $this->throwError($err["code"], $str_error);
    
                    }

                }

                
            } catch (\Throwable $th) {
               

            }

            $this->returnResponse(SUCCESS_RESPONSE, $infracciones);

        }

        public function obtener_boletas(){

            try {
                
                $data = [];

                $query = "  SELECT * FROM EMT_BOLETA
                            ORDER BY FECHA_USUARIO DESC";

                $stid = oci_parse($this->dbConn, $query);
                oci_execute($stid);

                $boletas = [];

                while ($row = oci_fetch_array($stid, OCI_ASSOC)) {
                    
                    $boletas [] = $row;

                }

                $data["items"] = $boletas;
                $data["headers"] = [

                    [
                        "text" => "No. Boleta",
                        "value" => "NUM_BOLETA",
                        "sortable" => false,
                        "width" => "10%"
                    ],
                    [
                        "text" => "No. Placa",
                        "value" => "NUM_PLACA",
                        "sortable" => false,
                        "width" => "15%"
                    ],
                    [
                        "text" => "Tipo",
                        "value" => "TIPO_VEHICULO",
                        "sortable" => false,
                        "width" => "10%"
                    ],
                    [
                        "text" => "Marca",
                        "value" => "MARCA",
                        "sortable" => false,
                        "width" => "10%"
                    ],
                    [
                        "text" => "Color",
                        "value" => "COLOR",
                        "sortable" => false,
                        "width" => "10%"
                    ],
                    [
                        "text" => "Conductor",
                        "value" => "CONDUCTOR",
                        "sortable" => false,
                        "width" => "35%"
                    ],
                    [
                        "text" => "Acciones",
                        "value" => "ACTIONS",
                        "sortable" => false,
                        "width" => "10%",
                        "align" => 'right'
                    ],


                ];

                $this->returnResponse(SUCCESS_RESPONSE, $data);

            } catch (\Throwable $th) {
                //throw $th;
            }

        }

        public function detalle_boleta(){

            $boleta =  $this->param['boleta'];

            try {
                
                $query = "  SELECT NUM_BOLETA, NUM_CIRCULACION, TIPO_PLACA, NUM_PLACA, NUM_DELEGACION, COD_MOT_RECHAZO, TIPO_VEHICULO, 
                            MARCA, COLOR, APELLIDOS, NOMBRE, NUM_LICENCIA, CLASE_LICENCIA, LUGAR_REMISION, TO_CHAR(FECHA_REMISION, 'YYYY-MM-DD') AS FECHA_REMISION, HORAREMISION, FECHA_PLAZO, COD_CHAPA, CONDUCTOR_AUSENTE, CONDUCTOR_NOFIRMA, NOTIFICADA, FECHA_NOTIFICACION, USUARIO, TO_CHAR(FECHA_USUARIO, 'DD/MM/YYYY HH24:MI:SS') AS FECHA_USUARIO, SERIE_BOLETA, OTROS
                            FROM EMT_BOLETA
                            WHERE NUM_BOLETA = '$boleta'";

                $stid = oci_parse($this->dbConn, $query);
                oci_execute($stid);

                $detalle_boleta = oci_fetch_array($stid, OCI_ASSOC);

                // Obtener la dirección

                $query = "  SELECT *
                            FROM EMT_DIRECCION
                            WHERE NUM_BOLETA = '$boleta'";

                $stid = oci_parse($this->dbConn, $query);
                oci_execute($stid);

                $direccion = oci_fetch_array($stid, OCI_ASSOC);

                $detalle_boleta["DIRECCION"] = $direccion["DIRECCION"];

                // Infracciones
                $query = "  SELECT T2.*, T1.ID_NUM_REGISTRO, T1.VALOR_INTERES, T1.VALOR_PAGO 
                            FROM EMT_INFRACCION_X_BOLETA T1
                            INNER JOIN EMT_INFRACCION T2
                            ON T1.ID_INFRACCION = T2.ID_INFRACCION
                            WHERE NUM_BOLETA = '$boleta'";

                $stid = oci_parse($this->dbConn, $query);
                oci_execute($stid);

                $infracciones = [];

                while ($data = oci_fetch_array($stid, OCI_ASSOC)) {
                    
                    $infracciones [] = $data;

                }

                $detalle_boleta["INFRACCIONES"] = $infracciones;

                // Obtener las delegaciones
                $query = "  SELECT *
                            FROM EMT_DELEGACION";

                $stid = oci_parse($this->dbConn, $query);
                oci_execute($stid);

                $delegaciones = [];

                while ($row = oci_fetch_array($stid, OCI_ASSOC)) {
                    
                    $delegaciones [] = $row;

                }

                $detalle_boleta["DELEGACIONES"] = $delegaciones;

            } catch (\Throwable $th) {
                //throw $th;
            }

            $this->returnResponse(SUCCESS_RESPONSE, $detalle_boleta);

        }

        public function editar_boleta(){

            $boleta =  (object) $this->param['boleta'];
            $infracciones = $this->param['infracciones'];

            try {
                
                $query = "  UPDATE EMT_BOLETA SET 
                            TIPO_PLACA = '$boleta->TIPO_PLACA',
                            NUM_PLACA = '$boleta->NUM_PLACA',
                            NUM_CIRCULACION = '$boleta->NUM_CIRCULACION',
                            TIPO_VEHICULO = '$boleta->TIPO_VEHICULO',
                            MARCA = '$boleta->MARCA',
                            COLOR = '$boleta->COLOR',
                            APELLIDOS = '$boleta->APELLIDOS',
                            NOMBRE = '$boleta->NOMBRE',
                            NUM_LICENCIA = '$boleta->NUM_LICENCIA',
                            CLASE_LICENCIA = '$boleta->CLASE_LICENCIA',
                            LUGAR_REMISION = '$boleta->LUGAR_REMISION',
                            HORAREMISION = '$boleta->HORAREMISION',
                            FECHA_REMISION = TO_DATE('$boleta->FECHA_REMISION', 'DD/MM/YYYY'),
                            COD_CHAPA = '$boleta->COD_CHAPA',
                            CONDUCTOR_AUSENTE = '$boleta->CONDUCTOR_AUSENTE',
                            CONDUCTOR_NOFIRMA = '$boleta->CONDUCTOR_NOFIRMA',
                            NUM_DELEGACION = '$boleta->NUM_DELEGACION',
                            OTROS = '$boleta->OTROS'
                            WHERE NUM_BOLETA = '$boleta->NUM_BOLETA'";

                $stid = oci_parse($this->dbConn, $query);

                if (false === oci_execute($stid)) {

					$err = oci_error($stid);

					$str_error = "Error al actualizar";

					$this->throwError($err["code"], $str_error);

                }
                
                // Editar la Dirección
                $query = "  UPDATE EMT_DIRECCION SET DIRECCION = '$boleta->DIRECCION' WHERE NUM_BOLETA = '$boleta->NUM_BOLETA'";
                $stid = oci_parse($this->dbConn, $query);
                oci_execute($stid);

                // Editar las infracciones
                $query = "  DELETE FROM EMT_INFRACCION_X_BOLETA WHERE NUM_BOLETA = '$boleta->NUM_BOLETA'";
                $stid = oci_parse($this->dbConn, $query);
                oci_execute($stid);

                foreach ($infracciones as $infraccion) {
                    
                    $infraccion = (object) $infraccion;

                    $query = "  INSERT INTO EMT_INFRACCION_X_BOLETA 
                            (ID_INFRACCION, CLASE_INFRACCION, NUM_BOLETA, TIPO_PLACA, NUM_PLACA, VALOR_INFRACCION) 
                            VALUES ('$infraccion->ID_INFRACCION', '$infraccion->CLASE_INFRACCION', '$boleta->NUM_BOLETA', '$boleta->TIPO_PLACA', '$boleta->NUM_PLACA', $infraccion->MONTO)";

                    $stid = oci_parse($this->dbConn, $query);
                    // oci_execute($stid);

                    if (false === oci_execute($stid)) {

                        $err = oci_error($stid);
    
                        $str_error = "Se ha encontrado un problema al actualizar la infracción.";
    
                        $this->throwError($err["code"], $str_error);
    
                    }

                }
                
            } catch (\Throwable $th) {
                //throw $th;
            }

            $this->returnResponse(SUCCESS_RESPONSE, $boleta);

        }

        public function eliminar_boleta(){

            $boleta =  $this->param['boleta'];

            try {
                
                // Eliminar la dirección
                $query = "  DELETE 
                            FROM EMT_DIRECCION
                            WHERE NUM_BOLETA = '$boleta'";

                $stid = oci_parse($this->dbConn, $query);

                if (false === oci_execute($stid)) {

                    $err = oci_error($stid);

                    $str_error = "Se ha encontrado un problema al intentar eliminar la dirección.";

                    $this->throwError($err["code"], $str_error);

                }

                $query = "  DELETE 
                            FROM EMT_BOLETA 
                            WHERE NUM_BOLETA = '$boleta'";

                $stid = oci_parse($this->dbConn, $query);

                if (false === oci_execute($stid)) {

                    $err = oci_error($stid);

                    $str_error = "Se ha encontrado un problema al intentar eliminar la boleta.";

                    $this->throwError($err["code"], $str_error);

                }

            } catch (\Exception $th) {
                
            }

            $this->returnResponse(SUCCESS_RESPONSE, $boleta);

        }

        public function obtener_infracciones(){

            try {
                
                $query = "  SELECT *
                            FROM EMT_INFRACCION";

                $stid = oci_parse($this->dbConn, $query);
                oci_execute($stid);

                $infracciones = [];

                while ($row = oci_fetch_array($stid, OCI_ASSOC)) {
                    
                    $infracciones [] = $row;

                }



            } catch (\Throwable $th) {
                //throw $th;
            }

            $this->returnResponse(SUCCESS_RESPONSE, $infracciones);

        }

        public function obtener_delegaciones(){

            try {
                
                $query = "  SELECT *
                            FROM EMT_DELEGACION";

                $stid = oci_parse($this->dbConn, $query);
                oci_execute($stid);

                $delegaciones = [];

                while ($row = oci_fetch_array($stid, OCI_ASSOC)) {
                    
                    $delegaciones [] = $row;

                }

            } catch (\Throwable $th) {
                //throw $th;
            }

            $this->returnResponse(SUCCESS_RESPONSE, $delegaciones);

        }


        // Notificaciones
        public function obtener_rechazos(){

            try {
                
                $query = "  SELECT *
                            FROM EMT_MOT_RECHAZO";

                $stid = oci_parse($this->dbConn, $query);
                oci_execute($stid);

                $rechazos = [];

                while ($row = oci_fetch_array($stid, OCI_ASSOC)) {
                    
                    $rechazos [] = $row;

                }

            } catch (\Throwable $th) {
                //throw $th;
            }

            $this->returnResponse(SUCCESS_RESPONSE, $rechazos);

        }

        public function actualizar_notificacion(){

            $boleta = $this->param['boleta'];
            $notificada = $this->param['notificada'];
            $motivo_rechazo = $this->param['motivo_rechazo'];
            $fecha_notificacion = $this->param['fecha'];

            try {
                
                if ($notificada) {
                    
                    $query = "  UPDATE EMT_BOLETA
                                SET NOTIFICADA = 'S',
                                FECHA_NOTIFICACION = TO_DATE('$fecha_notificacion', 'DD/MM/YYYY')
                                WHERE NUM_BOLETA = '$boleta'";

                }else{

                    $query = "  UPDATE EMT_BOLETA
                                SET NOTIFICADA = NULL,
                                COD_MOT_RECHAZO = '$motivo_rechazo'
                                WHERE NUM_BOLETA = '$boleta'";

                }

                $stid = oci_parse($this->dbConn, $query);

                if (false === oci_execute($stid)) {

                    $err = oci_error($stid);

                    $str_error = "Se ha encontrado un problema al actualizar la boleta.";

                    $this->throwError($err["code"], $str_error);

                }

            } catch (\Throwable $th) {
                //throw $th;
            }

        }

        public function boletas_sin_notificar(){

            try {
                
                $data = [];

                $query = "  SELECT * 
                            FROM EMT_BOLETA
                            WHERE NOTIFICADA IS NULL
                            AND COD_MOT_RECHAZO IS NULL
                            ORDER BY FECHA_USUARIO DESC";

                $stid = oci_parse($this->dbConn, $query);
                oci_execute($stid);

                $boletas = [];

                while ($row = oci_fetch_array($stid, OCI_ASSOC)) {
                    
                    $boletas [] = $row;

                }

                $data["items"] = $boletas;
                $data["headers"] = [

                    [
                        "text" => "No. Boleta",
                        "value" => "NUM_BOLETA",
                        "sortable" => false,
                        "width" => "10%"
                    ],
                    [
                        "text" => "No. Placa",
                        "value" => "NUM_PLACA",
                        "sortable" => false,
                        "width" => "15%"
                    ],
                    [
                        "text" => "Tipo",
                        "value" => "TIPO_VEHICULO",
                        "sortable" => false,
                        "width" => "10%"
                    ],
                    [
                        "text" => "Marca",
                        "value" => "MARCA",
                        "sortable" => false,
                        "width" => "10%"
                    ],
                    [
                        "text" => "Color",
                        "value" => "COLOR",
                        "sortable" => false,
                        "width" => "10%"
                    ],
                    [
                        "text" => "Conductor",
                        "value" => "CONDUCTOR",
                        "sortable" => false,
                        "width" => "35%"
                    ],
                    [
                        "text" => "Acciones",
                        "value" => "ACTIONS",
                        "sortable" => false,
                        "width" => "10%",
                        "align" => 'right'
                    ],


                ];

                $this->returnResponse(SUCCESS_RESPONSE, $data);

            } catch (\Throwable $th) {
                //throw $th;
            }

        }

        public function boletas_notificadas(){

            try {
                
                $data = [];

                $query = "  SELECT * 
                            FROM EMT_BOLETA
                            WHERE NOTIFICADA IS NOT NULL
                            AND FECHA_NOTIFICACION IS NOT NULL
                            AND COD_MOT_RECHAZO IS NULL
                            ORDER BY FECHA_USUARIO DESC";

                $stid = oci_parse($this->dbConn, $query);
                oci_execute($stid);

                $boletas = [];

                while ($row = oci_fetch_array($stid, OCI_ASSOC)) {
                    
                    $boletas [] = $row;

                }

                $data["items"] = $boletas;
                $data["headers"] = [

                    [
                        "text" => "No. Boleta",
                        "value" => "NUM_BOLETA",
                        "sortable" => false,
                        "width" => "10%"
                    ],
                    [
                        "text" => "No. Placa",
                        "value" => "NUM_PLACA",
                        "sortable" => false,
                        "width" => "15%"
                    ],
                    [
                        "text" => "Tipo",
                        "value" => "TIPO_VEHICULO",
                        "sortable" => false,
                        "width" => "10%"
                    ],
                    [
                        "text" => "Marca",
                        "value" => "MARCA",
                        "sortable" => false,
                        "width" => "10%"
                    ],
                    [
                        "text" => "Color",
                        "value" => "COLOR",
                        "sortable" => false,
                        "width" => "10%"
                    ],
                    [
                        "text" => "Conductor",
                        "value" => "CONDUCTOR",
                        "sortable" => false,
                        "width" => "35%"
                    ],
                    [
                        "text" => "Acciones",
                        "value" => "ACTIONS",
                        "sortable" => false,
                        "width" => "10%",
                        "align" => 'right'
                    ],


                ];

                $this->returnResponse(SUCCESS_RESPONSE, $data);

            } catch (\Throwable $th) {
                //throw $th;
            }

        }

        public function boletas_rechazadas(){

            try {
                
                $data = [];

                $query = "  SELECT * 
                            FROM EMT_BOLETA
                            WHERE NOTIFICADA IS NULL
                            AND FECHA_NOTIFICACION IS  NULL
                            AND COD_MOT_RECHAZO IS NOT NULL
                            ORDER BY FECHA_USUARIO DESC";

                $stid = oci_parse($this->dbConn, $query);
                oci_execute($stid);

                $boletas = [];

                while ($row = oci_fetch_array($stid, OCI_ASSOC)) {
                    
                    $boletas [] = $row;

                }

                $data["items"] = $boletas;
                $data["headers"] = [

                    [
                        "text" => "No. Boleta",
                        "value" => "NUM_BOLETA",
                        "sortable" => false,
                        "width" => "10%"
                    ],
                    [
                        "text" => "No. Placa",
                        "value" => "NUM_PLACA",
                        "sortable" => false,
                        "width" => "15%"
                    ],
                    [
                        "text" => "Tipo",
                        "value" => "TIPO_VEHICULO",
                        "sortable" => false,
                        "width" => "10%"
                    ],
                    [
                        "text" => "Marca",
                        "value" => "MARCA",
                        "sortable" => false,
                        "width" => "10%"
                    ],
                    [
                        "text" => "Color",
                        "value" => "COLOR",
                        "sortable" => false,
                        "width" => "10%"
                    ],
                    [
                        "text" => "Conductor",
                        "value" => "CONDUCTOR",
                        "sortable" => false,
                        "width" => "35%"
                    ],
                    [
                        "text" => "Acciones",
                        "value" => "ACTIONS",
                        "sortable" => false,
                        "width" => "10%",
                        "align" => 'right'
                    ],


                ];

                $this->returnResponse(SUCCESS_RESPONSE, $data);

            } catch (\Throwable $th) {
                //throw $th;
            }

        }

    }

?>