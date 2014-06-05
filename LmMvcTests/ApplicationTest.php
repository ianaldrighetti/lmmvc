<?php
namespace LmMvcTests;

use LmMvc\Application;
use LmMvc\DefaultExceptionHandler;

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
}
 