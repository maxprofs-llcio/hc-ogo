<?php namespace ogo\core;

class Router {
	private $app;
	private $request;
	private $default_action;
	private $routes = [];

	public function __construct() {
		$this->app = \ogo\core\App::get_instance();
		$this->request = $this->app->get_request();
	}

	public function add($method, $pattern, $exec) {
		$this->routes[] = [
			'pattern' 	=> $pattern,
			'method'	=> $method,
			'controller'	=> $exec
		];
	}

	public function def($exec) {
		$this->default_action = $exec;
	}

	public function run() {
		$request = $this->request;
		$path = $request->get_path();
		$method = $request->get_method();
		foreach ($this->routes as $route) {
			$r = $this->test_pattern($request, $route);
			if ($r === false) {
				continue;
			}
			if (is_object($r)) {
				return $r;
			}
			else {
				$r = new Response('error');
				$r->set_code(500);
				return $r;
			}
		}
		if (is_callable($this->default_action)) {
			$action = $this->default_action;
			return $action($this->app);
		}
		return new Response('error');
	}

	private function test_pattern($route) {

		$path = $this->request->get_path();
		$method = $this->request->get_method();

		if (preg_match("/{$method}/", $route["method"]) === 0) return false;

		$original_pattern = $route["pattern"];

		$pattern = preg_replace('/\[\:[a-z0-9_]+\]/i', '([^/]+)', $route["pattern"]);
		$pattern = str_replace('/', '\/', $pattern);

		if (preg_match('/^' . $pattern . '$/i', $path)) {
			preg_match('/' . $pattern . '/i', $path, $matches);

			array_shift($matches);

			preg_match_all('/\[\:([a-z0-9_]+)\]/i', $original_pattern, $keys);

			array_shift($keys);
			$keys = $keys[0];

			$params = array();
			for ($i = 0; $i < count($keys); $i++) {
				$params[$keys[$i]] = urldecode($matches[$i]);
			}

			if (is_callable($route["controller"])) {
				$b = $route["controller"]($this->app, $params);
				if ($b === false) {
					return false;
				}
			}
			return $b;
		}

		return false;
	}
}
