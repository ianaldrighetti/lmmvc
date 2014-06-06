<?php
namespace LmMvcTests\Mock;

use LmMvc\ExceptionHandler;

/**
 * Class MockExceptionHandler
 * @package LmMvcTests\Mock
 */
class MockExceptionHandler implements ExceptionHandler
{
    /**
     * Contains the exception handle was called with.
     * @var \Exception
     */
    private $exception;

    /**
     * Contains the internal flag handle was called with.
     * @var bool
     */
    private $internal;

    /**
     * Initializes the Mock Exception Handler.
     */
    public function __construct()
    {
        $this->exception = null;
        $this->internal = null;
    }

    /**
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @return boolean
     */
    public function getInternal()
    {
        return $this->internal;
    }

    /**
     * Just pretends to handle the exception.
     *
     * @param \Exception $ex
     * @param bool $internal
     */
    public function handle(\Exception $ex, $internal = true)
    {
        $this->exception = $ex;
        $this->internal = $internal;
    }
}
