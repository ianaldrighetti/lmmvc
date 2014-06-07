# LMMVC - Lean, Mean MVC

[![Build Status](https://travis-ci.org/ianaldrighetti/lmmvc.svg?branch=master)](https://travis-ci.org/ianaldrighetti/lmmvc)


LMMVC is a relatively small PHP MVC library. It allows you to route requests to a controller to handle the request based on the request URI -- pretty much like any other MVC. 

What's so different about LMMVC? Well, to be honest, probably not much. While there are hundreds (or thousands, or a lot) of other MVC libraries already out there, I made this primarily for learning experience. However, I do plan on using it in my projects from here on out. I figured I would make it open source (under the MS-RL), that way I could have a central location to keep it updated.

Just like most other MVC's, the URL's are formatted like so:
```
http://www.example.com/controller_name/method_name?id=1&code=abcde
```

With LMMVC's default setup, this would load a controller with a class name of ControllerName and invoke method_name. id and code would be accessible through $_GET, as usual.

### Installation
You can get the latest on our release page, or you can clone it, or even use composer. To use this package via composer, add this to your require:

```
  "ianaldrighetti/lmmvc": "dev-master"
```

### Basic Setup
For a basic setup you can checkout the ```/example/``` directory in the repository. There it has an example .htaccess, example index.php and example controller. The example index.php goes over the basics of the methods that are useful to someone setting up their application. However, for more detailed documentation, you can look below.

### Method Arguments
A small feature in LMMVC is that methods in controllers can specify arguments, these arguments would then be fetched from ```$_GET```, if available.

For example, a method like so:
```php
public function args($userId, $userName = 'you', array $data)
{
  // *do awesome stuff*
}
```

The above would be passed ```$_GET['userId']```, ```$_GET['userName']``` and ```$_GET['data']```. In the event that these are not found and the parameter sets to default, it will be passed null. If any variable is not found in ```$_GET``` and it does have a default, the default will be passed.

Arguments may be type hinted with an array, and if ```$_GET['data']``` in the example wasn't found, an empty array would be supplied. However, if ```$_GET['data']``` was not actually an array, LMMVC would convert it to an array (with the ```$data[0]``` entry set to ```$_GET['data']```).

### Application Documentation
The following is documentation for every method in the Application class.

##### setControllerCaser

The controller caser is very important to LMMVC. Because LMMVC relies on autoloading for loading the controllers at runtime the controller name must be properly cased in order for autoloading to work (as it is often case-sensitive).

To set a controller caser, you can do one of the following:
```php
// Invoke a function name for casing.
$application->setControllerCaser('function_name');

// If you are on the right PHP version, you can do:
$application->setControllerCaser(function($controllerName)
  {
    // *do stuff*
    return $controllerName;
  });

// You can also do classes:
$application->setControllerCaser(array($object, 'methodName'));

// Or a static method in a class:
$application->setController(array('\\Class\\Namespace\\ClassName', 'methodName'));
```

LMMVC provides a class with static methods that offer some common casings for controller names. These are all in the \LmMvc\Utility\ControllerCaser class:
  - **lowerCase** - This will lower case the controller name, so basically unchanged.
  - **upperCaseFirst** - This will upper case the first character of the name, so mycontroller would become Mycontroller.
  - **camelCase** - This will camel case the controller name. This works slightly differently, as it requires there to be an underscore (_) before the character that is to be uppercased. This means that my_controller would become myController.
  - **camelCaseWithFirstUpper** - The same as **camelCase** but my_controller would become MyController (it uppercases the first character of the controller name).

LMMVC defaults to ControllerCaser::camelCaseWithFirstUpper. Additionally, if you use camel casing that means your actual controller name (i.e. ```class MyController implements BaseController``` in the source file) cannot have an underscore. That's because they are used when parsing the controller name to determine the character to uppercase.

These controller casers can throw Exception's as well in the case that the controller name cannot be processed. For example, the camelCase methods in ControllerCaser throw an Exception if there is more than one underscore in a row. But there is no need to throw an Exception if the controller name has an invalid character for a controller class name, as it is validated first using the ```isClassMethodNameValid``` method first.

##### setExceptionHandler

An exception handler must be an instance of a class that implements the \LmMvc\ExceptionHandler class. The exception handler is used to handle, as it's name implies, exceptions. These exceptions include PageNotFoundException, MalformedUriException and ControllerException -- those are all exceptions that LMMVC can throw at some point. It must also handle every other exception as well, including those possibly thrown by a controller. As usual, LMMVC comes with a default exception handler, called \LmMvc\DefaultExceptionHandler.

However, if you wish to have a custom one, you simply set it by calling up:
```php
$application->setExceptionHandler($exceptionHandler);
```

##### setNamespace

As noted numerous times, LMMVC relies on autoloading to load a controller at runtime once the proper controller name has been determined. In order to accomplish this (well, to do it properly) you must tell LMMVC where the controllers reside, their namespace. This is done like so:
```php
$application->setNamespace('\\Your\\Application\\Controller');
```

This will make LMMVC try to autoload a controller by the name of my_controller (which becomes MyController with the default casing setup) from: ```\Your\Application\Controller\MyController```.

##### setDefaultController

If no controller name can be determined from the request URI (i.e. ```/index``` or ```/somepage```), LMMVC must be told what controller to use by default, like so:
```php
$application->setDefaultController('default_page');
```

Note, as above, that this is not the actual name of the controller in it's implementation, but the controller name that would appear in the URL based on the controller casing being used. For example, with default controller casing ```DefaultPage``` would be specified as ```default_page```.

##### run

After everything is setup, simply call:
```php
$application->run();
```

This will cause the routing to occur and your application (if setup right) will work. It's that easy!

##### Other Methods
There are other publicly accessible methods in the Application class, however they are just there for testing purposes. You can always take a look at the documentation for those other methods in the source, though.
