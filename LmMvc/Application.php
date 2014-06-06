<?php
namespace LmMvc;

use LmMvc\Exception\ControllerException;
use LmMvc\Exception\MalformedUriException;
use LmMvc\Exception\PageNotFound;

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
     * The name of the class that will handle exceptions thrown by LMMVC or the application.
     * @var ExceptionHandler
     */
    private $exceptionHandler;

    /**
     * An instance of the Header Wrapper class.
     * @var HeaderWrapper
     */
    private $headerWrapper;

    /**
     * The callback for properly casing the controller name (upper, lower, camel case, custom, etc.).
     * @var callback
     */
    private $controllerCaserCallback;

    /**
     * Sets up the application with the namespace the controllers belong to.
     *
     * @param string $defaultController The default controller to use if none is specified in the request URI (do not
     *                                  include the namespace).
     * @param string $controllerNamespace The controller namespace.
     */
    public function __construct($defaultController = null, $controllerNamespace = null)
    {
        // Set a couple things to null.
        $this->requestUri = null;
        $this->exceptionHandler = null;
        $this->headerWrapper = null;

        // Set some other things.
        $this->setDefaultController($defaultController);
        $this->setNamespace($controllerNamespace);
        $this->setControllerCaser(array('\\LmMvc\\ControllerCaser', 'camelCaseWithFirstUpper'));
    }

    /**
     * Returns the controller caser callback.
     *
     * @return callable
     */
    public function getControllerCaser()
    {
        return $this->controllerCaserCallback;
    }

    /**
     * Sets the controller casing callback. Any callback should accept one parameter, which is the controller name and
     * it is to return the controller name properly cased.
     *
     * LMMVC provides a ControllerCaser class that offers the following casing methods:
     *      - lowerCase - Lower cases the controller name.
     *      - upperCaseFirst - The first character of the controller name is uppercased.
     *      - camelCase - Converts the controller name into camel case, i.e. my_controller to myController.
     *      - camelCaseWithFirstUpper - Same as camelCase, but also uppercases the first character, so my_controller
     *                                  would become MyController.
     *
     * LMMVC defaults to camelCaseWithFirstUpper.
     *
     * A callback may throw an Exception if the controller name cannot be processed, for whatever reason. This will
     * then cause a PageNotFound Exception to be thrown by Application. For example, camelCase and
     * camelCaseWithFirstUpper throws an Exception if there is more than one underscore together. This is because
     * otherwise the page /my_controller/index and /my__controller/index could both point to the same place. Which isn't
     * something we (or at least I) want.
     *
     * As a final note, all controller names that will be passed to these callbacks have already been validated against
     * the isClassMethodNameValid method. This is a regexp for function, class and method names in PHP.
     *
     * @param callback $callback
     * @throws \InvalidArgumentException
     */
    public function setControllerCaser($callback)
    {
        if (!is_callable($callback))
        {
            throw new \InvalidArgumentException('The argument must be callable.');
        }

        $this->controllerCaserCallback = $callback;
    }

    /**
     * Returns an instance of the Header Wrapper class.
     *
     * @return HeaderWrapper
     */
    public function getHeaderWrapper()
    {
        if (is_null($this->headerWrapper))
        {
            $this->headerWrapper = new HeaderWrapper();
        }

        return $this->headerWrapper;
    }

    /**
     * Sets an instance of a Header Wrapper.
     *
     * @param HeaderWrapper $headerWrapper
     */
    public function setHeaderWrapper(HeaderWrapper $headerWrapper)
    {
        $this->headerWrapper = $headerWrapper;
    }

    /**
     * Returns the exception handler.
     *
     * @return ExceptionHandler|null
     */
    public function getExceptionHandler()
    {
        // If it is null, use the default.
        if (is_null($this->exceptionHandler))
        {
            $this->exceptionHandler = new DefaultExceptionHandler();
        }

        return $this->exceptionHandler;
    }

    /**
     * Sets the exception handler. Must implement the ExceptionHandler interface.
     *
     * @param ExceptionHandler|null $exceptionHandler An instance of an exception handler.
     * @see ExceptionHandler
     */
    public function setExceptionHandler(ExceptionHandler $exceptionHandler)
    {
        $this->exceptionHandler = $exceptionHandler;
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

        // Also make sure that it starts with a slash.
        if (substr($controllerNamespace, 0, 1) != '\\')
        {
            $controllerNamespace = '\\'. $controllerNamespace;
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
     * Runs the application by inspecting the request URI and determining the controller to load, the method to invoke
     * and everything else necessary to get the application going.
     */
    public function run()
    {
        // We put this all into it's own method so we can catch any exceptions generated by the Application class.
        // That way we can isolate them from any exceptions thrown by the application itself (out of our reach).
        $app = null;
        try
        {
            $app = $this->setup();
        }
        catch (\Exception $ex)
        {
            // Show the exception page. This, of course, can be set to a custom handler.
            $this->showExceptionPage($ex, true);
        }

        try
        {
            call_user_func(array($app['controller'], $app['method']), $app['arguments']);
        }
        catch (\Exception $ex)
        {
            // Show the exception page, but note that it is not internal (to LMMVC).
            $this->showExceptionPage($ex, false);
        }
    }

    /**
     * Displays the exception page.
     *
     * @param \Exception $ex The exception that was thrown.
     * @param bool $internal Whether the exception was thrown by LMMVC (if false, it came from the controller
     *                       implementation itself).
     */
    public function showExceptionPage(\Exception $ex, $internal = true)
    {
        // Now we need to get the exception handler.
        $exceptionHandler = $this->getExceptionHandler();

        // Invoke and die.
        $exceptionHandler->handle($ex, !empty($internal));
    }

    /**
     * Runs the application by inspecting the request URI and determining which controller to load and which method to
     * invoke.
     *
     * @throws Exception\MalformedUriException
     * @throws Exception\ControllerException
     * @throws Exception\PageNotFound
     * @return array
     */
    private function setup()
    {
        // Determine the request URI.
        $requestUri = $this->getRequestUri();

        // Now we need to determine the controller that is being used, the method to be invoked and the query string.
        $app = $this->getController($requestUri);

        // We broke $_GET. Let's fix it.
        $this->fixGetGlobal($app['query_string']);

        // Now we need to check to make sure that the request URI matches how it should appear ideally.
        $this->compareRequestUri($requestUri, $app);

        // Now we need to load the controller.
        $controller = $this->getControllerInstance($app['controller']);

        // Now that we have the controller, let's find out if the action (method) we want to execute is valid.
        $methodObject = $this->getMethodObject($controller, $app['method']);

        // Alright, if no exception was thrown (which we wouldn't be here right now if one was), we can continue on.
        // We will check the method for any parameters, if there are any we will check the query string if there are
        // any matching variables and we will pass that to the method when we invoke it.
        $methodArgs = $this->getMethodArgs($methodObject);

        // Everything is good to go, it seems.
        return array(
            'controller' => $controller,
            'method' => $methodObject->getName(),
            'arguments' => $methodArgs
        );
    }

    /**
     * Returns an array containing the arguments list to use when invoking the method. It will attempt to locate the
     * value of the parameter in the $_GET global variable -- if not found it will default to null (or an empty array
     * if the parameter is expecting an array).
     *
     * @param \ReflectionMethod $method
     * @return array
     */
    public function getMethodArgs(\ReflectionMethod $method)
    {
        $args = array();
        foreach ($method->getParameters() as $param)
        {
            // Get the value. If one is not found, set it to null. But if it is expecting an array, use an empty array.
            $value = array_key_exists($param->getName(), $_GET) ? $_GET[$param->getName()] :
                ($param->isArray() ? array() : null);

            // If it's null, check if there was a predefined default. If so, use that.
            if (!array_key_exists($param->getName(), $_GET) && $param->isOptional())
            {
                $value = $param->getDefaultValue();
            }

            // Is it expecting an array?
            if ($param->isArray() && !is_array($value))
            {
                $value = array($value);
            }

            // Add it to our arguments list.
            $args[] = $value;
        }

        return $args;
    }

    /**
     * Because of how the request is routed, the $_GET variable is undoubtedly broken. This fixes that (and $_REQUEST).
     *
     * @param string $queryString
     */
    public function fixGetGlobal($queryString)
    {
        global $_GET, $_REQUEST;

        parse_str($queryString, $_GET);

        // Also fix $_REQUEST.
        $_REQUEST = array_merge($_GET, $_POST);
    }

    /**
     * Returns a ReflectionMethod if the method exists in the controller.
     *
     * @param BaseController $controller
     * @param string $methodName
     * @throws Exception\PageNotFound
     * @return \ReflectionMethod
     */
    public function getMethodObject(BaseController $controller, $methodName)
    {
        $reflector = new \ReflectionClass($controller);

        try
        {
            $methodObject = $reflector->getMethod($methodName);
        }
        catch (\ReflectionException $ex)
        {
            throw new PageNotFound(
                sprintf(
                    'The method "%s" was not found in the "%s" controller.',
                    htmlspecialchars($methodName),
                    htmlspecialchars(get_class($controller))
                )
            );
        }

        return $methodObject;
    }

    /**
     * Creates an instance of the specified controller. It must inherit BaseController or an exception will be thrown.
     *
     * @param string $controllerName The name of the controller to create an instance of.
     * @throws Exception\ControllerException
     * @throws Exception\PageNotFound
     * @return BaseController
     */
    public function getControllerInstance($controllerName)
    {
        // Do a try/catch block. That way the callbacks can tell us if the controller name is really, really bad.
        try
        {
            $controllerName = call_user_func($this->getControllerCaser(), $controllerName);
        }
        catch(\Exception $ex)
        {
            // Throw a Page Not Found exception, along with the message and the previous exception.
            throw new PageNotFound(
                sprintf(
                    'The controller "%s" could not be processed by the controller caser.',
                    htmlspecialchars($controllerName)
                ),
                0,
                $ex
            );
        }

        // Alright, now form the whole name, that includes namespace as well.
        $controllerName = $this->getNamespace(). '\\'. $controllerName;

        // Check if the class can be loaded.
        if (!class_exists($controllerName))
        {
            throw new PageNotFound(
                sprintf('The controller "%s" could not be autoloaded.', htmlspecialchars($controllerName))
            );
        }

        // Now, create an instance of the controller. At least, attempt to.
        $controller = new $controllerName();

        // It must extend BaseController.
        if (!is_subclass_of($controller, '\\LmMvc\\BaseController'))
        {
            throw new ControllerException(
                sprintf('The controller "%s" does not inherit BaseController.', htmlspecialchars($controllerName))
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

        // Get the Header Wrapper (this allows another instance of Header Wrapper to be set for testing purposes).
        $headerWrapper = $this->getHeaderWrapper();

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
                $headerWrapper->setStatusCode(301, 'Moved Permanently', 1.0);
                break;

            case 303:
                $headerWrapper->setStatusCode(303, 'See Other', 1.1);
                break;

            case 307:
                $headerWrapper->setStatusCode(307, 'Temporary Redirect', 1.1);
                break;
        }

        // Don't cache this!
        $headerWrapper->add('Cache-Control', 'no-cache');

        // Now redirect to the location of your desire!
        $headerWrapper->add('Location', $uri);

        // Now send them.
        $headerWrapper->send();

        // If we're testing, don't keep going.
        if (!$headerWrapper->getEnabled())
        {
            return;
        }

        // Exit!
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
            !empty($components[1]) ? $components[1] : (!empty($components[0]) ? $components[0] : 'index')
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
