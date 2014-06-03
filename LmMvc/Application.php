<?php
namespace LmMvc;

use LmMvc\Exception\ControllerException;
use LmMvc\Exception\MalformedUriException;

/**
 * Class Application
 *
 * In order for the web application to run, you must create an instance of an Application and then setup everything.
 * You can see more information about how to do this in the wiki: https://github.com/ianaldrighetti/lmmvc/wiki.
 *
 * @package LmMvc
 */
class Application
{
    /**
     * The namespace that all the controllers belong to.
     * @var string
     */
    private $controllerNamespace;

    /**
     * The request URI (for testing purposes).
     * @var string
     */
    private $requestUri;

    /**
     * The default controller.
     * @var string
     */
    private $defaultController;

    /**
     * Sets up the application with the namespace the controllers belong to.
     *
     * @param string $defaultController The default controller to use if none is specified in the request URI (do not
     *                                  include the namespace).
     * @param string $controllerNamespace The controller namespace.
     */
    public function __construct($defaultController, $controllerNamespace)
    {
        $this->setRequestUri(null);
        $this->setDefaultController($defaultController);
        $this->setNamespace($controllerNamespace);
    }

    /**
     * Returns the namespace that the controllers belong to.
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->controllerNamespace;
    }

    /**
     * Sets the namespace that the controllers belong to.
     *
     * @param string $controllerNamespace
     */
    public function setNamespace($controllerNamespace)
    {
        // If it has a trailing \, remove it.
        if (substr($controllerNamespace, -1, 1) == '\\')
        {
            $controllerNamespace = substr($controllerNamespace, 0, strlen($controllerNamespace) - 1);
        }

        $this->controllerNamespace = $controllerNamespace;
    }

    /**
     * Returns the current request URI. If the request URI was not set explicitly, this will be $_SERVER['REQUEST_URI'].
     *
     * @return string
     */
    public function getRequestUri()
    {
        return is_null($this->requestUri) ? $_SERVER['REQUEST_URI'] : $this->requestUri;
    }

    /**
     * Sets the request URI, for testing purposes. Must begin with a forward slash.
     *
     * @param string $requestUri The request URI, i.e. /controller/action.
     */
    public function setRequestUri($requestUri)
    {
        if (substr($requestUri, 0, 1) != '/')
        {
            $requestUri = '/'. $requestUri;
        }

        $this->requestUri = $requestUri;
    }

    /**
     * Returns the default controller.
     *
     * @return string
     */
    public function getDefaultController()
    {
        return $this->defaultController;
    }

    /**
     * The default controller to use when the request URI doesn't specify one.
     *
     * @param string $defaultController
     */
    public function setDefaultController($defaultController)
    {
        $this->defaultController = $defaultController;
    }

    /**
     * Runs the application by inspecting the request URI and determining which controller to load and which method to
     * invoke.
     */
    public function run()
    {
        // Determine the request URI.
        $requestUri = $this->getRequestUri();

        // Now we need to determine the controller that is being used, the method to be invoked and the query string.
        $app = $this->getController($requestUri);

        // Now we need to check to make sure that the request URI matches how it should appear ideally.
        $this->compareRequestUri($requestUri, $app);

        // Now we need to load the controller.
        $controller = $this->getControllerInstance($app['controller']);
    }

    /**
     * Creates an instance of the specified controller. It must inherit BaseController or an exception will be thrown.
     * 
     * @param string $controllerName The name of the controller to create an instance of.
     * @throws Exception\ControllerException
     * @return BaseController
     */
    public function getControllerInstance($controllerName)
    {
        $controllerName = $this->getNamespace(). '\\'. $controllerName;

        // TODO: Register an error handler to determine if autoloading failed (then restore the old one, of course!).
        // Now, create an instance of the controller. At least, attempt to.
        $controller = new $controllerName();

        // It must extend BaseController.
        if (!is_subclass_of($controller, '\\LmMvc\\BaseController'))
        {
            throw new ControllerException(
                sprintf('The controller "%s" could not be autoloaded.', htmlspecialchars($controllerName))
            );
        }
        // Otherwise, return it.
        return $controller;
    }

    /**
     * Determines the request URI matches what it should be ideally. If it does not match a redirect will occur.
     *
     * @param string $requestUri
     * @param array $app
     */
    public function compareRequestUri($requestUri, array $app)
    {
        // Lower case the controller and the method name.
        $app['controller'] = strtolower($app['controller']);
        $app['method'] = strtolower($app['method']);

        //  What should it be, ideally?
        $idealUri = '/'. $app['controller']. '/'. $app['method'].
            (strlen($app['query_string']) > 0 ? '?'. $app['query_string'] : '');

        // Does it match?
        if ($requestUri == $idealUri)
        {
            // It's all good!
            return;
        }

        // If the controller is the default controller, it can be omitted.
        $idealUri = '/'. $app['method']. (strlen($app['query_string']) > 0 ? '?'. $app['query_string'] : '');

        // Make sure the controller is the default one and that they match.
        if (strtolower($this->getDefaultController()) == $app['controller'] && $requestUri == $idealUri)
        {
            return;
        }

        // Finally, it could be completely empty (we're going to require that anything with a query string contain
        // the method name, at least)...
        $idealUri = '/';

        if (strtolower($this->getDefaultController()) == $app['controller'] && 'index' == $app['method'] &&
            $requestUri == $idealUri)
        {
            return;
        }

        // It looks like nothing worked, so...
        $idealUri = (strtolower($this->getDefaultController()) != $app['controller'] ? '/'. $app['controller'] : '').
            '/'. $app['method']. (strlen($app['query_string']) > 0 ? '?'. $app['query_string'] : '');

        // Now, redirect!
        $this->redirect($idealUri);
    }

