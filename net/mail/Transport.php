<?php

namespace lithium\net\mail;

abstract class Transport extends \lithium\core\Object {
	protected $_options = array('to', 'from', 'charset', 'headers', 'body', 'types', 'subject');

	abstract public function deliver($message, array $options = array());

	protected function _options($message, array $options = array()) {
	    $config = $this->_config;
	    $values = array_map(function($name) use ($message, $options, $config) {
		if (isset($options[$name])) {
		    return $options[$name];
		} else if (isset($config[$name])) {
		    return $config[$name];
		} else {
		    return $message->$name();
		}
	    }, $this->_options);
	    return array_combine($this->_options, $values);
	}
}

?>