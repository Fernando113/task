<?php

header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: X-Requested-With");
header('Content-Type: text/html; charset=utf-8');
header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"'); 

include_once '../include/Config.php';
include_once '../include/DbHandler.php';

require '../libs/Slim/Slim.php'; 
\Slim\Slim::registerAutoloader(); 
$app = new \Slim\Slim();

/* Usando GET para consultar los exchanges */
$app->get('/exchange', function() use ($app) {
    
    $response = array();

    $queries = array();
    parse_str($_SERVER['QUERY_STRING'], $queries);

    verifyRequiredParams(array('base', 'rate'));

    $base = $app->request->get('base');
    $rate = $app->request->get('rate');
    $date = $app->request->get('date');

    echo($date);

    if(isset($date)){
        $json = file_get_contents('https://api.exchangeratesapi.io/'.$date.'?base='.$base.'&symbols='.$rate.'');
    } else {
        $json = file_get_contents('https://api.exchangeratesapi.io/latest?base='.$base.'&symbols='.$rate.'');
    }

    $obj = json_decode($json);
    $objarray = json_decode($json, true);
    
    $response["error"] = false;
    $response["message"] = "Exchange: " . count($objarray);
    $response["exchange"] = $obj;

    echoResponse(200, $response);
});

/* Usando POST para crear los exchanges */
$app->post('/exchange', 'authenticate', function() use ($app) {
    $respone = array();
    $mysql = new mysqli(SERVER, USER, PASSWORD, DB);

    if($mysql->connect_error){
        $respone["MESSAGE"] = "INTERNAL SERVER ERROR";
        $respone["STATUS"] = 400;
    } else {
        $json = file_get_contents('https://api.exchangeratesapi.io/latest');
        $jsonorg = json_decode($json);
        $obj = json_decode($json, true);

        print_r($jsonorg);

        $fecha = $obj['date'];
        $base = $obj['base'];
        $tmp1 = $obj['rates'];
        foreach ($tmp1 as $key => $value) {
            $param['symbol'][]  = $key;
            $param['value'][]  = $value;
            $param['date'][]  = $fecha;
            $param['base'][]  = $base;
        }

        $db = new DbHandler();
        $exchanges = $db->createExchanges($param);

        echo json_encode($exchanges);

    }

    //echoResponse(201, $response);
});

/* corremos la aplicación */
$app->run();

/*********************** USEFULL FUNCTIONS **************************************/

/**
 * Verificando los parametros requeridos en el metodo o endpoint
 */
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }
 
    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoResponse(400, $response);
        
        $app->stop();
    }
}
 
/**
 * Mostrando la respuesta en formato json al cliente o navegador
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoResponse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);
 
    // setting response content type to json
    $app->contentType('application/json');
 
    echo json_encode($response);
}

/**
 * Agregando un leyer intermedio e autenticación para uno o todos los metodos, usar segun necesidad
 * Revisa si la consulta contiene un Header "Authorization" para validar
 */
function authenticate(\Slim\Route $route) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();
 
    // Verifying Authorization Header
    if (isset($headers['Authorization'])) {
        //$db = new DbHandler(); //utilizar para manejar autenticacion contra base de datos
 
        // get the api key
        $token = $headers['Authorization'];
        
        // validating api key
        if (!($token == API_KEY)) { //API_KEY declarada en Config.php
            
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Acceso denegado. Token inválido";
            echoResponse(401, $response);
            
            $app->stop(); //Detenemos la ejecución del programa al no validar
            
        } else {
            //procede utilizar el recurso o metodo del llamado
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Falta token de autorización";
        echoResponse(400, $response);
        
        $app->stop();
    }
}

?>