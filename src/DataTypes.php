<?php

namespace TxPhpOrm;

use InvalidArgumentException;

class DataType{
    public string $name;
    public bool $unsigned = false;
    public function __construct(string $name, bool $unsigned= false) {
        $this->name = $name; 
        $this->unsigned = $unsigned;
    }
    public function unsigned(): static{
        $this->unsigned=true;
        return $this;
    }
    public function getStatement(){
        return $this->name . ($this->unsigned? " UNSIGNED" : "");
    }
}
enum DataTypes {
    // Numerics
    case INT;
    case BIGINT;
    case SMALLINT;
    case TINYINT;
    case FLOAT;
    case DOUBLE;
    case DECIMAL;

    // Text
    case CHAR;
    case STRING;
    case TEXT;
    case MEDIUMTEXT;
    case LONGTEXT;
    case ENUM;

    // DATETIME
    case DATE;
    case DATETIME;
    case TIMESTAMP;
    case TIME;
    case YEAR;

    // BINARY
    case BLOB;
    case MEDIUMBLOB;
    case LONGBLOB;

    public function get($range = 255, $extra = null): DataType {
        return match($this) {
            // Números
            self::INT        => new DataType("INT"),
            self::BIGINT     => new DataType("BIGINT"),
            self::SMALLINT   => new DataType("SMALLINT"),
            self::TINYINT    => new DataType("TINYINT"),
            self::FLOAT      => new DataType("FLOAT"),
            self::DOUBLE     => new DataType("DOUBLE"),
            self::DECIMAL    => new DataType("DECIMAL(10,2)"),

            // Texto
            self::CHAR       => new DataType("CHAR($range)"),
            self::STRING     => new DataType("VARCHAR($range)"),
            self::TEXT       => new DataType("TEXT"),
            self::MEDIUMTEXT => new DataType("MEDIUMTEXT"),
            self::LONGTEXT   => new DataType("LONGTEXT"),
            self::ENUM       => new DataType("ENUM(" . self::formatEnumValues($extra) . ")"),

            // Datas
            self::DATE       => new DataType("DATE"),
            self::DATETIME   => new DataType("DATETIME"),
            self::TIMESTAMP  => new DataType("TIMESTAMP"),
            self::TIME       => new DataType("TIME"),
            self::YEAR       => new DataType("YEAR"),

            // Binário
            self::BLOB        => new DataType("BLOB"),
            self::MEDIUMBLOB  => new DataType("MEDIUMBLOB"),
            self::LONGBLOB    => new DataType("LONGBLOB"),
        };
    }

    private static function formatEnumValues($extra): string {
        if (!is_array($extra) || empty($extra)) {
            throw new InvalidArgumentException("ENUM requires an array of possible values");
        }
        // Escapa os valores corretamente com aspas simples
        return implode(',', array_map(fn($v) => "'" . addslashes($v) . "'", $extra));
    }
}


?>