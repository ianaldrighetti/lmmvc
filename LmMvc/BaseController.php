<?php
namespace LmMvc;

/**
 * Class BaseController
 *
 * A base controller class that all controllers are to extend. The controller name and then the method is a component of
 * the URL being accessed. For example, say you have address http://www.example.com/register/activate. This would mean
 * that method activate in controller Register is handling the route.
 *
 * @package LmMvc
 */
abstract class BaseController
{
    /**
     * Every controller must at least create an action called index. This is the default page which will be shown if no
     * action is explicitly defined.
     *
     * @return void
     */
    abstract public function index();
}
