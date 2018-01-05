<?

require('vendor/autoload.php');

use WebSocket\Client;

$hubId = '398dj3lkd098u938lkmd93';
$secret = "dfoijoeij030dd8swlkd09u3epo98u";
$websocketOptions = array( 'timeout' => 600 );

function signRequest( $path, $params, $secret ) {
    $params['time'] = time();
    uksort( $params, "strcasecmp" );
    $getString = http_build_query( $params );

    $toSign = "GET $path?$getString";
    $params['sig']=hash_hmac ( 'sha256', $toSign, $secret );
    return http_build_query( $params );
}

$path = "/wibble";
$params = array(
    'mode'      => 'getEvents',
    'hubId'  	=> $hubId,
);


$isReconnect = false;
while (1) {
    if ($isReconnect) {
        $backOffDelay = mt_rand(2,4);
        fwrite( STDERR, "Backing off for {$backOffDelay}s before attempting to reconnect\n"); 
        // Random back off
        sleep( $backOffDelay );
    }

    $getString = signRequest( $path, $params, $secret );
    $client = new Client("wss://www.voicerail.com:8080$path?$getString",$websocketOptions);

    $isReconnect = true;
    try {
        $client->send("Hello");
    } catch( Exception $e ) {
        fwrite( STDERR, $e->getMessage()."\n" );
        continue;
    }

    while (1) {
        try {
            $received = $client->receive();
            // The server may have disconnected - test for that first
            if (!$client->isConnected()) {
                continue(2);
            }
            echo $received;
        } catch( Exception $e ) {
            $message = $e->getMessage();
            if (preg_match('/^Empty read/i',$message)) {
                fwrite( STDERR, "Connection closed by remote end" );
                try {
                    $client->close(); 
                } catch( Exception $e ) {
                    fwrite( STDERR, $e->getMessage()."\n" );
                    continue(2);
                }
                continue(2);
            } 
            echo $e->getMessage();
        }
    }
   
}

