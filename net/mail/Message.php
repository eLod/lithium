<?php

namespace lithium\net\mail;

use BadMethodCallException;

class Message extends \lithium\core\Object {
	/**
	 * Content-Types
	 *
	 * @var array
	 */
	protected $_types;

	/**
	 * Recipient.
	 *
	 * @var array
	 */
	protected $_to;

	/**
	 * Sender.
	 *
	 * @var array
	 */
	protected $_from;

	/**
	 * Subject.
	 *
	 * @var string
	 */
	protected $_subject;

	/**
	 * Character set.
	 *
	 * @var string
	 */
	protected $_charset;

	/**
	 * Headers.
	 *
	 * @var array
	 */
	protected $_headers = array();

	/**
	 * The body of the message, indexed by content type.
	 *
	 * @var array
	 */
	protected $_body = array();

	/**
	 * Classes used by Response.
	 *
	 * @var array
	 */
	protected $_classes = array(
		'media' => 'lithium\net\mail\Media'
	);

	/**
	 * Auto configuration properties.
	 *
	 * @var array
	 */
	protected $_autoConfig = array('types', 'to', 'from', 'subject', 'charset', 'headers' => 'merge', 'classes' => 'merge');

	public function __construct(array $config = array()) {
		$defaults = array(
			'types' => array('text', 'html'), 'charset' => 'UTF-8'
		);
		parent::__construct($config + $defaults);
	}

	protected function _init() {
		parent::_init();
		$media = $this->_classes['media'];
		$this->_types = array_combine($this->_types, array_map(function($type) use ($media) {
			$mtype = $media::invokeMethod('_types', array($type));
			return current((array) $mtype);
		}, $this->_types));
	}

	/**
	 * Add a header to rendered message, or return a single header or full header list.
	 *
	 * @param string $key
	 * @param string $value
	 * @return array
	 */
	public function headers($key = null, $value = null) {
		if (is_string($key) && strpos($key, ':') === false) {
			if ($value === null) {
				return isset($this->_headers[$key]) ? $this->_headers[$key] : null;
			}
			if ($value === false) {
				unset($this->_headers[$key]);
				return $this->_headers;
			}
		}

		if ($value) {
			$this->_headers = array_merge($this->_headers, array($key => $value));
		} else {
			foreach ((array) $key as $header => $value) {
				if (!is_string($header)) {
					if (preg_match('/(.*?):(.+)/i', $value, $match)) {
						$this->_headers[$match[1]] = trim($match[2]);
					}
				} else {
					$this->_headers[$header] = $value;
				}
			}
		}
		$headers = array();

		foreach ($this->_headers as $key => $value) {
			$headers[] = "{$key}: {$value}";
		}
		return $headers;
	}

	/**
	 * Add body parts.
	 *
	 * @param mixed $type
	 * @param mixed $data
	 * @param array $options
	 *        - `'buffer'`: split the body string
	 * @return array
	 */
	public function body($type = null, $data = null, $options = array()) {
		if (is_null($type)) {
		    return $this->_body;
		} else {
		    $default = array('buffer' => null);
		    $options += $default;
		    if (!isset($this->_body[$type])) {
			$this->_body[$type] = array();
		    }
		    $this->_body[$type] = array_merge((array) $this->_body[$type], (array) $data);
		    $body = join("\r\n", $this->_body[$type]);
		    return ($options['buffer']) ? str_split($body, $options['buffer']) : $body;
		}
	}

	/**
	 * Allow access on certain properties: `'to'`, `'from'`, `'subject'`, 
	 * `'charset'` and `'types'`.
	 */
	public function __call($method, $args) {
	    if (in_array($method, array('to', 'from', 'subject', 'charset', 'types'))) {
		$name = "_{$method}";
		if (count($args) == 1) {
		    return $this->$name = $args[0];
		} else {
		    return $this->$name;
		}
	    } else {
		$class = get_class($this);
		throw new BadMethodCallException("Method `{$method}` not defined or handled in class `{$class}`.");
	    }
	}
}

?>