<?php
    require('vendor/autoload.php');
    
    $config = require('config.php');
    
    // Получаем значени с Телеграма
    $request = file_get_contents('php://input');
    $requestJson = json_decode($request, true);

    // Проверка на пустые значение
    if(!isset($requestJson['message']['from']['id']) || !isset($requestJson['message']['text'])) {
        die("Вы не Telegram");
    }

    $text = $requestJson['message']['text'];
    $userId = $requestJson['message']['from']['id'];

    // Проверка на права, записан ли id в конфиге
    if($userId === $config['user_id']) {

    } else {
        sendMessage($userId, "У вас нет доступа", $config['bot_token']);
    }
    
    function sendMessage($userId, $text, $botToken){
        file_get_contents(sprintf('https://api.telegram.org/bot%s/sendMessage?chat_id=%s&text=%s', $botToken, $userId, $text));
    }