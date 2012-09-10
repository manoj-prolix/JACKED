<?php

    /**
     * Syrup Driver for MySQL
     */

    class SyrupDriver extends SyrupDriverInterface{

        private $_mysql_link = NULL;
        private $_config;
        private $_logr;

        public function __construct($config, $logr){
            $this->_config = $config;
            $this->_logr = $logr;
        }

        public function __destruct(){
            if($this->isLinkOpen()){
                try{
                    mysql_close($this->_mysql_link);
                }catch(Exception $e){}
                $this->_mysql_link = NULL;
            }
        }

        /**
        * Checks if the MySQL link is open.
        * 
        * @param $link int [optional] MySQL Link ID to check. Defaults to default link.
        * @return Boolean Whether the link is active.
        */
        private function isLinkOpen($link = NULL){
            $link = $link? $link : $this->_mysql_link;
            return ($this->_mysql_link == NULL)? false : true;
        }
        
        /**
        * Opens a new link to MySQL.
        * 
        * @param $setDefault Boolean [optional] Whether to make the new link the default link. Defaults to true.
        * @return int MySQL Link ID that was just opened.
        */
        private function openLink($setDefault = true){
            $link = mysql_connect($this->_config['db_host'], $this->_config['db_user'], $this->_config['db_pass']);
            mysql_select_db($this->_config['db_name']);
            if($setDefault){
                $this->_mysql_link = $link;
            }
            return $link;
        }
        
        /**
        * Returns the default link. If it's not open, opens it then returns the link.
        * 
        * @return int The default MySQL Link ID.
        */
        private function getLink(){
            if($this->isLinkOpen()){
                return $this->_mysql_link;
            }else{
                return $this->openLink();
            }
        }

        /**
        * Sanitize a string to be safe for use in MySQL queries.
        * TODO: add even better sanitization
        * 
        * @param $value String Value to sanitize.
        * @param $link int [optional] MySQL Link ID to use. Defaults to default link. Opens new default if necessary.
        * @return String Sanitized version of the input string.
        */
        private function sanitize($value, $link = NULL){
            $link = $link? $link : $this->getLink();
            return mysql_real_escape_string(stripslashes($value), $link);
        }

        /**
        * Get the MySQL LIMIT clause to use for paginating a query.
        * 
        * @param $rows int Number of rows/values per page.
        * @param $page int Page number to get values for.
        * @return String MySQL LIMIT clause.
        */
        private static function paginator($howMany, $page){
            return " LIMIT " . ($howMany * ($page - 1)) . ", " . $howMany;
        }

        private function parseWhereCriteria($criteria){
            $result = "";
            foreach($criteria as $key => $value){
                if(trim($key) == "OR" || trim($key) == "AND"){
                    $result .= trim($key) . " (" . $this->parseWhereCriteria($value) . ") ";
                }else if(is_array($value)){
                    $results = array();
                    foreach($value as $innerkey => $innerval){
                        $results[] = $this->parseWhereCriteria(array($innerkey => $innerval));
                    }
                    $result .= " ( " . implode(" AND ", $results) . " ) ";
                }else{
                    if(is_numeric($value)){
                        $value = strval($value);
                    }else if(is_bool($value)){
                        $value = ($value)? '1' : '0';
                    }
                    $result .= str_replace(array('*', '?'), array('%', str_replace('*', '%', $value)), trim($key)) . " ";
                }
            }

            return $result;
        }
        
        /**
        * Generates the WHERE clause of a query based on an array of field/value pairs.
        * 
        * @param $criteria Array String field/value pairs.
        * @return String MySQL WHERE clause.
        */
        private function getWhereClause($criteria){
            //accept JSON formatted criteria
            if(is_string($criteria)){
                $criteria = json_decode($criteria);
            }

            return "WHERE " . $this->parseWhereCriteria($criteria);
        }

        /**
        * Get the string value of the last MySQL error on the given link.
        * 
        * @param $link int [optional] MySQL Resource ID to identify database connection to use. Defaults to default link.
        * @return String Last error message from the database connection identified by the link
        */
        private function getError($link = NULL){
            $link = $link? $link : $this->getLink();
            $err = mysql_error($link);
            return $err;
        }

        /**
        * Perform a MySQL query on the given database connection, and return the result identifier. 
        * For internal use only, doesn't handle any sanitization.
        * 
        * @param $query String query to perform
        * @param $link int [optional] MySQL Resource ID to identify database connection to use. Defaults to default link
        * @return Array List of all rows returned by @query, or false if none were returned or an error occurred.
        */
        private function mysqlQuery($query, $link = NULL){
            $link = $link? $link : $this->getLink();
            $this->_logr->write($query, Logr::LEVEL_NOTICE, NULL);
            $result = mysql_query($query, $link);
            if($result === true){
                $value = true;
            }else if($result === false){
                $value = false;
            }else if(mysql_num_rows($result) > 0){
                $value = array();
                while($row = mysql_fetch_array($result, MYSQL_BOTH)){
                    $value[] = array_map("stripslashes", $row);
                }
                mysql_free_result($result);
            }else{
                $value = false;
                $this->_logr->write($this->getError($link), Logr::LEVEL_WARNING, NULL);
            }
            
            return $value;
        }

        /**
        * Perform a MySQL query on the given database connection, and return the result identifier. 
        * 
        * @param $query String query to perform
        * @param $link int [optional] MySQL Resource ID to identify database connection to use. Defaults to default link
        * @return Array List of all rows returned by @query, or false if none were returned or an error occurred.
        */
        public function query($query, $link = NULL){
            $query = $this->sanitize($query);
            return $this->mysqlQuery($query, $link);
        }

        public function find($criteria = array(), $order = null, $limit = null, $offset = 0){
            foreach($criteria as $field=>$value)
            $query = "SELECT * FROM " . static::tableName;
            $query .= " " . self::getWhereClause($criteria);
            if($order){
                $query .= " ORDER BY " . $order['field'] . $order['direction'];
            }
            if($limit){
                $query .= " LIMIT " . $limit;
            }
            if($offset){
                $query .= " OFFSET " . $offset;
            }
            $data = $this->query($query);
            $results = array();
            foreach($data as $row){
                $classname = get_class($this);
                $results[] = new $classname($row);
            }

            return $results;
        }

        public function count($criteria = array()){
            $query = "SELECT COUNT(" . $this->getPrimaryKeyName() . ") AS count FROM " . static::tableName . " " . self::getWhereClause($criteria);
            $done = $this->query($query);
            return $done[0]['count'];
        }

        public function save(){
            if($this->_isDirty){
                if($this->_isNew){
                    $insertFields = array();
                    $insertValues = array();
                    foreach($this->getFields() as $name => $val){
                        $insertFields[] = $name;
                        $insertValues[] = $val;
                    }
                    $query = "INSERT INTO " . static::tableName . " (`" . implode('`, `', $insertFields) . "`) VALUES ('" . implode('\', \'', $insertValues) . "')";
                }else{
                    $query = "UPDATE " . static::tableName . " SET ";
                    foreach($this->getFields() as $name => $val){
                        $query .= "`$name` = '$val'";
                    }
                    $query .= " WHERE " . $this->getPrimaryKeyName() . " = '" . $this->_primaryKey['field']  . "'";
                }

                $done = $this->query($query);
                $this->_isDirty = false;

                return $done;
            }else{
                return true;
            }
        }

        public function delete(){
            if(!$this->_isNew){
                $query = "DELETE FROM " . static::tableName . " WHERE " . $this->getPrimaryKeyName() . " = '" . $this->_primaryKey['field']  . "'";
                $done = $this->query($query);
            }else{
                $done = true;
            }

            foreach($this->getFields() as $field){
                $this->$field = NULL;
            }

            $this->_isNew = true;

            return $done;
        }

    }

?>