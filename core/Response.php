<?php namespace ogo\core;

class Response {

	const statusERROR 	= 'error'; 	// === 0
	const statusOK 		= 'ok'; 	// === 1
	const statusREDIRECT 	= 'redirect'; 	// === 2

	private static $headers = false;

	public static $types = array(
		"php"		=> "php",
		"widget" 	=> "component",
		"component"	=> "component",
		"html"		=> "text/html",
		"text"		=> "text/plain",
		"json"		=> "application/json",
		"xml"		=> "text/xml",
		"rss"		=> "application/rss+xml",
		"csv"		=> "text/csv",
		"bin"		=> "application/octet-stream",
		"pdf"		=> "application/pdf",
		"jpeg"		=> "image/jpeg",
		"jpg"		=> "image/jpeg",
		"gif"		=> "image/gif",
		"png"		=> "image/png"
	);

	public static $messages	= array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		306 => 'Switch Proxy',
		307 => 'Temporary Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		418 => 'I\'m a teapot',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		425 => 'Unordered Collection',
		426 => 'Upgrade Required',
		449 => 'Retry With',
		450 => 'Blocked by Windows Parental Controls',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		506 => 'Variant Also Negotiates',
		507 => 'Insufficient Storage',
		509 => 'Bandwidth Limit Exceeded',
		510 => 'Not Extended'
	);

	private $status;
	private $data = null;
	private $message = null;

	public function __construct($status=null, $data=null, $message=null) {
		if ($status == null) {
			$status = "ok";
		}
		$this->set_status($status);
		$this->set_data($data);
		$this->set_message($message);
	}

	public function set_status($status) {
		if ($status === 0 || $status == "error") {
			$this->status = "error";
		}
		elseif ($status === 1 || $status == "ok") {
			$this->status = "ok";
		}
		elseif ($status === 2 || $status == "redirect") {
			$this->status = "redirect";
		}
		else {
			throw new \Exception("Invalid response status.");
		}
	}

	public function set_message($message) {
		$this->message = $message;
	}

	public function get_status() {
		return $this->status;
	}

	public function set_data($data) {
		if ($data instanceof View) {
			$data = $data->parse();
		}
		$this->data = $data;
	}

	public function get_data() {
		return $this->data;
	}

	public function to_JSON() {
		$data = array(
			"status"	=> $this->status,
			"data"		=> $this->data,
			"message"       => $this->message
		);

		return json_encode($data);
	}

	public function headers($type, $code=null, $charset='utf-8') {
		if ($this->headers_already_sent() ) {
			return;
		}
		if ( ! in_array($type, self::$types) ) {
			if (isset(self::$types[$type]) ) {
				$type = self::$types[$type];
			}
		}
		if ($type == "application/json") {
			header("HTTP/1.1 200 OK");
			header("Content-type: application/json; charset=" . $charset);
			self::$headers = true;
			return;
		}
		if ($this->status == "redirect") {
			$code 	= $code ? $code : 303;
			$message 	= self::$messages[$code];
			header("HTTP/1.1 {$code} {$message}");
			header("Location: " . $this->data);
			self::$headers = true;
			exit();
		}
		if ($this->status == "error") {
			$code 	= $code ? $code : 500;
			$message 	= self::$messages[$code];
			header("HTTP/1.1 {$code} {$message}");
			header("Content-type: " . $type . "; charset=" . $charset);
			self::$headers = true;
		}
		if ($this->status == "ok") {
			$code 	= $code ? $code : 200;
			$message 	= self::$messages[$this->code];
			if ($type != "component") {
				header("HTTP/1.1 {$code} {$message}");
				header("Content-type: " . $type . "; charset=" . $charset);
				self::$headers = true;
			}
		}
	}

	public function render() {
		if (is_array($this->data) ) {
			echo $this->to_JSON();
		}
		else {
			echo $this->data;
		}
	}

	public function __toString() {
		if ($this->status == 'ok') {
			return $this->data;
		}
		else {
			return '';
		}
	}

	public function headers_already_sent() {
		return self::$headers;
	}
}
