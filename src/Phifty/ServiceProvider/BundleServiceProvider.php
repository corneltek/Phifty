<?php
namespace Phifty\ServiceProvider;
use Phifty\BundleManager;

class BundleServiceProvider
    implements ServiceProvider
{

    public function getId() { return 'Bundle'; }

    /**
     *
     * @param Phifty\Kernel $kernel  Kernel object.
     * @param array         $options Plugin service options.
     */
    public function register($kernel, $options = array() )
    {
        // here we check bundles stash to decide what to load.
        $config = $kernel->config->get('framework','Bundles');
        if ( ! $config || $config->isEmpty() ) {
            return;
        }

        // plugin manager depends on classloader,
        // register plugin namespace to classloader.
        $kernel->bundles = function() use ($kernel, $config, $options) {
            $manager = new BundleManager($kernel);
            $paths = array();
            if (isset($options["Paths"])) {
                foreach ($options["Paths"] as $dir) {
                    $paths[] = PH_APP_ROOT . DIRECTORY_SEPARATOR . $dir;
                    $manager->registerBundleDir(PH_APP_ROOT . DIRECTORY_SEPARATOR . $dir);
                }
            }
            foreach ($config as $bundleName => $bundleConfig) {
                $kernel->classloader->addNamespace(array(
                    $bundleName => $paths,
                ));
                $manager->load($bundleName, $bundleConfig);
            }
            return $manager;
        };
        $kernel->bundles->init();
    }

}
