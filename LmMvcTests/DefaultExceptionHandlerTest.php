<?php
namespace LmMvcTests;

use LmMvc\DefaultExceptionHandler;
use LmMvc\Exception\MalformedUriException;
use LmMvc\Exception\PageNotFoundException;
use LmMvc\Utility\HeaderWrapper;

/**
 * Class DefaultExceptionHandlerTest
 * @package LmMvcTests
 */
class DefaultExceptionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DefaultExceptionHandler
     */
    private $defaultExceptionHandler;

    /**
     * Creates an instance of a Default Exception Handler.
     */
    public function setUp()
    {
        $this->defaultExceptionHandler = new DefaultExceptionHandler();
    }

    /**
     * Tests the getHeaderWrapper.
     */
    public function testGetHeaderWrapper()
    {
        $this->assertInstanceOf('\\LmMvc\\Utility\\HeaderWrapper', $this->defaultExceptionHandler->getHeaderWrapper());
    }

    /**
     * Tests handling of PageNotFoundException's.
     */
    public function testHandlePageNotFoundException()
    {
        $headerWrapper = new HeaderWrapper();
        $headerWrapper->setEnabled(false);
        $this->defaultExceptionHandler->setHeaderWrapper($headerWrapper);

        ob_start();
        $this->defaultExceptionHandler->handle(new PageNotFoundException());
        $output = ob_get_contents();
        ob_clean();

        $this->assertEquals(404, $headerWrapper->getStatusCode());
    }

    /**
     * Tests the handling of all other exceptions.
     */
    public function testHandleGenericException()
    {
        $headerWrapper = new HeaderWrapper();
        $headerWrapper->setEnabled(false);
        $this->defaultExceptionHandler->setHeaderWrapper($headerWrapper);

        ob_start();
        $this->defaultExceptionHandler->handle(new MalformedUriException());
        $output = ob_get_contents();
        ob_clean();

        $this->assertEquals(500, $headerWrapper->getStatusCode());
    }
}
