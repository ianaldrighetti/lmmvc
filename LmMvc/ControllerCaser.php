<?php
namespace LmMvc;

/**
 * Class ControllerCaser
 *
 * The Controller Caser provides some methods for correcting the controller name to allow it to work properly with
 * autoloading.
 *
 * @package LmMvc
 */
class ControllerCaser
{
    /**
     * Lower cases the controller name.
     *
     * @param string $controllerName
     * @return string
     */
    public static function lowerCase($controllerName)
    {
        return strtolower($controllerName);
    }

    /**
     * Upper cases the first character in the controller name.
     *
     * @param string $controllerName
     * @return string
     */
    public static function upperCaseFirst($controllerName)
    {
        return ucfirst(self::lowerCase($controllerName));
    }

    /**
     * Converts a controller name to camel case (i.e. my_controller to myController).
     *
     * @param string $controllerName
     * @param bool $ucFirst
     * @return string
     */
    public static function camelCase($controllerName, $ucFirst = false)
    {
        $controllerName = self::lowerCase($controllerName);

        // Do we need to uppercase the first character?
        if (!empty($ucFirst))
        {
            $controllerName = self::upperCaseFirst($controllerName);
        }

        // If there aren't any underscores, then we have nothing to do.
        if (strpos($controllerName, '_') === false)
        {
            return $controllerName;
        }

        $casedName = '';
        $upperCaseNext = false;
        foreach (str_split($controllerName) as $char)
        {
            // If it's an underscore, flag that the next character is to be uppercased and continue.
            if ($char == '_')
            {
                $upperCaseNext = true;
                continue;
            }

            // Add the character and uppercase it if necessary.
            $casedName .= $upperCaseNext ? strtoupper($char) : $char;
            $upperCaseNext = false;
        }

        return $casedName;
    }

    /**
     * Converts a controller name to camel case, much like the camelCase method, only it uppercases the first character
     * of the controller name.
     *
     * @param string $controllerName
     * @return string
     */
    public static function camelCaseWithFirstUpper($controllerName)
    {
        return self::camelCase($controllerName, true);
    }
}
