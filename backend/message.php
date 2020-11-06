<?php

require_once(realpath(dirname(__FILE__).'/livechat-config.php'));
require_once(realpath(dirname(__FILE__).'/models/settings.php'));
require_once(realpath(dirname(__FILE__).'/models/helper.php'));


if ( session_status() !== PHP_SESSION_ACTIVE ) session_start(); 

cors();


define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/');


global $livechat_db;
global $livechat_prefix;
$token = $_GET['token'];

if (!empty($token)){
    $token = strtolower($token);

    //1) We check if we have no admin
    // And if the message equals BOT_ADMIN_SECRET
    // We record ADMIN_CHAT_TOKEN and chat_id in `admin_chat`

    //2) if the bot message is anything else, we record it with time and chat_id into `admin_chat_message`, NOTE that the session_id when this script runs may be a malicious user trying to steal another one's chat so we don't store session_ids and we retrieve the messages based on the assumption that the admin can only chat with one person at a time and that has a current_chat_start_date_utc
       
    
    $offset = get_setting("last_telegram_bot_offset");
    error_log('requesting getUpdates ...');
    $updates = apiRequest('getUpdates',array('offset'=>$offset));
    $messages =  array();
    
    if (!empty($updates)){
      foreach ($updates as $update){
          $message = $update['message'];
          $chat_id = $message['chat']['id'];
          $message_text= $message['text'];
          error_log($message_text);
          if ( $message_text == BOT_ADMIN_SECRET){ //TODO we should also check if there is no admin right now.
            error_log('adding admin chat...');
            add_admin_chat(ADMIN_CHAT_TOKEN, $chat_id);
            
          }else{

              array_push($messages,  array('message_text'=>$message_text,
              'message_date_utc'=>getDateFromEpcoch($message['date']),
            'chat_id'=>$chat_id));
          }
        
          $offset = $update['update_id'] +1;
      }
      update_setting("last_telegram_bot_offset", $offset);
    }

    
    if (!empty($messages)){
      $insert_messages_cmd = build_insert_command($messages,"${livechat_prefix}admin_chat_message",false);
      $livechat_db->query($insert_messages_cmd);
    }
   
    
    log_with_sid('$token:'.json_encode($token));
    $admin_chat = get_admin_chat_by_token($token);
    log_with_sid('$admin_chat:'.json_encode($admin_chat));
    
    $admin_chat['dirty'] = false;
    $current_session_id = null;
    if ($admin_chat){
      $now_utc = new DateTime('now', new DateTimeZone("UTC"));
      
      $last_message_timeout_minutes = 3;
      $last_heart_beat_timeout_seconds = 30;
      if (array_key_exists('last_message_date_utc', $admin_chat )){ 
      $last_message_date_utc = $admin_chat['last_message_date_utc'];
      $last_heart_beat_utc = $admin_chat['last_heart_beat_utc'];

      if (!empty($last_message_date_utc)){
        if ( $now_utc > date_create($last_message_date_utc.' UTC')->add(new DateInterval('PT'.$last_message_timeout_minutes.'M'))){ 
          end_chat($admin_chat, 'Message Timeout');
        } 
      }

      if (!empty($last_heart_beat_utc)){
        log_with_sid('$last_heart_beat_utc:'.json_encode($last_heart_beat_utc));
        if ( $now_utc > date_create($last_heart_beat_utc.' UTC')->add(new DateInterval('PT'.$last_heart_beat_timeout_seconds.'S'))){ 
          end_chat($admin_chat, 'No Heartbeat');
        } 
      }
      
      
      
      $current_session_id = $admin_chat['current_session_id'];
      }
      $wrong_session_id = false;
      
      if ($current_session_id!=null){
        if ($current_session_id != session_id()){
            $wrong_session_id = true;
            //TODO Header error
            $error_message = "The admin is busy with another chat that is not yet finished";
            log_with_sid($error_message.';$admin_chat[\'current_session_id\']:'.$current_session_id);
            echo $error_message;
        }
      }
      
      if (!$wrong_session_id){
        if ($current_session_id!=null) {
          if ($_SERVER['REQUEST_METHOD'] == 'GET'){
              get_messages($admin_chat);
          }
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST'){
            send_new_message($admin_chat);
            
        }
      }
      
    if ($admin_chat['dirty']){
      log_with_sid('INFO: $admin_chat is dirty, going to update.');
      //Finally update $admin_chat
      unset ($admin_chat['dirty']);
      update_admin_chat($admin_chat);
    }

    }else{

        //TODO Header error
          echo "Wrong token";
    }
    

   
}


function getDateFromEpcoch($epoch){
  $dt = new DateTime("@$epoch"); // convert UNIX timestamp to PHP DateTime
  return $dt->format('Y-m-d H:i:s');
}