    /**
     * Redirects to the specified URI.
     *
     * @param string $uri The URI to redirect to.
     * @param int $status The status to send (301 for Moved Permanently or 307 for Temporary Redirect).
     */
    public function redirect($uri, $status = 301)
    {
        // Did we send anything yet?
        if(ob_get_length() > 0)
        {
            // Well, if there are any.
            @ob_clean();
        }

        // We only accept 301 or 307.
        if (!in_array($status, array(301, 307)))
        {
            // Default to temporary.
            $status = 307;
        }

        // We may need to change the status if we are getting POST data...
        $status = count($_POST) > 0 ? 303 : (int)$status;

        // Output the right header.
        switch($status)
        {
            case 301:
                header('HTTP/1.0 301 Moved Permanently');
                break;

            case 303:
                header('HTTP/1.1 303 See Other');
                break;

            case 307:
                header('HTTP/1.1 307 Temporary Redirect');
                break;
        }

        // Don't cache this!
        header('Cache-Control: no-cache');

        // Now redirect to the location of your desire!
        header('Location: '. $uri);

        // Don't do anything else.
        exit;
    }

    /**
     * Returns an array containing the following indices: controller (string), method (string) and query_string
     * (string).
     *
     * @param string $requestUri The request URI to parse.
     * @throws Exception\MalformedUriException
     * @return array
     */
    public function getController($requestUri)
    {
        // It must start with a slash.
        if (substr($requestUri, 0, 1) != '/')
        {
            throw new MalformedUriException(
                sprintf('The request URI "%s" is malformed and could not be parsed.', htmlspecialchars($requestUri))
            );
        }

        // Let's parse out the query string. That's the easiest.
        $queryString = '';
        if (strpos($requestUri, '?') !== false)
        {
            list($requestUri, $queryString) = explode('?', $requestUri, 2);
        }

        // Set up our result array.
        $app = array(
            'controller' => null,
            'method' => null,
            'query_string' => $queryString
        );

        // Yes, we require it start with a slash, but now we're going to remove it.
        $requestUri = substr($requestUri, 1);

        // Let's get all the components.
        $components = explode('/', $requestUri, 2);

        // Determine the method. If the second component is empty, then that means there is no controller defined.
        $method = $this->cleanMethodName(
            isset($components[1]) ? $components[1] : (isset($components[0]) ? $components[0] : 'index')
        );

        // Now for the controller. We may need to use the default.
        $controller = isset($components[1]) ? $components[0] : $this->getDefaultController();

        // Now set the controller and method.
        $app['controller'] = trim($controller);
        $app['method'] = trim($method);

        // Alright... Is the controller name valid?
        if (!$this->isClassMethodNameValid($app['controller']))
        {
            throw new MalformedUriException(
                sprintf(
                    'The request URI was malformed: the controller "%s" is invalid.',
                    htmlspecialchars($app['controller'])
                )
            );
        }

        // Same check for the method.
        if (!$this->isClassMethodNameValid($app['method']))
        {
            throw new MalformedUriException(
                sprintf(
                    'The request URI was malformed: the method "%s" is invalid.',
                    htmlspecialchars($app['method'])
                )
            );
        }

        // Otherwise it's all good.
        return $app;
    }

    /**
     * Determines whether the class/method name is valid.
     *
     * @param string $name
     * @return bool
     */
    public function isClassMethodNameValid($name)
    {
        // Regular expression source: http://php.net/manual/en/language.oop5.basic.php.
        return preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name) !== 0;
    }

    /**
     * Returns a cleaned up method name (removes an allowed additional /, but if there are more than one an exception is
     * thrown).
     *
     * @param string $methodName
     * @throws Exception\MalformedUriException
     * @return string
     */
    public function cleanMethodName($methodName)
    {
        $methodName = trim($methodName);
        if (strpos($methodName, '/') === false)
        {
            return $methodName;
        }

        // We will allow a / if there is one and if it's at the end.
        if (substr_count($methodName, '/') > 1 || substr($methodName, -1, 1) != '/')
        {
            throw new MalformedUriException(
                sprintf(
                    'The request URI was malformed: the method "%s" is invalid.',
                    htmlspecialchars($methodName)
                )
            );
        }

        // Remove the /.
        return substr($methodName, 0, strpos($methodName, '/'));
    }
}
