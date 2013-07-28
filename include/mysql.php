<?php

    class Mysql
    {

        const result = "result";
        const id = "id";
        const num_rows = "#rows";
        const assoc = "assoc";
        const single_assoc = "single assoc";
        const single_field = "single field";
        const single_fields = "single fields";
        const row = "row";
        const single_row = "single row";

        private $username;
        private $password;
        private $hostname;
        private $database;
        private $queries = array();
        private $mysqli = null;

        public function __construct($username = "", $password = "", $hostname = "", $database = "")
        {
            $this->username = $username;
            $this->password = $password;
            $this->hostname = $hostname;
            $this->database = $database;
        }

        public function connect($username = "", $password = "", $hostname = "", $database = "")
        {
            global $cfg;
            $connect = new mysqli(
                    (!empty($hostname) ? $hostname : (!empty($this->hostname) ? $this->hostname : (!empty($cfg["mysql"]["hostname"]) ? $cfg["mysql"]["hostname"] : "localhost")))
                    , (!empty($username) ? $username : (!empty($this->username) ? $this->username : $cfg["mysql"]["username"]))
                    , (!empty($password) ? $password : (!empty($this->password) ? $this->password : $cfg["mysql"]["password"]))
                    , (!empty($database) ? $database : (!empty($this->database) ? $this->database : $cfg["mysql"]["database"]))
            );

            if($connect->connect_error){
                return false;
            }
            else {
                $this->mysqli = $connect;
                return true;
            }
        }

        public function query($query, $return = self::result, $idfield = NULL)
        {
            if($this->mysqli === null){
                $this->connect();
            }

            if($this->mysqli !== null){
                $timer = microtime(true);
                $result = $this->mysqli->query($query);

                $this->queries[] = array(
                    "query" => $query
                    , "timer" => round((microtime(true) - $timer), 4)
                    , "result" => $result
                    , "msg" => (($result === FALSE) ? $this->mysqli->errno.": ".$this->mysqli->error : NULL)
                );

                if($result === FALSE){
                    return false;
                }

                switch(strtolower($return)){
                    case self::id:
                        if(substr($query, 0, 11) === "INSERT INTO"){
                            return $this->mysqli->insert_id;
                        }
                        else {
                            return $result;
                        }
                        break;
                    case self::num_rows:
                        if(substr($query, 0, 6) === "SELECT"){
                            return $result->num_rows;
                        }
                        else {
                            return $this->mysqli->affected_rows;
                        }
                        break;

                    case self::assoc:
                        $return = array();
                        while($data = $result->fetch_assoc()){
                            if($idfield == NULL || !isset($data[$idfield])){
                                $return[] = $data;
                            }
                            else {
                                $return[$data[$idfield]] = $data;
                                unset($return[$data[$idfield]][$idfield]);
                            }
                        }
                        return $return;
                        break;

                    case self::single_assoc:
                        $r = $result->fetch_assoc();
                        return (($r !== NULL) ? $r : NULL);
                        break;

                    case self::single_field:
                        $r = @reset($result->fetch_row());
                        return (($r !== NULL) ? $r : NULL);
                        break;

                    case self::single_fields:
                        $return = array();
                        if($idfield === NULL){
                            while($r = $result->fetch_row()){
                                $return[] = reset($r);
                            }
                        }
                        else {
                            while($r = $result->fetch_assoc()){
                                $key = $r[$idfield];
                                unset($r[$idfield]);
                                $return[$key] = reset($r);
                            }
                        }
                        return $return;
                        break;

                    case self::row:
                        $return = array();
                        while($data = $result->fetch_row()){
                            $return[] = $data;
                        }
                        return $return;
                        break;

                    case self::single_row:
                        $r = $result->fetch_row();
                        return (($r !== NULL) ? $r : NULL);
                        break;

                    default:
                        return $result;
                        break;
                }
            }
            else {
                $this->queries[] = array(
                    "query" => $query
                    , "timer" => 0
                    , "result" => false
                    , "msg" => 'Unable to connect to database.'
                );

                return false;
            }
        }

        public function escape($var)
        {
            if($this->mysqli !== null){
                $this->connect();
            }

            if($this->mysqli !== null){
                return $this->mysqli->real_escape_string($var);
            }
            else {
                var_dump($this);
            }
        }

        public function messages()
        {
            $c = count($this->queries);
            $r = array();
            for($i = 0; $i < $c; $i++){
                if($this->queries[$i]["msg"] !== NULL){
                    $r[] = array("query" => $this->queries[$i]["query"], "msg" => $this->queries[$i]["msg"]);
                }
            }
            return $r;
        }

    }

    $sql = new Mysql("username", "password", "localhost", "SimulEdit");
    $sql->connect();
    
?>