function get_messages(&$admin_chat){

    if ($admin_chat['current_session_id'] == null) return;

    $admin_chat['last_heart_beat_utc'] = now_utc_formatted();
    $admin_chat['dirty'] = true;

    global $livechat_db;
    global $livechat_prefix;
    $current_chat_start_date_utc = $livechat_db->real_escape_string($admin_chat['current_chat_start_date_utc']);
    $chat_id = $livechat_db->real_escape_string($admin_chat['chat_id']);

    $select_cmd = "SELECT message_date_utc, message_text FROM `${livechat_prefix}admin_chat_message`
    WHERE chat_id='$chat_id'
    AND message_date_utc > '$current_chat_start_date_utc'
    AND is_read = '0'
    LOCK IN SHARE MODE
    ";
    

    $result = $livechat_db->query($select_cmd);

    if ($result){

      $messages = $result->fetch_all(MYSQLI_ASSOC);
      foreach ($messages as &$message){
        if ($message['message_text'] == 'end chat!'){
          end_chat($admin_chat, 'Admin order');
        }
        $message['message_date_utc'] = strtotime($message['message_date_utc']).'000';
      }
      echo json_encode($messages);


      $update_is_read_cmd = "UPDATE `${livechat_prefix}admin_chat_message` 
      SET is_read = '1'
      WHERE chat_id = '$chat_id'";

      $livechat_db->query($update_is_read_cmd);

    }

}

function send_new_message(&$admin_chat){
     
    
    $is_first_message = $admin_chat['current_session_id'] == null;
    $email = '';
    if ($is_first_message){
      $admin_chat['current_session_id'] = ''.session_id();
      $admin_chat['current_chat_start_date_utc'] = now_utc_formatted();
      
      apiRequest("sendMessage", 
        array(
         'chat_id' => $admin_chat['chat_id'],
         'text' => 'Chat started with '.$admin_chat['current_session_id']
        ));
    }

    $admin_chat['last_message_date_utc'] = now_utc_formatted();
    $admin_chat['dirty']=true;
    $new_message_json = file_get_contents('php://input');
    $new_message = json_decode($new_message_json, true); //assoc array
    $send_message_text = $new_message['message_text'];
    if (array_key_exists('email', $new_message)){
      $send_message_text = '['.$new_message['email'].']:'.$send_message_text;
    }
    apiRequest("sendMessage", 
        array(
         'chat_id' => $admin_chat['chat_id'],
         'text' => $send_message_text
        ));

    header('Content-type: application/json');
    echo '{"result":"ok"}';

}

function now_utc_formatted(){
  return (new DateTime('now', new DateTimeZone("UTC")))->format('Y-m-d H:i:s'); 
}

function end_chat(&$admin_chat, $cause=''){
    
    //IN END CHAT 
    //NOTE that we don't have to check for the session here
    //Because this method occurs after satisfying the conditions for ending. It may happen in another client.
     
      $ending_session_id = $admin_chat['current_session_id'];
      log_with_sid('INFO: Ending chat with'.$ending_session_id);
      $admin_chat['current_session_id'] = null;
      $admin_chat['current_chat_start_date_utc'] = null;
      $admin_chat['last_message_date_utc'] = null;
      $admin_chat['last_heart_beat_utc']=null;
      $admin_chat['dirty']=true;
      
      log_with_sid('$admin_chat:'.json_encode($admin_chat));
      
      apiRequest("sendMessage", 
      array(
       'chat_id' => $admin_chat['chat_id'],
       'text' => "Chat ended with $ending_session_id ($cause)"
      ));

    
}

function add_admin_chat($token, $chat_id){
    global $livechat_db;
    global $livechat_prefix;
    $cmd = "INSERT INTO ${livechat_prefix}admin_chat (chat_id, token) VALUES ('$chat_id','$token');";
    //error_log($cmd);
    $livechat_db->query($cmd);
}



function get_admin_chat_by_token($token){
    global $livechat_db;
    global $livechat_prefix;
    $token = $livechat_db->real_escape_string($token);
    $cmd = "SELECT * FROM ${livechat_prefix}admin_chat WHERE token='$token';";
    $result = $livechat_db->query($cmd);
    if ($result){
      return $result->fetch_assoc();
    }else{
      return false;
    }
}

function update_admin_chat($admin_chat){
  global $livechat_db;
  global $livechat_prefix;
  $chat_id=false;
  if (array_key_exists('chat_id', $admin_chat)){
      $chat_id = $livechat_db->real_escape_string($admin_chat['chat_id']);
      unset($admin_chat['chat_id']);
      unset($admin_chat['token']);
  }
  $update_cmd = "UPDATE `${livechat_prefix}admin_chat` SET";
  $set_array = array();
  foreach($admin_chat as $k=>$v){
      if (empty($v)){
        $v="NULL";
      }else{
        $v="'$v'";
      }
      array_push($set_array," $k = $v");
  }
  $update_cmd.=implode(',', $set_array);
  if ($chat_id){
      $update_cmd.=" WHERE `chat_id`='$chat_id';";
  }else{
      return false;
  }
  log_with_sid('INFO:'.$update_cmd);
// var_dump($update_cmd);
  $result = $livechat_db->query($update_cmd);
  return $result;
}


