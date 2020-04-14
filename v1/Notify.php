<?php
	require dirname(__DIR__).'/vendor/autoload.php';

	use PhpAmqpLib\Connection\AMQPStreamConnection;
	use PhpAmqpLib\Exchange\AMQPExchangeType;
	use PhpAmqpLib\Message\AMQPMessage;

	include_once '../include/Config.php';
	include_once '../include/DbHandler.php';

	$respone = array();
	$mysql = new mysqli(SERVER, USER, PASSWORD, DB);

	if($mysql->connect_error){
		$respone["MESSAGE"] = "INTERNAL SERVER ERROR";
		$respone["STATUS"] = 400;
	} else {
		$json = file_get_contents('https://api.exchangeratesapi.io/latest');
	    $obj = json_decode($json, true);
	    $tmp = $obj['rates'];
	    $currencyUSD = $tmp['USD'];

	    $sql = "SELECT * FROM exchange WHERE symbol = 'USD';";
    	$result = $mysql->query($sql);
    	$row = $result->fetch_assoc();
		$lastexchange = $row['exchange'];

	    if($currencyUSD != $lastexchange){
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
	        $exchanges = $db->notifyExchange($respone, $param);
	    } else {
			$respone["MESSAGE"] = "THE EXCHANGE HAS NOT CHANGED";
			$respone["STATUS"] = 500;
		}
	}
	echo json_encode($respone);
?>