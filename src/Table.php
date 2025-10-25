<?php
namespace TxPHP\orm;
use BadMethodCallException;
use DateTime;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use TxPHP\orm\utils\ArrayToString;


abstract class Table
{
    private static $internalTableName;
    private static DB $internalTableConn;

    private static $internalColumnsTable;
    private static $internalTablePrimaryKey;

    private static $internalAllowTimeStamps = false;



    public static function getColumns()
    {
        return static::$internalColumnsTable;
    }

    public static function setConn($conn)
    {
        static::$internalTableConn = $conn;
        
    }

    /**
     * @phpstan-param array<string, mixed> $args
     * @return static
     */
    public static function addEntry(array $args)
    {
        $obj = [];
        foreach($args as $key => $value){
            if((in_array($key, array_keys(static::$internalColumnsTable))) == true){
                $obj[$key] = $value;
            }
        }

        $statement = "INSERT INTO `" . static::$internalTableName . "` " . str_replace("'", "`", ArrayToString::from(array_keys($obj))) . " VALUES " . ArrayToString::from(array_values($obj)). ";";
        static::$internalTableConn->execute_query($statement);
        $allResult = static::findAll(new Where($obj));
        $lastResult = $allResult[sizeof($allResult)-1];
        return $lastResult;
    }

    public static function removeWhere($idColumn, $id)
    {
        $id = is_string($id) ? "\"$id\"" : $id;
        static::$internalTableConn->execute_query("DELETE FROM `" . static::$internalTableName . "` WHERE `$idColumn` = $id");
    }
    /**
     * @return array<int, static>
     */
    public static function findAll(Where $where = new Where())
    {   
        $allRows = static::$internalTableConn->execute_query("SELECT * from `" . static::$internalTableName . "` ".$where->getStatement().";\n")->fetch_all(MYSQLI_ASSOC);
        $objects = [];
       

        foreach ($allRows as $row) {
            array_push($objects, static::arrayToObject($row));
        }

        return $objects;
    }

     public static function count(): int
    {   
        return static::$internalTableConn->execute_query("SELECT * from `" . static::$internalTableName . "` ;\n")->num_rows;
    }

    
    public function save(){
        $obj = [];
        foreach (get_object_vars($this) as $key => $value){
            if(in_array($key, get_class_vars(Table::class)) || $key == static::$internalTablePrimaryKey || $key == "createdAt" || $key == "updatedAt") continue;
            $obj[$key] = $value;
        }

        
        
        static::update($obj, new Where([static::$internalTablePrimaryKey => get_object_vars($this)[static::$internalTablePrimaryKey]]));
    }

    /**
     * @return static | null
     */
    public static function findByPK($value)
    {
        $value = is_string($value) ? "\"$value\"" : $value;
        $result = static::$internalTableConn->execute_query("SELECT * from `" . static::$internalTableName . "` WHERE `" . static::$internalTablePrimaryKey . "` = $value")->fetch_assoc();
        if (sizeof($result) >= 1) {
            return static::arrayToObject($result);

        } else
            return null;
    }

    public static function update(array $obj, Where $where = new Where())
    {
        $statement = "";
        for($i = 0; $i < sizeof($obj); $i ++){
            $key = array_keys($obj)[$i];
            $value = $obj[$key];
            if(is_string($value)) $value = "\"$value\"";
            else if(is_bool($value)) $value = $value? "true" : "false";
            $statement .= " `$key` = $value" . ($i != (sizeof($obj)-1)? ", " : " ");
        }
        static::$internalTableConn->execute_query("UPDATE `" . static::$internalTableName . "` SET $statement ".$where->getStatement() . ";");
    }

    public static function destroy(Where $where){
        $tableName = static::$internalTableName;
        static::$internalTableConn->execute_query("DELETE FROM `$tableName` ".$where->getStatement().";\n");
    }

    /**
     * Summary of arrayToObject
     * @return static|null
     */
    private static function arrayToObject($array)
    {
        $instance = new static();
        foreach (array_keys(get_class_vars(static::class)) as $var) {
            if (
                !in_array($var, array_keys(get_class_vars(Table::class))) &&
                isset($array[$var])
            ) {
                try {
                    $reflection = new ReflectionProperty($instance, $var);
                    $type = $reflection->getType();
                    if (!$type)
                        continue;
                    $returnValue = null;
                    switch ($type) {
                        case "DateTime":
                            $returnValue = new DateTime($array[$var]);
                            break;
                        default:
                            $returnValue = $array[$var];
                            break;

                    };
                    $instance->$var = $returnValue;
                } catch (ReflectionException $e) {
                    continue;
                }
            }

        }
        return $instance;

    }

 

