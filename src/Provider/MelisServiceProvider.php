<?php
namespace MelisPlatformFrameworkSilex\Provider;

use MelisPlatformFrameworkSilex\Service\MelisServices;
use MelisPlatformFrameworkSilex\Service\MelisSilexToolCreatorService;
use MelisPlatformFrameworkSilex\Twig\Extension\MelisViewHelperTwigExtension;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Twig\TwigFunction;

class MelisServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    /**
     *
     * A Silex provider that allows Silex to use the Melis Services
     *
     * @param Container $app
     */
    public function register(Container $app)
    {
        // Initializing Service called
        $app['melis.services'] = function ($app) {
            $melisServiceAdaptor = new MelisServices();
            return $melisServiceAdaptor;
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
        /**
         * DATABASE CONFIGURATION
         * Configuring Silex DB using Melis Platform DB configurations.
         */
        #Getting DB configurations from Melis Platform
        $dbConfig = include __DIR__ .  '/../../../../../config/autoload/platforms/' . getenv('MELIS_PLATFORM') . '.php';
        $dsn = str_getcsv($dbConfig['db']['dsn'],";");
        foreach ($dsn as $key => $config){
            if(strpos($config, ':') !== false)
                $data = explode("=",explode(":",$config)[1]);
            else
                $data = explode("=",$config);

            $dbConfig['db'][$data[0]] = $data[1];
        }

        #Getting pre configured DB configurations
        $dbObtions = isset($app['db.options']) ? $app['db.options'] : (isset($app['dbs.options']) ? $app['dbs.options'] : []);

        #Preparing DB configurations from the Melis Platform
        $melisDBOptions = array(
            'melis' => array(
                'driver'   => 'pdo_mysql',
                'host'      => $dbConfig['db']['host'],
                'dbname'    => $dbConfig['db']['dbname'],
                'user'      => $dbConfig['db']['username'],
                'password'  => $dbConfig['db']['password'],
                'charset'   => $dbConfig['db']['charset'],
            )
        );

        if (count($dbObtions) == count($dbObtions, COUNT_RECURSIVE)){
            #Merging Silex DB Configuration if Silex has SINGLE DB configuration
            $melisDBOptions['silex'] = $dbObtions;
        }else{
            #Merging Silex DB Configuration if Silex has MULTIPLE DB configuration
            foreach(array_reverse($dbObtions[0],true) as $key => $dbObtion){
                $melisDBOptions[$key] = $dbObtion;
            }
        }
        $melisDBOptions = array_reverse($melisDBOptions);
        $app['dbs.options'] = $melisDBOptions;

        //the block of code below is for adding Melis Zend View Helpers as a Twig Function Extension.
        $app->extend('twig', function($twig, $app) {
            $twig->addExtension( new MelisViewHelperTwigExtension($app));
            return $twig;
        });

        /**
         * ROUTING CONFIGURATIONS
         * Silex routing for Tool Creator.
         */
        $app->get('/melis/silex-module-create', function () use ($app) {
            $melisSilexToolCreatorSvc = new MelisSilexToolCreatorService($app);
            $melisSilexToolCreatorSvc->createTool();

            return new JsonResponse(array(
                'success' => true
            ));
        });
    }
}