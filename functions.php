<?php

function closeConnection($message = 'OK!')
{
    if (php_sapi_name() === 'cli' || isset($GLOBALS['exited'])) {
        return;
    }
    @ob_end_clean();
    header('Connection: close');
    ignore_user_abort(true);
    ob_start();
    echo '<html><body><h1>'.$message.'</h1></body</html>';
    $size = ob_get_length();
    header("Content-Length: $size");
    header('Content-Type: text/html');
    ob_end_flush();
    flush();
    $GLOBALS['exited'] = true;
}

function shutdown_function($lock)
{
    $a = fsockopen((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'tls' : 'tcp').'://'.$_SERVER['SERVER_NAME'], $_SERVER['SERVER_PORT']);
    fwrite($a, $_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI'].' '.$_SERVER['SERVER_PROTOCOL']."\r\n".'Host: '.$_SERVER['SERVER_NAME']."\r\n\r\n");
    flock($lock, LOCK_UN);
    fclose($lock);
}

function sm($chatID, $text, $parsemode = 'HTML', $reply = 0)
{
    global $MadelineProto;
    if ($reply) {
        $MadelineProto->messages->sendMessage(['peer' => $chatID, 'message' => $text, 'parse_mode' => $parsemode, 'reply_to_msg_id' => $reply]);
    } else {
        $MadelineProto->messages->sendMessage(['peer' => $chatID, 'message' => $text, 'parse_mode' => $parsemode]);
    }
}

function setProfilo($nome, $cognome = '')
{
    global $MadelineProto;
    $MadelineProto->account->updateProfile(['first_name' => $nome, 'last_name' => $cognome]);
}

function joinChat($chatLink, $chatLOG)
{
    //ACCETTA SOLO https://t.me/joinchat/ksjdvbdskvhbsdk o @usernameChat in questo formato
    global $MadelineProto;

    try {
        if (stripos($chatLink, 'joinchat')) {
            $MadelineProto->messages->importChatInvite([
      'hash' => str_replace('https://t.me/joinchat/', '', $chatLink),
    ]);
        } else {
            $MadelineProto->channels->joinChannel([
      'channel' => '@'.str_replace('@', '', $chatLink),
    ]);
        }
        sm($chatLOG, 'Sono entrato nel canale/gruppo');
    } catch (\danog\MadelineProto\RPCErrorException $e) {
        sm($chatLOG, 'NON sono entrato nel canale/gruppo.');
    } catch (\danog\MadelineProto\Exception $e2) {
        sm($chatLOG, 'NON sono entrato nel canale/gruppo.');
    }
}

function abbandonaChat($chatID)
{
    //USARE SOLO SU SUPERGRUPPI/CANALI o CRASH
    global $MadelineProto;
    $MadelineProto->channels->leaveChannel(['channel' => $chatID]);
}