function apiRequestWebhook($method, $parameters) {
  if (!is_string($method)) {
    log_with_sid("Method name must be a string\n");
    return false;
  }

  if (!$parameters) {
    $parameters = array();
  } else if (!is_array($parameters)) {
    log_with_sid("Parameters must be an array\n");
    return false;
  }

  $parameters["method"] = $method;

  header("Content-Type: application/json");
  echo json_encode($parameters);
  return true;
}

function exec_curl_request($handle) {
  $response = curl_exec($handle);

  if ($response === false) {
    $errno = curl_errno($handle);
    $error = curl_error($handle);
    log_with_sid("Curl returned error $errno: $error\n");
    curl_close($handle);
    return false;
  }

  $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
  curl_close($handle);

  if ($http_code >= 500) {
    // do not wat to DDOS server if something goes wrong
    sleep(10);
    return false;
  } else if ($http_code != 200) {
    $response = json_decode($response, true);
    log_with_sid("Request has failed with error {$response['error_code']}: {$response['description']}\n");
    if ($http_code == 401) {
      throw new Exception('Invalid access token provided');
    }
    return false;
  } else {
    $response = json_decode($response, true);
    if (isset($response['description'])) {
      log_with_sid("Request was successful: {$response['description']}\n");
    }
    $response = $response['result'];
  }

  return $response;
}

function apiRequest($method, $parameters) {
  if (!is_string($method)) {
    log_with_sid("Method name must be a string\n");
    return false;
  }

  if (!$parameters) {
    $parameters = array();
  } else if (!is_array($parameters)) {
    log_with_sid("Parameters must be an array\n");
    return false;
  }

  foreach ($parameters as $key => &$val) {
    // encoding to JSON array parameters, for example reply_markup
    if (!is_numeric($val) && !is_string($val)) {
      $val = json_encode($val);
    }
  }
  $url = API_URL.$method.'?'.http_build_query($parameters);

  $handle = curl_init($url);
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($handle, CURLOPT_TIMEOUT, 60);
  curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 0);

  return exec_curl_request($handle);
}

function apiRequestJson($method, $parameters) {
  if (!is_string($method)) {
    log_with_sid("Method name must be a string\n");
    return false;
  }

  if (!$parameters) {
    $parameters = array();
  } else if (!is_array($parameters)) {
    log_with_sid("Parameters must be an array\n");
    return false;
  }

  $parameters["method"] = $method;

  $handle = curl_init(API_URL);
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($handle, CURLOPT_TIMEOUT, 60);
  curl_setopt($handle, CURLOPT_POST, true);
  curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters));
  curl_setopt($handle, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));

  return exec_curl_request($handle);
}

function processMessage($message) {
  // process incoming message
  $message_id = $message['message_id'];
  $chat_id = $message['chat']['id'];
  if (isset($message['text'])) {
    // incoming text message
    $text = $message['text'];

    if (strpos($text, "/start") === 0) {
      apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Hello', 'reply_markup' => array(
        'keyboard' => array(array('Hello', 'Hi')),
        'one_time_keyboard' => true,
        'resize_keyboard' => true)));
    } else if ($text === "Hello" || $text === "Hi") {
      apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Nice to meet you'));
    } else if (strpos($text, "/stop") === 0) {
      // stop now
    } else {
      apiRequestWebhook("sendMessage", array('chat_id' => $chat_id, "reply_to_message_id" => $message_id, "text" => 'Cool'));
    }
  } else {
    apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'I understand only text messages'));
  }
}

/*
define('WEBHOOK_URL', 'https://my-site.example.com/secret-path-for-webhooks/');

if (php_sapi_name() == 'cli') {
  // if run from console, set or delete webhook
  apiRequest('setWebhook', array('url' => isset($argv[1]) && $argv[1] == 'delete' ? '' : WEBHOOK_URL));
  exit;
}*/



/**
 *  An example CORS-compliant method.  It will allow any GET, POST, or OPTIONS requests from any
 *  origin.
 *
 *  In a production environment, you probably want to be more restrictive, but this gives you
 *  the general idea of what is involved.  For the nitty-gritty low-down, read:
 *
 *  - https://developer.mozilla.org/en/HTTP_access_control
 *  - http://www.w3.org/TR/cors/
 *
 */
function cors() {

    // Allow from any origin
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header("Access-Control-Allow-Origin: *");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
    }

    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");         

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

        exit(0);
    }

 }

 function log_with_sid($message){
 
    error_log('[sid:'.session_id().'] '.$message);
 }


?>
