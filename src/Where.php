<?php
namespace TxPhpOrm;

class Where
{

private static $operators = [
    "WHEREINTERNALOPERATOR_eq" => "=",
    "WHEREINTERNALOPERATOR_ne" => "!=",
    "WHEREINTERNALOPERATOR_gt" => ">",
    "WHEREINTERNALOPERATOR_gte" => ">=",
    "WHEREINTERNALOPERATOR_lt" => "<",
    "WHEREINTERNALOPERATOR_lte" => "<=",
    "WHEREINTERNALOPERATOR_between" => "BETWEEN",
    "WHEREINTERNALOPERATOR_notBetween" => "NOT BETWEEN",
    "WHEREINTERNALOPERATOR_in" => "IN",
    "WHEREINTERNALOPERATOR_notIn" => "NOT IN",
    "WHEREINTERNALOPERATOR_like" => "LIKE",
    "WHEREINTERNALOPERATOR_notLike" => "NOT LIKE",
    "WHEREINTERNALOPERATOR_iLike" => "ILIKE",
    "WHEREINTERNALOPERATOR_notILike" => "NOT ILIKE",
    "WHEREINTERNALOPERATOR_is" => "IS",
    "WHEREINTERNALOPERATOR_not" => "IS NOT",
    "WHEREINTERNALOPERATOR_col" => "=",
    "WHEREINTERNALOPERATOR_and" => "AND",
    "WHEREINTERNALOPERATOR_or" => "OR"
];

    private string $statement;

    public function __construct(array $args = [])
    {
        $statement = $args != []? "WHERE ": "";
        for ($i = 0; $i < sizeof($args); $i++) {
            $key = array_keys($args)[$i];
            $value = $args[$key];
            if(is_bool($value)) $value = $value?"true" : "false";
            else if(is_string($value)) $value = "\"$value\"";
            if (!($i == 0))
                $statement .= " AND ";
            if (!in_array($key, array_keys(Where::$operators))) {
                if (!is_array($value))
                    $statement .= "(`$key` = $value)";
                else
                    $statement .= Where::handleOperators($value, "AND",  $key);
            } else {
                    $statement .= Where::handleOperators($value, "AND",  $key);

            }

        }
        $statement = trim($statement) . "";
        $this->statement = $statement;

    }

    public function getStatement(){
        return $this->statement;
    }

    private static function handleOperators($args, $externalOperator, $owner = null, $internal = false): string
    {
        $response = "(";
        for ($i = 0; $i < sizeof($args); $i++) {
            
            $key = array_keys($args)[$i];
            $value = $args[$key];
            if(is_bool($value)) $value = $value?"true" : "false";
            else if(is_string($value)) $value = "\"$value\"";
            if (in_array($key, array_keys(Where::$operators)) && is_array($value)) {
                $response .= Where::handleOperators($value, $key, $owner, $i != sizeof($args)-1);
            }else if (!in_array($key, array_keys(Where::$operators)) && !is_array($value)){
                $response .= "`$key` = $value".($i == sizeof($args)-1? "" : " AND ");
            }else if (is_array($value)){
                $response .= Where::handleOperators($value, $externalOperator, $key, $i != sizeof($args)-1);
            }else{
               $response .= "`$owner` ".Where::$operators[$key] ." $value ". ($i == sizeof($args)-1? "" : Where::$operators[$externalOperator]). " ";
            }
            if($i == sizeof($args)-1 && $internal==true){
                $response.=") AND ";
            }

        }
        return $response . ($internal? "":")");
    }
}

class Operators {
    public static $eq = "WHEREINTERNALOPERATOR_eq"; // equals 
    public static $ne = "WHEREINTERNALOPERATOR_ne"; // npt equals 
    public static $gt = "WHEREINTERNALOPERATOR_gt"; // greater than
    public static $gte = "WHEREINTERNALOPERATOR_gte"; // equals or greater than 
    public static $lt = "WHEREINTERNALOPERATOR_lt"; // lower than 
    public static $lte = "WHEREINTERNALOPERATOR_lte"; // equals or lower than
    public static $between = "WHEREINTERNALOPERATOR_between";
    public static $notBetween = "WHEREINTERNALOPERATOR_notBetween";
    public static $in = "WHEREINTERNALOPERATOR_in";
    public static $notIn = "WHEREINTERNALOPERATOR_notIn";
    public static $like = "WHEREINTERNALOPERATOR_like";
    public static $notLike = "WHEREINTERNALOPERATOR_notLike";
    public static $iLike = "WHEREINTERNALOPERATOR_iLike";
    public static $notILike = "WHEREINTERNALOPERATOR_notILike";
    public static $is = "WHEREINTERNALOPERATOR_is";
    public static $not = "WHEREINTERNALOPERATOR_not";
    public static $col = "WHEREINTERNALOPERATOR_col";
    public static $and = "WHEREINTERNALOPERATOR_and";
    public static $or = "WHEREINTERNALOPERATOR_or";
}


?>