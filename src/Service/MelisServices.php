<?php
namespace MelisPlatformFrameworkSilex\Service;

use Laminas\Mvc\Application;
use Laminas\ServiceManager\ServiceManager;

class MelisServices
{
    /**
     * Get common services on melis-platform modules
     *  you can find them on every melis-modules
     *  ex. \melis-core\config\module.config.php
     *       - under [service_manager] key
     *
     * @param $serviceName
     * @return array|object
     */
    public function getService($serviceName)
    {
        return $this->initServiceManager()->get($serviceName);
    }
    /**
     *  Get melis platform application config
     * @return string
     */
    protected function getMelisAppPathConfig()
    {
        return include $_SERVER['DOCUMENT_ROOT'] . "/../config/application.config.php";
    }
    /**
     * Get Melis Back office module load
     * @return mixed
     */
    protected  function getMelisBOModuleLoad()
    {
        return  include $_SERVER['DOCUMENT_ROOT'] . "/../config/melis.module.load.php";
    }
    /**
     *
     * @return ServiceManager
     */
    protected function initServiceManager()
    {
        $appConfig = $this->getMelisAppPathConfig();

        $serviceMnager = null;
        // get the zend application
        $zendApplication = Application::init($appConfig);

        return $zendApplication->getServiceManager();
    }
}