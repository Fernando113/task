<?php

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;

class DbHandler {
 
    private $conn;
 
    function __construct() {
        require_once dirname(__FILE__) . './DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }
 
    public function createExchanges($param){
        $mysql = new mysqli(SERVER, USER, PASSWORD, DB);

        for ($i=0; $i <count($param['symbol']) ; $i++) {
            $sql = "INSERT INTO exchange(symbol, exchange, date, base) VALUES ('".$param['symbol'][$i]."',".$param['value'][$i].",'".$param['date'][$i]."','".$param['base'][$i]."');";
            if ($mysql->query($sql)) {
                $respone["MESSAGE"] = "SAVE DATA SUCCED";
                $respone["STATUS"] = 200;
            } else {
                $respone["MESSAGE"] = "SAVE DATA FAILED";
                $respone["STATUS"] = 500;
            }
        }

        echo json_encode($respone);
    }

    public function notifyExchange($respone, $param){
        $host = 'localhost:8181';
        $user = 'guest';
        $port = 15671;
        $pass = 'guest';
        $vhost = 'guest';
        $exchange = 'exchange';
        $queue = 'The_exchange_has_changed';

        $connection = new AMQPStreamConnection($host, $port, $user, $pass, $vhost);
        $channel = $connection->channel();

        $channel->queue_declare($queue, false, true, false, false);

        $channel->exchange_declare($exchange, AMQPExchangeType::DIRECT, false, true, false);

        $channel->queue_bind($queue, $exchange);

        $messageBody = json_encode([
            'email' => 'fros113@hotmail.com',
            'exchange' => true
        ]);

        $message = new AMQPMessage($messageBody, ['content_type' => 'application/json', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
        $channel->basic_publish($message, $exchange);

        $channel->close();
        $connection->close();

        $respone["MESSAGE"] = "THE EXCHANGE HAS CHANGED";
        $respone["STATUS"] = 200;  

        $this->updateExchanges($param);

        echo json_encode($respone);

    }

    public function updateExchanges($param){
        $mysql = new mysqli(SERVER, USER, PASSWORD, DB);

        for ($i=0; $i <count($param['symbol']) ; $i++) {
            $sql = "update exchange set exchange = '".$param['value'][$i]."', date = '".$param['date'][$i]."' where symbol = '".$param['symbol'][$i]."';";

            echo $sql;
            if ($mysql->query($sql)) {
                $respone["MESSAGE"] = "SAVE DATA SUCCED";
                $respone["STATUS"] = 200;
            } else {
                $respone["MESSAGE"] = "SAVE DATA FAILED";
                $respone["STATUS"] = 500;
            }
        }

        echo json_encode($respone);
    }
 
}
 
?>