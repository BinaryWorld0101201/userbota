<?php

ini_set('display_errors', true);
error_reporting(E_ALL);

chdir(__DIR__);
if (!file_exists(__DIR__.'/madeline.php') || !filesize(__DIR__.'/madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', __DIR__.'/madeline.php');
}

$remote = 'bruninoit/AltervistaUserBot';
$branch = 'master';
$url = "https://raw.githubusercontent.com/$remote/$branch";

$version = file_get_contents("$url/av.version?v=new");
if (!file_exists(__DIR__.'/av.version') || file_get_contents(__DIR__.'/av.version') !== $version) {
    foreach (explode("\n", file_get_contents("$url/files?v=new")) as $file) {
        if ($file) {
            copy("$url/$file?v=new", __DIR__."/$file");
        }
    }
    foreach (explode("\n", file_get_contents("$url/basefiles?v=new")) as $file) {
        if ($file && !file_exists(__DIR__."/$file")) {
            copy("$url/$file?v=new", __DIR__."/$file");
        }
    }
}

require __DIR__.'/madeline.php';
require __DIR__.'/functions.php';
require __DIR__.'/_config.php';

if (!file_exists('bot.lock')) {
    touch('bot.lock');
}
$lock = fopen('bot.lock', 'r+');

$try = 1;
$locked = false;
while (!$locked) {
    $locked = flock($lock, LOCK_EX | LOCK_NB);
    if (!$locked) {
        closeConnection();

        if ($try++ >= 30) {
            exit;
        }
        sleep(1);
    }
}

$MadelineProto = new \danog\MadelineProto\API('session.madeline', ['logger' => ['logger_level' => 5]]);
$MadelineProto->start();

register_shutdown_function('shutdown_function', $lock);
closeConnection();

$running = true;
$offset = 0;
$started = time();

try {
    while ($running) {
        $updates = $MadelineProto->get_updates(['offset' => $offset]);
        foreach ($updates as $update) {
            $offset = $update['update_id'] + 1;

            if (isset($update['update']['message']['out']) && $update['update']['message']['out'] && !$leggi_messaggi_in_uscita) {
                continue;
            }
            $up = $update['update']['_'];

            if ($up == 'updateNewMessage' or $up == 'updateNewChannelMessage') {
                if (isset($update['update']['message']['message'])) {
                    $msg = $update['update']['message']['message'];
                }

                try {
                    $chatID = $MadelineProto->get_info($update['update']);
                    $type = $chatID['type'];
                    $chatID = $chatID['bot_api_id'];
                } catch (Exception $e) {
                }

                if (isset($update['update']['message']['from_id'])) {
                    $userID = $update['update']['message']['from_id'];
                }

                try {
                    require '_comandi.php';
                } catch (Exception $e) {
                    if (isset($chatID)) {
                        try {
                            //sm($chatID, '<code>'.$e.'</code>');
                        } catch (Exception $e) {
                        }
                    }
                }
            }

            if (isset($msg)) {
                unset($msg);
            }
            if (isset($chatID)) {
                unset($chatID);
            }
            if (isset($userID)) {
                unset($userID);
            }
            if (isset($up)) {
                unset($up);
            }
        }
    }
} catch (\danog\MadelineProto\RPCErrorException $e) {
    \danog\MadelineProto\Logger::log((string) $e);
    if (in_array($e->rpc, ['SESSION_REVOKED', 'AUTH_KEY_UNREGISTERED'])) {
        foreach (glob('session.madeline*') as $path) {
            unlink($path);
        }
    }
}
