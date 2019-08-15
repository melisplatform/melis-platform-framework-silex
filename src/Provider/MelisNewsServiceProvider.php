<?php
namespace MelisPlatformFrameworkSilex\Provider;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Application;
use Zend\Mvc\Service\ServiceManagerConfig;
use Zend\ServiceManager\ServiceManager;

class MelisNewsServiceProvider implements BootableProviderInterface,ServiceProviderInterface
{

    public function register(Container $app)
    {
        # Initializing Service
        $app['MelisNews'] = function ($app) {

            //get zend app config
            $configuration  = include $_SERVER['DOCUMENT_ROOT'] . "/../config/application.config.php";

            //get module list from module load config
            $modules  = include $_SERVER['DOCUMENT_ROOT'] . "/../config/melis.module.load.php";

            //merge loaded modules in app config and module load config
            $configuration['modules'] = array_unique(array_merge($configuration['modules'],$modules), SORT_REGULAR);

            // check for service manager config
            $smConfig       = isset($configuration['service_manager']) ? $configuration['service_manager'] : array();
            // get zend servicemanagers
            $serviceManager =  new ServiceManager(new ServiceManagerConfig($smConfig));
            // set service application config
            $serviceManager->setService('ApplicationConfig', $configuration);
            // load melis modules
            $serviceManager->get('ModuleManager')->loadModules();

            /** @var  $news  \MelisCmsNews\Service\MelisCmsNewsService*/
            $news = $serviceManager->get('MelisCmsNewsService');

            return $news;
        };
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     *
     * @param Application $app
     */
    public function boot(Application $app)
    {
        // TODO: Implement boot() method.
    }
}