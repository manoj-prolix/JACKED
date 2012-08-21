<?php

    abstract class SyrupDriverInterface{
        
        public static function __callStatic($method, $params){
            if (!preg_match('/^(find|findOne|count)By(w+)$/', $method, $matches)) {
                throw new Exception("Call to undefined method {$method}");
            }
     
            $criteriaKeys = explode('_And_', preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $matches[2]));
            $criteriaKeys = array_map('strtolower', $criteriaKeys);
            $criteriaValues = array_slice($params, 0, count($criteriaKeys));
            $criteria = array_combine($criteriaKeys, $criteriaValues);
     
            $method = $matches[1];
            return static::$method($criteria);
        }

        public static function findOne($criteria = array(), $order = null){
            $objects = static::find($criteria, $order, 1, 0);
            return count($objects) == 1 ? $objects[0] : null;
        }

        abstract public function find($criteria = array(), $order = null, $limit = null, $offset = 0);

        abstract public function count($criteria = array());

        abstract public function save();

        abstract public function delete();

    } 

?>