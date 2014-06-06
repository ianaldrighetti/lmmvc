<?php
namespace LmMvcTests;

use LmMvc\ControllerCaser;

/**
 * Class ControllerCaserTest
 * @package LmMvcTests
 */
class ControllerCaserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the ControllerCaser::lowerCase method.
     */
    public function testLowerCase()
    {
        $controllerName = 'ImUpperCasedSomePlaces';
        $this->assertEquals(strtolower($controllerName), ControllerCaser::lowerCase($controllerName));
    }

    /**
     * Tests the ControllerCaser::upperCaseFirst method.
     *
     * @param string $controllerName
     * @param string $expectedControllerName
     * @dataProvider upperCaseFirstDataProvider
     */
    public function testUpperCaseFirst($controllerName, $expectedControllerName)
    {
        $this->assertEquals($expectedControllerName, ControllerCaser::upperCaseFirst($controllerName));
    }

    /**
     * @return array
     */
    public function upperCaseFirstDataProvider()
    {
        return array(
            array('mycontroller', 'Mycontroller'),
            array('MyCoNtRoLlEr', 'Mycontroller'),
            array('ALLUPPER', 'Allupper')
        );
    }

    /**
     * Tests the ControllerCaser::camelCase method.
     *
     * @param string $controllerName
     * @param string $expectedControllerName
     * @dataProvider camelCaseDataProvider
     */
    public function testCamelCase($controllerName, $expectedControllerName)
    {
        $this->assertEquals($expectedControllerName, ControllerCaser::camelCase($controllerName));
    }

    /**
     * @return array
     */
    public function camelCaseDataProvider()
    {
        return array(
            array('mycontroller', 'mycontroller'),
            array('MyController', 'mycontroller'),
            array('my_controller', 'myController'),
            array('my_other_controller', 'myOtherController'),
            array('ive__got___issues', 'iveGotIssues')
        );
    }

    /**
     * Tests the ControllerCaser::camelCaseWithFirstUpper method.
     *
     * @param string $controllerName
     * @param string $expectedControllerName
     * @dataProvider camelCaseWithFirstUpperDataProvider
     */
    public function testCamelCaseWithFirstUpper($controllerName, $expectedControllerName)
    {
        $this->assertEquals($expectedControllerName, ControllerCaser::camelCaseWithFirstUpper($controllerName));
    }

    /**
     * @return array
     */
    public function camelCaseWithFirstUpperDataProvider()
    {
        return array(
            array('mycontroller', 'Mycontroller'),
            array('MyController', 'Mycontroller'),
            array('my_controller', 'MyController'),
            array('my_other_controller', 'MyOtherController'),
            array('ive__got___issues', 'IveGotIssues')
        );
    }
}
