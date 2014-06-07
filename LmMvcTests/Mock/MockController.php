<?php
namespace LmMvcTests\Mock;

use LmMvc\BaseController;

/**
 * Class MockController
 * @package LmMvcTests\Mock
 */
class MockController implements BaseController
{
    /**
     * Indicates whether the index method of the controller has been invoked.
     *
     * @var bool
     */
    private static $indexInvoked;

    /**
     * An array containing the parameters methodWithParams is called with or null if it hasn't been invoked.
     * @var array|null
     */
    private static $methodParameters;

    /**
     * Initializes everything...
     */
    public function __construct()
    {
        self::$indexInvoked = false;
        self::$methodParameters = null;
    }

    /**
     * @return boolean
     */
    public static function getIndexInvoked()
    {
        return self::$indexInvoked;
    }

    /**
     * @return array|null
     */
    public static function getMethodParameters()
    {
        return self::$methodParameters;
    }

    /**
     * The index page of the controller.
     */
    public function index()
    {
        self::$indexInvoked = true;
    }

    /**
     * A page with parameters.
     *
     * @param int $userId
     * @param array $data
     * @param int $withDefault
     */
    public function methodWithParams($userId, array $data, $withDefault = 123)
    {
        self::$methodParameters = array(
            'userId' => $userId,
            'data' => $data,
            'withDefault' => $withDefault
        );
    }

    /**
     * A page that throws an exception...
     *
     * @throws \Exception
     */
    public function throwException()
    {
        throw new \Exception('Just an Exception...');
    }
} 