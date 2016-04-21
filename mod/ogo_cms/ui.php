<?php namespace ogo\mod\ogo_cms;

class ui extends \ogo\core\Controller {

	public function action_index() {
		return $this->response("ok", "<h1>Hello world!</h1>");
	}
}
