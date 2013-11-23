<?php
namespace CLMVC\Core\Http;
use CLMVC\Controllers\BaseController;
use CLMVC\Helpers\Communication;

class Routes {
    /**
     * @var Route[]
     */
    private $routes = array();
    function add($route,$callback , $params = array()) {
        $this->routes[] = new Route($route, $callback, $params);
    }

    /**
     * Takes request uri and routes to controller.
     */
    function routing() {
        $uri = $_SERVER['REQUEST_URI'];
        $method = Communication::getMethod();
        foreach ($this->routes as $route) {
            if ($matches = $route->match($uri, $method)) {
                $params = $route->params($uri);
                $array = $route->getCallback();
                $controller = str_replace('/', '\\', $array[0]);
                /**
                 * @var BaseController $controller
                 */
                $ctrl = new $controller(false);
                $ctrl->init();
                $action = $array[1];
                if ($action == ':action')
                    $action = str_replace(':action', $matches['action'], $action);
                $ctrl->executeAction($action, $params);
                break;
            }
        }
    }
}
