<?php
    require('vendor/autoload.php');
    
    $config = require('config.php');
    
    // Получаем значени с Телеграма
    $request = file_get_contents('php://input');
    $requestJson = json_decode($request, true);

    $db = new SafeMySQL($config['db']);

    // Проверка на пустые значение
    if(!isset($requestJson['message']['from']['id']) || !isset($requestJson['message']['text'])) {
        die("Вы не Telegram");
    }

    $text = $requestJson['message']['text'];
    $userId = $requestJson['message']['from']['id'];    
    
    // Проверка на права, записан ли id в конфиге
    if($userId === $config['user_id']) {

        // Запомнить входящий платеж в рублях и указать к ней комментарий
        if(substr($text,0,4) === '/in '){
            $matches = [];
            preg_match_all('/(^[0-9]+ )(.+)/', ltrim($text, "/in "), $matches);
    
            if(isset($matches[1][0]) && isset($matches[2][0])){
                $cost = intval($matches[1][0]);
                $comment = trim($matches[2][0]);
                
                $db->query("INSERT INTO `costs`(`type`, `comment`, `cost`) VALUES (?s, ?s, ?i)", "IN", $comment, $cost);
            } else {
                sendMessage($userId, "❌ Неправильна команда ❌", $config['bot_token']);
            }
        }
        
        // Запомнить исходящий платеж в рублях и указать к ней комментарий
        if(substr($text,0,5) === '/out '){
            $matches = [];
            preg_match_all('/(^[0-9]+ )(.+)/', ltrim($text, "/out "), $matches);
    
            if(isset($matches[1][0]) && isset($matches[2][0])){
                $cost = intval($matches[1][0]);
                $comment = trim($matches[2][0]);
                
                $db->query("INSERT INTO `costs`(`type`, `comment`, `cost`) VALUES (?s, ?s, ?i)", "OUT", $comment, $cost);
            } else {
                sendMessage($userId, "❌ Неправильна команда ❌", $config['bot_token']);
            }
        }

        // Считат доход и расход за неделю, выводит итог
        if((substr($text,0,6) === "/stat ")){
            $days = intval(ltrim($text, "/stat "));

            if($days > 0) {
                // Считает сумму входящих значение за дни
                $sumIn = $db->getOne("SELECT SUM(cost) as `in_sum` FROM costs WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ?i DAY) AND created_at <= CURDATE() AND type = 'IN'", $days);
                // Считает сумму исходящих значение за дни
                $sumOut = $db->getOne("SELECT SUM(cost) as `out_sum` FROM costs WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ?i DAY) AND created_at <= CURDATE() AND type = 'OUT'", $days);
                
                $total = intval($sumIn) - intval($sumOut);

                sendMessage($userId, sprintf("Доход - %s; Расход - %s; Итог - %s;", $sumIn, $sumOut, $total), $config['bot_token']);
            } else {
                sendMessage($userId, "❌ Неправильна команда ❌", $config['bot_token']);
            }
        }

    } else {
        sendMessage($userId, "У вас нет доступа", $config['bot_token']);
    }

    // Функция отпрвки сообщения в телеграм 
    function sendMessage($userId, $text, $botToken){
        file_get_contents(sprintf('https://api.telegram.org/bot%s/sendMessage?chat_id=%s&text=%s', $botToken, $userId, urlencode($text)));
    }
    
