<?php

namespace Phifty\Routing;

use Exception;
use InvalidArgumentException;
use ReflectionObject;
use Symfony\Component\Yaml\Yaml;
use Pux\Controller\ExpandableController;
use Pux\Expandable;
use Phifty\Kernel;

class Controller extends ExpandableController
{
    /**
     * @var Phifty\View view object cache
     */
    protected $_view;

    protected $kernel;

    public $defaultViewClass;

    public function context(array & $environment, array $response)
    {
        parent::context($environment, $response);
        $this->kernel = $environment['phifty.kernel'];
        $this->init();
    }

    protected function init()
    {

    }



    public function __get($name)
    {
        if ('request' === $name) {
            return $this->getRequest();
        }
        throw new InvalidArgumentException("property '{$name}' is undefined.");
    }

    public function getCurrentUser()
    {
        return $this->kernel->currentUser;
    }

    /**
     * some response header util methods
     */
    public function setHeader($field, $value)
    {
        $this->response[1][ $field ] = $value;
    }


    /**
     * response header util method let you append headers.
     *
     * @param string $field
     * @param string $value
     */
    public function appendHeader($field, $value)
    {
        $this->response[1][] = [$field => $value];
    }

    /**
     * Create/Get view object with rendering engine options
     *
     * @param array $options
     *
     * @return Phifty\View
     */
    public function view()
    {
        if ($this->_view) {
            return $this->_view;
        }


        // call the view object factory from service
        return $this->_view = $this->kernel->getObject('view', [$this->kernel, $this->defaultViewClass]);
    }

    /**
     * Create view object with custom view class
     *
     * @param string $class
     * @param array  $options
     */
    public function createView($viewClass = null)
    {
        return $this->kernel->getObject('view', [$this->kernel, $viewClass]);
    }

    /**
     * Web utils functions
     * */
    public function redirect($url)
    {
        // $this->setHeader('Location', $url);
        return [302, [ "Location: " . $url ], []];
    }

    public function redirectLater($url,$seconds = 1 )
    {
        return [302, ["Refresh" => "$seconds ;url=$url" ], []];
        // $this->setHeader('Refresh', "$seconds; url=$url");
    }

    /* Move this into Agent class */
    public function isMobile()
    {
        $agent = $_SERVER['HTTP_USER_AGENT'];
        return preg_match( '/(ipad|iphone|android)/i' ,$agent );
    }

    /**
     * Tell browser dont cache page content
     */
    public function setHeaderNoCache()
    {
        // require HTTP/1.1
        $this->setHeader("Cache-Control", "no-cache, must-revalidate");
    }

    /*
     * Set cache expire time
     */
    public function setHeaderCacheTime( $time = null )
    {
        $datestr = gmdate(DATE_RFC822, $time );
        // header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
        header("Expires: $datestr");
    }

    public function toYaml($data, $encodeFlags = null)
    {
        if (extension_loaded('yaml') ){
            $body = yaml_emit($data, $encodeFlags);
        } else {
            $body = Yaml::dump($data, $encodeFlags);
        }
        return [200, [ 'Content-type: application/yaml; charset=UTF-8;' ], $body ];
    }

    public function error($code, $message)
    {
        return [ $code, ['Content-Type: text/html;'], $message];
    }

    /**
     * Render template directly.
     *
     * @param string $template template path, template name
     * @param array  $args     template arguments
     * @return string rendered result
     */
    public function render($template, array $args = array())
    {
        $view = $this->view();
        $view->assign($args);
        return [200, ['Content-Type: text/html;'], $view->render($template)];
    }

    /**
     * Set HTTP status to 403 forbidden with message
     *
     * @param string $msg
     */
    public function forbidden($msg = '403 Forbidden')
    {
        /* XXX: dirty hack this for phpunit testing */
        if ( ! CLI ) {
            header('HTTP/1.1 403 Forbidden');
        }
        echo $msg;
        exit(0);
    }
}
