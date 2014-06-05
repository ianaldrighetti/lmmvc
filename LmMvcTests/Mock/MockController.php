<?php
namespace LmMvcTests\Mock;

use LmMvc\BaseController;

/**
 * Class MockController
 * @package LmMvcTests\Mock
 */
class MockController implements BaseController
{
    public function index()
    {

    }

    public function methodWithParams($userId, array $data, $withDefault = 123)
    {

    }
} 