    /**
     * Summary of init
     * @param string $name
     * @param array<string, array[type : DataType, primarykey? : boolean, allowNull? : bool, defaultValue? : mixed]> $columns
     * @param DB $db
     * @param array[timestamps? : boolean] 
     * @return void
     */
    public static function init($name, array $columns, $db, $options = [])
    {
        static::$internalTableName = $name;
        if (isset($options["timestamps"]) && $options["timestamps"]) {
            $columns["createdAt"] = ["type" => DataTypes::TIMESTAMP->get(), "defaultValue" => "CURRENT_TIMESTAMP"];
            $columns["updatedAt"] = ["type" => DataTypes::TIMESTAMP->get(), "defaultValue" => "CURRENT_TIMESTAMP", "onUpdate" => "CURRENT_TIMESTAMP"];
            static::$internalAllowTimeStamps = true;
        }
        static::$internalColumnsTable = $columns;
        static::setConn($db);
        foreach ($columns as $key => $value) {
            if (isset($value["primaryKey"]) && $value["primaryKey"]) {
                static::$internalTablePrimaryKey = $key;
            }
        }

    }


    private static $_internalColumnPossibleArgs = [
        "primaryKey" => "PRIMARY KEY",
        "allowNull" => "ALLOW NULL",
        "defaultValue" => "DEFAULT",
        "onUpdate" => "ON UPDATE",
        "autoIncrement" => "AUTO_INCREMENT",
        "unique" => "UNIQUE"

    ];
    public static function sync($alter = false, $force = false)
    {

        $tablename = static::$internalTableName;
        if ($force) {
            static::$internalTableConn->execute_query("DROP TABLE `$tablename`;");
        }
        $statement = "";
        if (static::$internalTableConn->execute_query("SELECT * from information_schema.tables WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = \"$tablename\"")->fetch_column() == 0) {
            $columns = "";
            $first = true;
            foreach (static::getColumns() as $key => $value) {
                if ($first)
                    $first = false;
                else
                    $columns .= ",\n";
                $columns .= TABLE::assembleColumn($key, $value);
            }

            $statement = <<<statement
            CREATE TABLE `$tablename`(
            $columns
            );
            statement;
            static::$internalTableConn->execute_query($statement);
        } else if ($alter) {
            $statement = "";
            foreach (static::getColumns() as $key => $value) {
                if (isset($value["primaryKey"]) && $value["primaryKey"])
                    continue;
                $exists = static::$internalTableConn->execute_query(<<<statement
                select * from information_schema.columns
                where table_schema = database()
                and table_name = "$tablename"
                and column_name = "$key";
                statement)->fetch_column() != 0;
                if ($exists) {
                    $statement .= "ALTER TABLE `$tablename` MODIFY COLUMN " . TABLE::assembleColumn($key, $value) . ";\n";
                } else {
                    $statement .= "ALTER TABLE `$tablename` ADD COLUMN " . TABLE::assembleColumn($key, $value) . ";\n";

                }
            }
            static::$internalTableConn->multi_query($statement);

        }

    }
    /**
     * Summary of assembleColumn
     * @param string $columnKey
     * @param array[type : DataType, primarykey? : boolean, allowNull? : bool, defaultValue? : mixed]> $columnValue
     * @return string
     */
    private static function assembleColumn(string $columnKey, array $columnValue)
    {
        $assembled = "`$columnKey` " . $columnValue["type"]->getStatement() . " ";
        foreach (TABLE::$_internalColumnPossibleArgs as $optionalKey => $optionalValue) {
            if (!in_array($optionalKey, array_keys($columnValue)))
                continue;
            $assembled .= match ($optionalKey) {

                "defaultValue", "onUpdate" => "$optionalValue " . $columnValue[$optionalKey],

                default => $optionalValue,

            } . " ";

        }

        return trim($assembled);
    }

    
}




?>