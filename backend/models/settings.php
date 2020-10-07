<?php

function get_setting($key){
    global $livechat_db;
    $key = $livechat_db->real_escape_string($key);
    $cmd =  "SELECT setting_value FROM cps_setting WHERE setting_key='$key' LOCK IN SHARE MODE";
    $result = $livechat_db->query($cmd);
    if ($result){
        return $result->fetch_row()[0];
    }else{
        return false;
    }
}


function get_settings(){
    global $livechat_db;
    $cmd =  "SELECT setting_key,setting_value FROM cps_setting";
   
    $result = $livechat_db->query($cmd);
    if ($result){
        $output=array();
        
        foreach (($result->fetch_all(true)) as $row){
         
            $output[$row['setting_key']] = $row['setting_value'];
        }
        return $output;
    }else{
        return false;
    }
}


function update_setting($key,$value){
    global $livechat_db;
    $key = $livechat_db->real_escape_string($key);
    $value = $livechat_db->real_escape_string($value);
    $cmd =  "UPDATE cps_setting
     SET setting_value = '$value'
      WHERE setting_key='$key'";
    $result = $livechat_db->query($cmd);
    return $result;
}

?>