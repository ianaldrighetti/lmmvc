<?php
namespace LmMvcTests;

use LmMvc\Application;
use LmMvc\DefaultExceptionHandler;
use LmMvcTests\Mock\MockController;

/**
 * Class ApplicationTest
 * @package LmMvcTests
 */
class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Application
     */
    private $application;

    /**
     * Creates an instance of Application.
     */
    public function setUp()
    {
        $this->application = new Application();
    }

    /**
     * Ensures that if no exception handler is set that the default one is used instead.
     */
    public function testDefaultExceptionHandler()
    {
        $this->assertInstanceOf('\\LmMvc\\DefaultExceptionHandler', $this->application->getExceptionHandler());
    }

    /**
     * Ensures the exception handler can be set.
     */
    public function testSetExceptionHandler()
    {
        $exceptionHandler = new DefaultExceptionHandler();
        $this->application->setExceptionHandler($exceptionHandler);
        $this->assertEquals($exceptionHandler, $this->application->getExceptionHandler());
    }

    /**
     * Ensures namespaces can be set.
     */
    public function testSetNamespace()
    {
        $namespaceName = '\\This\\Is\\A\\Namespace';
        $this->application->setNamespace($namespaceName);
        $this->assertEquals($namespaceName, $this->application->getNamespace());
    }

    /**
     * Tests to ensure that namespaces that aren't quite right get fixed.
     */
    public function testFixSetNamespace()
    {
        $namespaceWithEndingSlash = '\\This\\Ends\\With\\Slashes\\';
        $namespaceWithNoEndingSlash = '\\This\\Ends\\With\\Slashes';
        $this->application->setNamespace($namespaceWithEndingSlash);
        $this->assertEquals($namespaceWithNoEndingSlash, $this->application->getNamespace());

        $namespaceWithNoStartingSlash = 'This\\Doesnt\\Start\\With\\A\\Slash';
        $namespaceWithStartingSlash = '\\'. $namespaceWithNoStartingSlash;
        $this->application->setNamespace($namespaceWithNoStartingSlash);
        $this->assertEquals($namespaceWithStartingSlash, $this->application->getNamespace());
    }

    /**
     * Ensures that setting a custom request URI works.
     */
    public function testSetRequestUri()
    {
        $customUri = '/my/custom/uri';
        $this->application->setRequestUri($customUri);
        $this->assertEquals($customUri, $this->application->getRequestUri());
    }

    /**
     * Tests to ensure that URI's start with a slash, and adds them if missing.
     */
    public function testFixSetRequestUri()
    {
        $customUri = 'my/custom/uri';
        $this->application->setRequestUri($customUri);
        $this->assertEquals('/'. $customUri, $this->application->getRequestUri());
    }

    /**
     * Tests setting a default controller.
     */
    public function testSetDefaultController()
    {
        $controllerName = 'MyDefaultController';
        $this->application->setDefaultController($controllerName);
        $this->assertEquals($controllerName, $this->application->getDefaultController());
    }

    /**
     * Tests the regexp for method names.
     *
     * @param string $methodName
     * @param bool $isValid
     * @dataProvider provideMethodNames
     */
    public function testIsMethodNameValid($methodName, $isValid)
    {
        $this->assertEquals(!empty($isValid), $this->application->isClassMethodNameValid($methodName));
    }

    /**
     * @return array
     */
    public function provideMethodNames()
    {
        return array(
            array('validMethod', true),
            array('this_is_valid', true),
            array('this1isvalid', true),
            array('this-isnt-valid', false),
            array('1notvalid', false),
            array('definitely!not%valid', false)
        );
    }

    /**
     * Tests Application::cleanMethodName
     */
    public function testCleanMethod()
    {
        $perfectMethodName = 'idontneedanythingdone';
        $this->assertEquals($perfectMethodName, $this->application->cleanMethodName($perfectMethodName));

        $removeSlash = 'justremovetheslash/';
        $slashRemoved = 'justremovetheslash';
        $this->assertEquals($slashRemoved, $this->application->cleanMethodName($removeSlash));
    }

    /**
     * Tests that the cleanMethodName method throws an exception with a method name with more than one slash.
     *
     * @expectedException \LmMvc\Exception\MalformedUriException
     */
    public function testCleanMethodException()
    {
        $moreThanOneSlash = 'method_name/uh-oh/';
        $this->application->cleanMethodName($moreThanOneSlash);
    }

    /**
     * Tests to ensure that the $_GET and $_REQUEST variables are fixed.
     */
    public function testFixGetGlobal()
    {
        $get = array(
            'id' => 1,
            'other' => array(1, 2, 3),
            'name' => 'name',
        );

        // Empty out $_GET and $_REQUEST.
        $GLOBALS['_GET'] = array();
        $GLOBALS['_REQUEST'] = array();
        $GLOBALS['_POST'] = array(
            'test' => 23,
            'another' => 'yes',
        );

        $this->application->fixGetGlobal(http_build_query($get));

        // Check that everything in the query string matches.
        foreach (array('_GET', '_REQUEST') as $variable)
        {
            foreach ($get as $key => $value)
            {
                $this->assertEquals($value, $GLOBALS[$variable][$key]);
            }
        }

        // _REQUEST should be $_GET and $_POST merged.
        $this->assertEquals(array_merge($GLOBALS['_GET'], $GLOBALS['_POST']), $GLOBALS['_REQUEST']);
    }

    /**
     * Tests getting a ReflectionMethod object from the getMethodObject method.
     */
    public function testGetMethodObject()
    {
        $mockController = new MockController();
        $methodObject = $this->application->getMethodObject($mockController, 'methodWithParams');
        $this->assertInstanceOf('\\ReflectionMethod', $methodObject);
    }

    /**
     * Tests to ensure the PageNotFound exception is thrown when a method doesn't exist in a controller.
     *
     * @expectedException \LmMvc\Exception\PageNotFound
     * @expectedExceptionMessage The method "doesntExist" was not found in the "LmMvcTests\Mock\MockController"
     *                           controller.
     */
    public function testGetMethodObjectException()
    {
        $mockController = new MockController();
        $this->application->getMethodObject($mockController, 'doesntExist');
    }

    /**
     * Tests getting the method arguments array for a reflected method.
     */
    public function testGetMethodArgs()
    {
        // Note: The arguments aren't indexed with the name of the parameter.
        $expectedArgs = array(
            0 => 321,
            1 => array('test'),
            2 => '123',
        );

        // We're going to set the userId to 321 in $_GET (but be sure nothing else is in there).
        $GLOBALS['_GET'] = array(
            'userId' => 321,
            'data' => 'test',
        );

        $mockController = new MockController();
        $methodObject = $this->application->getMethodObject($mockController, 'methodWithParams');
        $methodArgs = $this->application->getMethodArgs($methodObject);

        // Did it work?
        $this->assertEquals($expectedArgs, $methodArgs);
    }

    /**
     * Tests most important component of LMMVC, which is determining the controller, method and query string from the
     * request URI.
     *
     * @param string $requestUri
     * @param string $controllerName
     * @param string $methodName
     * @param string $queryString
     * @dataProvider getControllerDataProvider
     */
    public function testGetController($requestUri, $controllerName, $methodName, $queryString)
    {
        // We will need to set a default controller.
        $this->application->setDefaultController('default');

        // Parse the request URI.
        $app = $this->application->getController($requestUri);

        // Make sure it all works.
        $this->assertEquals($controllerName, $app['controller']);
        $this->assertEquals($methodName, $app['method']);
        $this->assertEquals($queryString, $app['query_string']);
    }

    /**
     * @return array
     */
    public function getControllerDataProvider()
    {
        return array(
            array(
                '/user/login', 'user', 'login', ''
            ),
            array(
                '/register/index', 'register', 'index', ''
            ),
            array(
                '/register/activate?id=100&code=abcd', 'register', 'activate', 'id=100&code=abcd',
            ),
            array(
                '/index', 'default', 'index', ''
            ),
            array(
                '/somethingElse', 'default', 'somethingElse', '',
            ),
            array(
                '/inDefault?id=1&name=someone&code=abc', 'default', 'inDefault', 'id=1&name=someone&code=abc'
            ),
            array(
                '/?id=1&name=you', 'default', 'index', 'id=1&name=you'
            )
        );
    }

    /**
     * Tests the exceptions thrown by getController.
     *
     * @param string $requestUri
     * @param string $expectedMessage
     * @dataProvider getControllerExceptionData
     */
    public function testGetControllerException($requestUri, $expectedMessage)
    {
        $this->setExpectedException('\\LmMvc\\Exception\\MalformedUriException', $expectedMessage);
        $this->application->getController($requestUri);
    }

    /**
     * @return array
     */
    public function getControllerExceptionData()
    {
        return array(
            array(
                'doesntStartWithSlash/index',
                'The request URI "doesntStartWithSlash/index" is malformed and could not be parsed.'
            ),
            array(
                '/invalid!controller/test',
                'The request URI was malformed: the controller "invalid!controller" is invalid.'
            ),
            array(
                '/controllerName/1invalidMethod',
                'The request URI was malformed: the method "1invalidMethod" is invalid.'
            )
        );
    }

    public function testRedirect()
    {
        $requestUri = '/where/to';
        $this->application->redirect($requestUri);
        print_r(headers_list());
    }
}
 