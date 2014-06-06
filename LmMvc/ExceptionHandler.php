<?php
namespace LmMvc;

/**
 * Interface ExceptionHandler
 *
 * If you wish to implement a custom exception handler, you must implement this interface.
 *
 * @package LmMvc\Exception
 */
interface ExceptionHandler
{
    /**
     * This is the only method that an Exception Handler must implement. It will be passed the exception instance and
     * whether the exception was internal (to LMMVC). There are a few exceptions you can expect LMMVC to throw one time
     * or another:
     *      ControllerException - Thrown if a controller does not implement BaseController.
     *      MalformedUriException - Thrown if the URI was malformed and could not be processed.
     *      PageNotFoundException - Thrown if the page was not found (as in, the controller could not be found, the method
     *                     doesn't exist, etc.).
     *
     * @param \Exception $ex The exception thrown.
     * @param bool $internal If true it means that the exception was generated internally (by LMMVC), otherwise (false)
     *                       it means that the exception was thrown by a controller (so probably the application
     *                       itself).
     * @return void
     */
    public function handle(\Exception $ex, $internal = true);
}
