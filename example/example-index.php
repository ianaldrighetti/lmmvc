<?php
/**
 * This is an example index.php to use with LMMVC. All requests to your web application will be routed to this file and
 * then LMMVC will will decide which controller to load and which method in that controller to invoke. To have all
 * requests routed to this file (assuming you renamed it to index.php), you would want to copy example.htaccess and
 * place it in a .htaccess file in the same directory as the index.php (also assuming you're using Apache).
 */

// If you use Composer, make sure this points to your autoload file. However, LMMVC depends on autoloading for
// controllers to be loaded at runtime. That, or all controllers are included anyways (though not a good idea).
if (file_exists('../vendor/autoload.php'))
{
    require_once('../vendor/autoload.php');
}

// You can include any of your own bootstrap files now.
// Here we're including a controller for simplicities sake.
require_once ('controller/DefaultPage.php');

// Create an instance of Application.
$application = new \LmMvc\Application();

// We're going to set the controller casing, which will be used to correct controller names for autoloading purposes.
// You can check out the README for more information. This is the default:
$application->setControllerCaser(array('\\LmMvc\\Utility\\ControllerCaser', 'camelCaseWithFirstUpper'));

// If you want custom error pages, you must create your own Exception Handler. However, LMMVC comes with one by default:
$application->setExceptionHandler(new \LmMvc\DefaultExceptionHandler());

// As noted, LMMVC relies on autoloading to load controller's at runtime. This requires that you tell LMMVC the
// namespace that they all belong to. Every controller must also implement the \LmMvc\BaseController interface as well.
$application->setNamespace('\\Application\\Controller');

// If no controller name can be determined from the URL, we will use the default:
// (do not use the name of the controller class, but the name that would be used in the URL)
$application->setDefaultController('default_page');

// There isn't anything else to do, so have LMMVC route the request.
$application->run();