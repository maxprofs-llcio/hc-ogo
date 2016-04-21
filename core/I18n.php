<?php namespace ogo\core;

class I18n {

	private $data;
	private $language;
	private $app;
	private $folder;

	public function __construct($folder, $language) {
		$this->app = App::create();
		$this->folder = $folder;
		$this->change_language($language);
	}

	public function translate($txt, $lang=null) {
		if ($lang == null) {
			$lang = $this->language;
		}
		if (isset($this->data[$lang][$txt])) {

			$text = $this->data[$lang][$txt];

			if (strpos($text, "FILE:") === 0) {
				$file = $this->folder . '/' . substr($text, 5);
				if (file_exists($file) ) {
					return file_get_contents($file);
				}
			}

			return $this->data[$lang][$txt];
		}
		else {
			$parts = explode(".", $txt);
			if (count($parts) == 1) {
				return $txt;
			}
			else {
				$r = [];
				$first = true;
				foreach ($parts as $part) {
					if ($first) {
						$r = $this->data[$lang];
						$first = false;
					}
					$r = isset($r[$part]) ? $r[$part] : false;
					if (!$r) return $txt;
				}
				return $r;
			}
		}
	}

	public function change_language($lang) {
		$this->language = $lang;
		$folder_base = rtrim($this->folder, "/") . "/";
		$file = $folder_base . strtolower($lang) . ".php";
		if ( ! isset($this->data[$lang]) ) {
			if (file_exists($file) ) {
				$this->data[$lang] = include $file;
			}
			else {
				$this->data[$lang] = [];
			}
		}
	}
}
