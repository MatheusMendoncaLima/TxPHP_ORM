<?php 
namespace TxPHP\orm\utils;

use DateTime;

    class ArrayToString {
    public static function from(array $array){
        $arrayString ="(";
        $first = true;
        if(sizeOf($array) == 0) return "()";
        if(array_keys($array)[0] == 0)
        foreach ($array as $value){
            if(!$first){
                $arrayString.=",";
            }
            if(is_string($value)){
                $value = "\"$value\"";
            }else if($value instanceof DateTime){
                $value = $value->format("Y-m-d H:i:s");
            }
                            
 
            if($value == null) $value = "NULL";
            $arrayString.=$value;
            $first=false;
        }
        else foreach (array_keys($array) as $key){
            $value = $array[$key];
            if(!$first){
                $arrayString.=",";
            }
            if(is_string($value)){
                $value = "\"$value\"";
            }else if($value instanceof DateTime){
                $value = $value->format("Y-m-d H:i:s");
            }
            if($value == null) $value = "NULL";
            $arrayString.="$key => $value";
            $first=false;
        }
        $arrayString.=")";

        return $arrayString;
    }
}
    
?>