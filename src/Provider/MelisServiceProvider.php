<?php
namespace MelisPlatformFrameworkSilex\Provider;

use MelisPlatformFrameworkSilex\MelisServices;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class MelisServiceProvider implements ServiceProviderInterface
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

}