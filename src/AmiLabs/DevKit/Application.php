<?php

namespace AmiLabs\DevKit;

use \AmiLabs\DevKit\Request;
use \AmiLabs\DevKit\Registry;
use \AmiLabs\DevKit\Template;

/**
 * Application class.
 */
class Application{
    /**
     * Singeton instance.
     *
     * @var \AmiLabs\DevKit\Application
     */
    protected static $oInstance = null;

    /**
     * @todo Separate config for every application
     * @var Application configuation
     */
    protected $oConfig;

    /**
     * Singleton implementation.
     *
     * @return \AmiLabs\DevKit\Application
     */
    public static function getInstance(){
        if(is_null(self::$oInstance)){
            self::$oInstance = new Application();
        }
        return self::$oInstance;
    }
    /**
     * Runs application.
     */
    public function run(){
        $this->runController();
    }
    /**
     * Returns database object.
     *
     * @return \AmiLabs\DevKit\Database
     */
    public function getDatabase(){
        return null; // Database::getInstance();
    }
    /**
     * Returns template engine.
     *
     * @return \AmiLabs\DevKit\Template
     */
    public function getTemplate(){
        return Template::getInstance();
    }
    /**
     * Returns template object.
     *
     * @return \AmiLabs\DevKit\RequestURI
     */
    public function getRequest(){
        return Request::getInstance(
            $this->oConfig->get('request/type', 'uri')
        );
    }
    /**
     * Returns application configuration.
     *
     * @return mixed
     */
    public function getConfig(){
        return $this->oConfig;
    }
    /**
     * Runs controller.
     *
     * @return boolean
     */
    protected function runController(){
        $oRequest = $this->getRequest();
        $controller = $oRequest->getControllerName();
        $action = $oRequest->getActionName();
        $className = $controller . 'Controller';
        $methodName = 'action' . ucfirst($action); // action + Name
        $fileName = $this->oConfig->get('path/app') . '/controllers/' . $className . '.php';
        if(file_exists($fileName)){
            require_once $fileName;
            if(class_exists($className) && method_exists($className, $methodName)){
                /* @var $oController \AmiLabs\DevKit\Controller */
                $oController = new $className;
                call_user_func(array($oController, $methodName), $this, $oRequest);
                $oTpl = $this->getTemplate();
                $oView = $oController->getView();
                $layout = $oController->getLayoutName();
                $tplFile = $oController->getTemplateFile($controller . '/' . $action);
                $content = $oTpl->get($tplFile, $oView->getScope());
                $oTpl->render($layout, array('content' => $content) + $oView->getGlobalScope());
                return true;
            }else{
                throw new \Exception('Cannot call "' . $className . '::' . $methodName . '"');
            }
        }else{
            throw new \Exception('File "' . $fileName . '" not found');
        }
        return false;
    }
    /**
     * Constructor.
     */
    protected function __construct(){
        $this->oConfig = Registry::useStorage('CFG');
    }
}