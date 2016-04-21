<?php namespace ogo\core;

class View {

	private $file;
	private $folder;
	private $data = array();
	private $app;
	private $controller;
	private $i18n;
	private $uid;

	public function __construct($file, $controller=null) {
		$this->file = "/" . trim($file, "/");
		$this->folder = dirname($file);
		$this->app = App::create();
		$this->controller = $controller;
		$this->uid = uniqid();
	}

	public function parse() {
		try {
			ob_start();
			$this->render();
			$contents = ob_get_contents();
			ob_end_clean();
		}
		catch (\Exception $e) {
			ob_clean();
			$contents = $e->getMessage();
		}
		return $contents;
	}

	public function render() {
		extract($this->data, EXTR_OVERWRITE);
		include $this->file;
	}

	public function escape($txt, $html=true) {
		if ( ! $html) {
			return str_replace('"', '\"', html_entity_decode(strip_tags($txt), ENT_COMPAT, 'utf-8'));
		}
		return htmlentities($txt, ENT_QUOTES, 'UTF-8');
	}

	public function bind($data) {
		foreach ($data as $key => $value) {
			$this->data[$key] = $value;
		}
	}

	public function __set($name, $value) {
		$this->data[$name] = $value;
	}

	public function __get($name) {
		return ! empty($this->data[$name]) ? $this->data[$name] : null;
	}

	public function translate($text) {
		return $this->i18n->translate($text);
	}

	public function txt($text) {
		return $this->i18n->translate($text);
	}

	public function __($text) {
		return $this->i18n->translate($text);
	}

	public function etxt($text) {
		return $this->escape($this->txt($text));
	}

	public function set_i18n($i18n) {
		$this->i18n = $i18n;
	}

	public function get_uid($prefix='') {
		return $prefix . $this->uid;
	}

	public function script($files, $options=null) {
		if ($options["hide_tag"]) {
			$js = "";
		}
		else {
			$js = '<script>';
		}
		if ($options["widget"]) {
			$js .= '(function(){';
			$js .= '[].forEach.call(document.querySelectorAll("' . $options["widget"] . '"), function(widget) {';
		}
		foreach ($files as $file) {
			$path = $this->folder . "/js/" . $file . ".js";
			ob_start();
			include($path);
			$code = ob_get_contents();
			ob_end_clean();
			$js .= '(function(){';
			$js .= $code;
			$js .= '})();';
		}
		if ($options["widget"]) {
			$js .= '});})();';
		}
		if ( ! $options["hide_tag"]) {
			$js .= '</script>';
		}
		return $js;
	}

	public function __toString() {
		return $this->parse();
	}

}
