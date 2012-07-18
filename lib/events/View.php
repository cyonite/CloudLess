<?php
class View
{
    public static $ViewSections = array();

    static function register($section, $callback, $priority = 100)
    {
        if (!isset(self::$ViewSections[$section]['handler'])) {
            if (!isset(self::$ViewSections))
                self::$ViewSections = array();
            if (is_array($callback)) {
                if (is_string($callback[0]))
                    $id = hash('md5', $callback[0] . $callback[1] . $priority);
                else
                    $id = hash('md5', get_class($callback[0]) . $callback[1] . $priority);
            } else
                $id = hash('md5', $callback . $priority);
            self::$ViewSections[$section][$priority][$id] = $callback;
        } else {
            $handler = self::$ViewSections[$section]['handler'];
            call_user_func($handler, $section, $callback);
        }
    }

    static function registerHandler($section, $callback)
    {
        self::$ViewSections[$section]['handler'] = $callback;
    }

    static function generate($section, $params = array(), $isArray = false)
    {
        $priorities = array_key_exists_v($section, self::$ViewSections);
        if (self::hasCustomHandler($section)) {
            ob_start();
            if (!$isArray && !is_array($params))
                $params = array($params);
            call_user_func_array($priorities['handler'] . '_run',array($section,$params));
            $sections = ob_get_contents();
            ob_end_clean();
            return $sections;
        }

        if ($priorities)
            ksort($priorities);
        if (is_array($priorities)) {
            ob_start();
            if (!$isArray && !is_array($params))
                $params = array($params);
            foreach ($priorities as $functions) {
                if (is_array($functions))
                    foreach ($functions as $function) {
                        if (!is_callable($function)) {
                            if (is_array($function))
                                if (is_string($function[0]))
                                    $message = implode('::', $function);
                                else
                                    $message = get_class($function[0]) . '->' . $function[1];
                            else
                                $message = $function;
                            trigger_error('View cannot call ' . $message . ' it does not exist.', E_USER_WARNING);
                            continue;
                        }
                        call_user_func_array($function, $params);
                    }
            }
            $sections = ob_get_contents();
            ob_end_clean();
            return $sections;
        }
        return '';
    }

    static function render($section, $params = array(), $isArray = false)
    {
        $sections = self::generate($section, $params, $isArray);
        if ($sections)
            echo $sections;
    }

    static function isRegistered($section)
    {
        return array_key_exists($section, self::$ViewSections);
    }

    static function hasCustomHandler($section)
    {
        return isset(self::$ViewSections[$section]['handler']);
    }
}

/*
* deprecated since 11.6
*/
class ViewHelper
{
    static function registerViewSection($section, $callback, $priority = 100)
    {
        View::register($section, $callback, $priority);
    }

    static function registerViewSectionHandler($section, $callback)
    {
        View::registerHandler($section, $callback);
    }

    static function renderSection($section, $params = array(), $isArray = false)
    {
        View::render($section, $params, $isArray);
    }

    static function isRegistered($section)
    {
        return View::isRegistered($section);
    }

    static function hasCustomHandler($section)
    {
        return View::hasCustomHandler($section);
    }

}