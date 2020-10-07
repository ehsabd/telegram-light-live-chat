<?php 


function build_insert_command($arr, $table_name,$has_created_date_utc){
    global $livechat_db;
    $insert_cmd="INSERT INTO `".$livechat_db->real_escape_string($table_name)."` ";
    $keys = array();
    
    if (has_string_keys($arr)){
        $arr = array($arr);
    }

    foreach ($arr[0] as $k=>$v){
        array_push($keys, '`'.$livechat_db->real_escape_string((string)$k).'`');
    }

    if ($has_created_date_utc){
        array_push($keys, '`created_date_utc`');
    }

    $all_vals = array();
    foreach ($arr as $item){
        $item_vals = array();
        foreach ($item as $k=>$v){
            array_push($item_vals, "'".$livechat_db->real_escape_string((string)$v)."'");
        }
        if ($has_created_date_utc){
            array_push($item_vals, "UTC_TIMESTAMP()");
        }
        array_push($all_vals,$item_vals);
    }

    $values_part = implode(', ', array_map(function ($item) {
        return '('.implode(', ', $item).')';
    }, $all_vals));

    $insert_cmd.='('.implode(',',$keys).") VALUES $values_part ;";

    return $insert_cmd;
}

function has_string_keys(array $array) {
    return count(array_filter(array_keys($array), 'is_string')) > 0;
}