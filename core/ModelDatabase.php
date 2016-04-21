<?php namespace ogo\core;

abstract class ModelDatabase {

	protected $table;
	protected $config;
	protected $fields = [];
	protected $i18n_fields = [];
	protected $data;
	protected $app;
	protected $exists;
	protected $language;

	public function __construct($data=null, $language=null) {
		$this->app = App::create();
		if ($language == null) {
			$language = $this->app->get_language();
		}
		$this->language = $language;
		$this->exists = false;
		$this->parse_admin_info();
		if ($data == null) {
			foreach ($this->fields as $name) {
				$this->data[$name] = null;
			}
			foreach ($this->i18n_fields as $name) {
				$langs = $this->app->config->active_languages;
				foreach ($langs as $lang) {
					$this->data[$name] = null;
				}
			}
		}
		elseif (is_array($data)) {
			$this->data = $data;
			if (isset($this->data["id"]) ) {
				$this->exists = true;
			}
		}
		elseif (is_numeric($data)) {
			$rows = $this->app->get_connection()->execute("select * from {$this->table} where id=:id", ["id" => $data]);
			if (count($rows) == 1) {
				$this->exists = true;
				$this->data = $rows[0];
			}
		}
	}

	private function parse_admin_info() {
		$this->config = self::get_class_config();
		$this->table = $this->config["table"];
		$this->i18n_fields = [];
		$this->fields = ["id", "created_at", "updated_at", "c_user", "u_user"];
		foreach ($this->config["fields"] as $name => $field) {
			if ( ! $field["i18n"]) {
				$this->fields[] = $name;
			}
		}
		if ($this->config["i18n"]) {
			foreach ($this->config["fields"] as $name => $field) {
				if ($field["i18n"]) {
					$this->i18n_fields[] = $name;
				}
			}
		}
	}

	public static function get_class_config() {
		$currentClass = get_called_class();
		$app = App::create();
		$refl = new \ReflectionClass($currentClass);
		$namespace = $refl->getNamespaceName();
		$folder = $app->get_root_folder() . DIRECTORY_SEPARATOR . str_replace("\\", DIRECTORY_SEPARATOR, $namespace);
		$file = $folder . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . strtolower($refl->getShortName() ) . '.php';
		if ( file_exists($file) ) {
			return include($file);
		}
		return [];
	}

	public function get_id() {
		return $this->get("id");
	}

	public function exists() {
		return $this->exists;
	}

	public function get_data() {
		return $this->data;
	}

	public function get($name, $lang=null) {
		$original = $name;
		if ($lang != null) {
			$name = $name . '_' . strtolower($lang);
		}
		elseif ($this->is_i18n($name)) {
			$name = $name . '_' . $this->app->get_language();
		}
		if (!$this->allow_empty_i18n && $this->is_i18n($original) && trim($this->data[$name]) == '') {
			$name = $original . '_' . $this->app->get_config()->get("default_language");
			return isset($this->data[$name]) ? $this->data[$name] : null;
		}
		return isset($this->data[$name]) ? $this->data[$name] : null;
	}

	public function has($name, $lang=null) {
		if ($lang != null) {
			$name = $name . '_' . strtolower($lang);
		}
		elseif ($this->is_i18n($name)) {
			$name = $name . '_' . $this->app->get_language();
		}
		return trim($this->data[$name]) != "" ? true : false;
	}

	public function set($name, $value, $lang=null) {
		if ($lang != null) {
			$name = $name . '_' . strtolower($lang);
		}
		elseif ($this->is_i18n($name)) {
			$name = $name . '_' . $this->app->get_language();
		}
		$this->data[$name] = $value;
	}

	public function is_set($name, $lang=null) {
		if ($lang != null) {
			$name = $name . '_' . strtolower($lang);
		}
		elseif ($this->is_i18n($name)) {
			$name = $name . '_' . $this->app->get_language();
		}
		return isset($this->data[$name]);
	}

	public function get_app() {
		return $this->app;
	}

	public function to_array() {
		return $this->data;
	}

	public function to_JSON() {
		return json_encode($this->data);
	}

	public function save() {
		$date = date("Y-m-d H:i:s");
		if ($this->data["id"] == null) {
			$this->data["created_at"] = $date;
			$this->data["updated_at"] = $date;
			$this->data["id"] = $this->app->get_connection()->insert($this->table, $this->data);
		}
		else {
			$this->data["updated_at"] = $date;
			$id = (int) $this->data["id"];
			$this->app->get_connection()->update($this->table, $this->data, "id='{$id}'");
		}
	}

	public function delete() {
		$this->app->get_connection()->execute("delete from {$this->table} where id=:id", ["id" => $this->data["id"]]);
		$this->exists = false;
		foreach ($this->data as $name => $value) {
			$this->data[$name] = null;
		}
	}

	protected function get_i18n_data() {
		foreach ($this->i18n_fields as $field) {
			if (isset($this->data[$field]) ) {
				$data[$field] = $this->data[$field];
			}
		}
		return $data;
	}

	protected function get_non_i18n_data() {
		$data = [];
		foreach ($this->fields as $field) {
			if (isset($this->data[$field]) ) {
				$data[$field] = $this->data[$field];
			}
		}
		return $data;
	}

	protected function is_i18n($field=null) {
		if ($field == null) {
			return count($this->i18n_fields) > 0;
		}
		else {
			return array_search($field, $this->i18n_fields) !== false;
		}
	}
}
