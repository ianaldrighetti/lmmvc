<?php
namespace Application\Controller;

use LmMvc\BaseController;

/**
 * Class DefaultPage
 *
 * This is the default page of the application setup in index.php. It can be accessed by going to /{methodName} or
 * default_page/{methodName} (due to the camel casing set for controller casing).
 *
 * @package controller
 */
class DefaultPage implements BaseController
{
    /**
     * This is the index page.
     */
    public function index()
    {
        echo 'You\'re accessing the index!';
    }

    /**
     * This page has arguments. They will be obtained from anything in the query string, i.e. ?userId=1&userName=you.
     * If they are not found then they will be null. They are not sanitized in any way, so it is just like accessing
     * the $_GET variable.
     *
     * Also note that you may specify default values for parameters as well, and they will be respected if the variable
     * is not found in $_GET.
     *
     * @param string $userId
     * @param string $userName
     */
    public function args($userId, $userName)
    {
        echo 'You\'re user ID is: ', intval($userId), ', user name: ', htmlspecialchars($userName);
    }
} 