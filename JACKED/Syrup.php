<?php
    /*
        Not entirely unlike ORM.
    */

    class Syrup extends JACKEDModule{

        const moduleName = 'Syrup';
        const moduleVersion = 1.0;
        public static $dependencies = array();

        //constants for public use
        //non-magic type
        const OBJECT = 'object';
        //for defining the automagical relations
        const CONTENT_OBJECT = 'content'; //will automagically have CONTENT_META data object relations connected
        const CONTENT_META = 'meta';      //will automagically connect to CONTENT_OBJECTs 

        private $registeredModels;

        public function __construct($JACKED){
            JACKEDModule::__construct($JACKED);

            $this->registeredModels = array();

            //import the base classes and correct SyrupDriver based on the driver name
            if(!class_exists('SyrupDriverInterface', false)){
                include(JACKED_MODULES_ROOT . $this->config->driver_root . 'SyrupDriverInterface.php');
                include(JACKED_MODULES_ROOT . $this->config->driver_root . $this->config->storage_driver_name . '.php');
            }
            if(!class_exists('SyrupModel', false)){
                include(JACKED_MODULES_ROOT . $this->config->model_root . 'SyrupModel.php');
            }
        }

        /**
         * Make the registered modules accessible directly as properties of the class
         */
        public function __get($module){
            if(array_key_exists($module, $this->registeredModels)){
                return $this->registeredModels[$module];
            }else{
                if($this->config->lazy_load_all){
                    try{
                        $this->registerModule($module);
                        return $this->registeredModels[$module];
                    }catch(Exception $ex){
                        throw new UnknownModelException($module, 0, $ex);
                    }
                }
            }
        }
        /**
         * We don't want to allow setting of properties
         */
        public function __set($key, $val){
            
        }

        /**
        * Register a Module with Syrup. Loads the content models, 
        * registers the module, and sets up any relations to other registered modules.
        * 
        * @param $moduleName String The full (case-sensitive) class name of the Module to register
        */
        private function registerModule($moduleName){
            try{
                if(!class_exists($moduleName . 'Model', false)){
                    include(JACKED_MODULES_ROOT . $this->config->model_root . $moduleName . '.php');
                }
            }catch(Exception $e){
                throw new UnknownModelException($moduleName, 0, $e);
            }
            $modelName = $moduleName . 'Model';
            $this->registeredModels[$moduleName] = new $modelName($this->config->driverConfig, $this->JACKED->Logr, $this->JACKED->Util, $modelName);
            $thingy = $this->registeredModels[$moduleName];
        }

        /**
        * Register a Model (not a module) with Syrup. This method accepts an already instantiated model object 
        * and adds it to registered models.
        * 
        * @param $model SyrupModel The model object to be registered 
        * @param $modelName String The full (case-sensitive) class name of the Model to be registered
        * @throws ModelAlreadyExistsException If the given name has already been registered
        */
        public function registerModel($model, $modelName){
            if(array_key_exists($modelName, $this->registeredModels)){
                throw new ModelAlreadyExistsException($modelName);
            }
            $this->registeredModels[$modelName] = $model;
        }
    }


    class UnknownModelException extends Exception{
        public function __construct($name, $code = 0, Exception $previous = null){
            $message = "Could not find a model named: `$name`.";
            
            parent::__construct($message, $code, $previous);
        }
    }

    class ModelAlreadyExistsException extends Exception{
        public function __construct($name, $code = 0, Exception $previous = null){
            $message = "Model named `$name` has already been registered.";
            
            parent::__construct($message, $code, $previous);
        }
    }
?>