<?php 
namespace TxPhpOrm;

use mysqli;



class DB{

    private $host;
    private $user;
    private $password;
    private string $db;

    protected $conn;

    public function __construct(string $host, string $user, string $password, string $db){
        $this->host=$host;    
        $this->user=$user;     
        $this->password=$password;  
        $this->db=$db;  
    }

    public function connect() {
        $this->conn = new mysqli($this->host, $this->user, $this->password, $this->db);
    }


    public function disconnect() {
        $this->conn->close();
    }

    public function getName(){
        return $this->db ?? null;
    }

    public function execute_query($statement){
        return $this->conn->execute_query($statement);
    }
    public function multi_query($statement){
        $results = [];
        $this->conn->multi_query($statement);
        $result = $this->conn->store_result();
        if($result){
            while($row = $result->fetch_column()){
                array_push($results, $row);
            }
            $result->free();
        }

        while($this->conn->more_results()){

            $this->conn->next_result();
            $result = $this->conn->store_result();
            if($result){
                while($row = $result->fetch_column()){
                    array_push($results, $row);
                }
                $result->free();
            }

            
        }
    }
}


?>