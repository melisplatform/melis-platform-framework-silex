<?php
namespace MelisPlatformFrameworkSilex;

use Zend\Mvc\Service\ServiceManagerConfig;
use Zend\ServiceManager\ServiceManager;

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
        $serviceManagerConfig = isset($appConfig['service_manager']) ? $appConfig['service_manager'] : [];
        // init service manager
        $serviceManager = new ServiceManager(new ServiceManagerConfig($serviceManagerConfig));
        // set service application config
        $serviceManager->setService('ApplicationCOnfig', $appConfig);
        // load modules
        $serviceManager->get('ModuleManager')->loadModules();
        return $serviceManager;
    }
}