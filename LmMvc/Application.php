<?php
namespace LmMvc;

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
    }

    /**
     * Determines the request URI matches what it should be ideally. If it does not match a redirect will occur.
     *
     * @param string $requestUri
     * @param array $app
     */
    public function compareRequestUri($requestUri, array $app)
    {

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
        $method = $this->cleanMethodName(isset($components[1]) ? $components[1] : $components[0]);

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
