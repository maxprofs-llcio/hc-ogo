<?php namespace ogo\core;

class Controller {

	protected $app;
	protected $config;
	protected $folder;
	protected $i18n;
	protected $request;

	public function __construct() {
		$this->app = App::create();
		$currentClass = get_class($this);
		$refl = new \ReflectionClass($currentClass);
		$namespace = $refl->getNamespaceName();
		$this->request = $this->app->get_request();
		$this->folder = $this->app->get_root_folder() . "/" . str_replace("\\", "/", $namespace);
		$config = $this->folder . '/config/config.php';
		if ( file_exists($config) ) {
			$this->config = new Config($config);
		}
		if (is_dir($this->folder . "/i18n")) {
			$this->i18n = new I18n($this->folder ."/i18n", $this->app->get_language() );
		}
	}

	public function set_i18n($i18n) {
		$this->i18n = $i18n;
	}

	public function get_folder() {
		return $this->folder;
	}

	public function get_view($name) {
		$file = $this->folder . '/view/' . $name . '.phtml';

		if ( file_exists( $file ) ) {
			$view = new View($file, $this);
			$view->set_i18n($this->i18n);
			return $view;
		}
		else {
			throw new \Exception("View {$file} not found.");
		}
		return null;
	}

	public function response($status=null, $data=null, $message=null) {
		return new Response($status, $data, $message);
	}

	public static function call() {

		$params = func_get_args();
		$controller_action = $params[0];
		$params = array_slice($params, 1);

		$parts = explode('->', $controller_action);
		$action = $parts[1];
		$parts = explode('.', $parts[0]);
		$controller = implode('\\', $parts);

		$class = explode('.', $controller);
		$class = '\\' . implode('\\', $class);
		$class = '\\ogo\\mod' . $class;
		if (class_exists($class)) {
			$w = new $class;
		}
		else {
			throw new \Exception("Class {$class} not exists.");
		}
		$method = 'action_' . strtolower($action);
		if ( method_exists($w, $method) ) {
			return call_user_func_array([$w, $method], $params);
		}
		else {
			throw new \Exception("Action {$method} not exists.");
		}
	}

	public static function action_exists($module, $action) {
		$class = explode('.', $module);
		$class = '\\' . implode('\\', $class);
		$class = '\\ogo\\app' . $class;
		if ( ! class_exists($class)) {
			return false;
		}
		else {
			$method = 'action_' . strtolower($action);
			$w = new $class;
			if ( ! method_exists($w, $method) ) {
				return false;
			}
		}
		return true;
	}
}
