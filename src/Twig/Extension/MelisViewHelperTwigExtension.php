<?php declare(strict_types=1);

namespace MelisPlatformFrameworkSilex\Twig\Extension;
use MelisPlatformFrameworkSilex\Service\MelisServices;
use Pimple\Container;

class MelisViewHelperTwigExtension extends \Twig_Extension
{
    /**
     * @var Container
     */
    protected $app;

    /**
     * @param Container $app
     */
    public function __construct(Container $app)
    {
        $this->app = $app;

    }
    /**
     * @inheritDoc
     */
    public function getFunctions(): array
    {
        return [
            'getMelisViewHelpers' => new \Twig_SimpleFunction(
                'getMelisViewHelpers',
                [$this, 'getMelisViewHelpers'],
                ['is_safe' => ['html']]
            )
        ];
    }
    public function getMelisViewHelpers($helperName, $functionName = null, $params = null){
        $melisServiceAdaptor = new MelisServices();
        $viewHelperManager = $melisServiceAdaptor->getService("viewhelpermanager");
        $registerdViewHelpers = $viewHelperManager->getRegisteredServices();
        $zendMelisViewHelpers = $registerdViewHelpers['invokableClasses'];
        $zendMelisViewHelpers = array_merge($zendMelisViewHelpers,$registerdViewHelpers['aliases']);
        $zendMelisViewHelpers = array_merge($zendMelisViewHelpers,$registerdViewHelpers['factories']);

        if(in_array($helperName, $zendMelisViewHelpers)) {
            $helperName = strtolower($helperName);
            $helper = $viewHelperManager->get($helperName);

            if (!empty($functionName)) {
                /**
                 * Check parameters to apply
                 */
                if (!empty($params))
                    return $helper->$functionName($params);
                else
                    return $helper->$functionName();
            }else {
                //get helper method list
                $methods = get_class_methods(get_class($helper));
                /**
                 * If the helper has an __invoke method,
                 * then we execute it
                 */
                $invoke = '__invoke';
                if (in_array($invoke, $methods)) {
                    /**
                     * Check parameters to apply
                     */
                    if (!empty($params))
                        return call_user_func_array($helper, $params);
                    else
                        return $helper();
                } else {
                    return '';
                }
            }
        }else{
            throw new \Exception('Unrecognized helper name: '. $helperName);
        }
    }
    /**
     * Returns the name of the extension
     * @return string The extension name
     */
    public function getName(): string
    {
        return 'getMelisViewHelpers';
    }
}