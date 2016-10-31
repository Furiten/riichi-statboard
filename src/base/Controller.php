<?php

require_once 'scripts/base/Layout.php';
require_once 'scripts/base/Db.php';

abstract class Controller
{
    protected $_url;
    protected $_path;
    public function __construct($url, $path)
    {
        $this->_url = $url;
        $this->_path = $path;
    }

    public function run()
    {
        if ($this->_beforeRun()) {
            layout::init();
            $this->_run();
            layout::show();
        }
        $this->_afterRun();
    }

    abstract protected function _run();

    protected function _beforeRun()
    {
        return true;
    }

    protected function _afterRun()
    {
    }

    /**
     * @param $url
     * @return Controller
     * @throws Exception
     */
    public static function makeInstance($url)
    {
        $routes = require_once 'config/routes.php';
        $matches = [];
        foreach ($routes as $regex => $controller) {
            if (preg_match('#^' . $regex . '$#', $url, $matches)) {
                require_once 'scripts/controllers/' . $controller . '.php';
                return new $controller($url, $matches);
            }
        }
        throw new Exception('No available controller found for this URL');
    }
}
