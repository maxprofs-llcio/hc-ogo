<?php namespace ogo\core;

class App {

	public $config;
	public $request;
	public $language;
	private $connections = [];
	private $data = [];

	private static $instance = null;
	private $event_dispatchers;
	private $environment;
	private $public_folder;
	private $i18n = [];

	private function __construct() {
		define('ROOT_FOLDER', realpath(__DIR__ . '/..') );
		$this->request = new Request();
		$this->public_folder = $_SERVER["CONTEXT_DOCUMENT_ROOT"];
	}

	public static function create() {
		if ( ! self::$instance) {
			self::$instance = new App();
		}
		return self::$instance;
	}

	public static function get_instance() {
		return self::create();
	}

	public function get_environment() {
		return $this->environment;
	}

	public function set($key, $value) {
		$this->data[$key] = $value;
	}

	public function get($key) {
		return $this->data[$key];
	}

	public function set_environment($env) {
		$env = strtolower($env);
		$this->environment = $env;
		switch ($env) {
			case "production":
				ini_set("display_errors", 0);
			break;
			case "development":
				ini_set("display_errors", 1);
				error_reporting(E_ALL ^ E_NOTICE);
			break;
			default:
				ini_set("display_errors", 1);
				error_reporting(E_ALL ^ E_NOTICE);
			break;
		}

		if ($env != "production") {
			set_exception_handler(function ($e) {
				echo "<pre>";
				echo "Uncaught Exception: " , $e->getMessage(), "\n\n";

				echo "" . $e->getFile() . " ";
				echo "(" . $e->getLine() . ")\n";

				$trace = $e->getTrace();

				foreach ($trace as $part) {
					echo "" . $part["file"] . " ";
					echo "(" . $part["line"] . ")\n";
				}

				echo "</pre>";
				exit;
			});
		}
	}

	public function debug($data) {
		if ($this->environment != "production") {
			echo "<pre>";
			print_r($data);
			echo "</pre>";
		}
	}

	public function get_root_folder() {
		return ROOT_FOLDER;
	}

	public function set_public_folder($folder) {
		$this->public_folder = $folder;
	}

	public function get_public_folder() {
		return $this->public_folder;
	}

	public function set_connection($name, $conn) {
		$this->connections[$name] = $conn;
	}

	public function get_connection($name='default') {
		return $this->connections[$name];
	}

	public function get_request() {
		return $this->request;
	}

	public function get_config() {
		return $this->config;
	}

	public function set_config($file) {
		$config = new Config($file);
		if ( ! $this->environment) {
			throw new \Exception('Environment not set.');
		}
		if ( ! $config->get($this->environment)) {
			throw new \Exception('Environment not set in configuration file.');
		}
		$data = $config->get_data();
		$this->config = new Config($data[$this->environment]);
	}

	public function get_i18n($module, $lang=null) {

		if ($lang == null) {
			$lang = $this->get_language();
		}

		if (defined("APP")) {
			$module = preg_replace('/^APP/', APP, $module);
			$module = preg_replace('/^\\APP/', APP, $module);
		}

		$folder = $this->get_folder() . '/' . str_replace(".", "/", $module) . '/i18n';
		$file = $folder . '/' . $lang;
		if ( ! isset($this->i18n[$file])) {
			if ($this->config->i18n && is_dir($folder)) {
				$this->i18n[$file] = new I18n($folder, $lang);
			}
			else {
				throw new \Exception('I18n file not found');
			}
		}

		return $this->i18n[$file];
	}

	public function redirect($url, $permanent=false) {
		if ($permanent) {
			header("HTTP/1.1 301 Moved Permanently");
		}
		header("Location: {$url}");
		exit();
	}

	public function get_language() {
		return $this->language;
	}

	public function get_languages() {
		return $this->config->get("languages");
	}

	public function set_language($lang) {
		$this->language = $lang;
	}

	public function call() {
		return call_user_func_array('\ogo\core\Controller::call', func_get_args() );
	}

	public function attach_event($name, $function) {
		if ( ! is_callable($function) ) {
			throw new \Exception("Second parameter is not a function");
		}
		$name = strtolower($name);
		if ( ! isset($this->event_dispatchers) ) {
			$this->event_dispatchers = [];
		}
		if ( ! isset($this->event_dispatchers[$name]) ) {
			$this->event_dispatchers[$name] = [];
		}
		$this->event_dispatchers[$name][] = $function;
	}

	public function remove_event($name) {
		$name = strtolower($name);
		unset($this->event_dispatchers[$name]);
	}

	public function trigger($name, $data=null) {
		$name = strtolower($name);
		foreach ($this->event_dispatchers[$name] as $i => $f) {
			if (is_callable($f) ) {
				$b = $f($data);
				if ($b === false) {
					return; // STOP PROPAGATION
				}
			}
		}
	}
}
