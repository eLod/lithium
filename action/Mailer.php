<?php

namespace lithium\action;

use lithium\util\Inflector;
use BadMethodCallException;

/**
 * The `Mailer` class is the fundamental building block for sending mails within your application.
 *
 * @see lithium\net\mail\Media
 * @see lithium\net\mail\Delivery
 * @see lithium\action\Mailer::deliver()
 */
class Mailer extends \lithium\core\StaticObject {
	/**
	 * Holds extra configurations per message. See `Mailer::message()`.
	 *
	 * @see lithium\action\Mailer::message()
	 * @var array
	 */
	protected static $_messages = array('test2' => array('layout' => false));

	/**
	 * Class dependencies.
	 *
	 * @var array
	 */
	protected static $_classes = array(
	    'media' => 'lithium\net\mail\Media',
	    'delivery' => 'lithium\net\mail\Delivery',
	    'message' => 'lithium\net\mail\Message'
	);

	/**
	 * Create a message.
	 */
	static public function message($message, array $options = array()) {
		return static::_filter(__FUNCTION__, compact('options'), function($self, $params) {
		    $options = $params['options'];
		    $class = isset($options['class']) ? $options['class'] : 'message';
		    unset($options['class']);
		    return $self::invokeMethod('_instance', array($class, $options));
		});
	}

	/**
	 * Get delivery adapter.
	 */
	static public function transport($name) {
	    $delivery = static::$_classes['delivery'];
	    return $delivery::adapter($name);
	}

	/**
	 * Deliver a message.
	 */
	static public function deliver($message, array $options = array()) {
		$options = static::_options($message, $options);
		$delivery = isset($options['delivery']) ? $options['delivery'] : 'default';
		$data = isset($options['data']) ? $options['data'] : array();
		unset($options['delivery']);
		unset($options['data']);

		$class = get_called_class();
		$name = preg_replace('/Mailer$/', '', substr($class, strrpos($class, "\\") + 1));
		$options += array(
			'mailer' => Inflector::underscore($name),
			'template' => $message
		);
		$message = static::message($message, $options);
		$transport = static::transport($delivery);

		$media = static::$_classes['media'];
		$params = compact('options', 'data', 'message', 'transport');
		return static::_filter(__FUNCTION__, $params, function($self, $params) use ($media) {
			extract($params);
			$media::render($message, $data, $options);
			return $transport->deliver($message, $options);
		});
	}

	/**
	 * Allows the use of syntactic-sugar like `Mailer::deliverTestWithLocal()` instead of
	 * `Mailer::deliver('test', array('delivery' => 'local'))`.
	 *
	 * @see lithium\action\Mailer::deliver()
	 * @link http://php.net/manual/en/language.oop5.overloading.php PHP Manual: Overloading
	 *
	 * @throws BadMethodCallException On unhandled call, will throw an exception.
	 * @param string $method Method name caught by `__callStatic()`.
	 * @param array $params Arguments given to the above `$method` call.
	 * @return mixed Results of dispatched `Mailer::deliver()` call.
	 */
	static public function __callStatic($method, $params) {
		preg_match('/^deliver(?P<message>\w+)$|^deliver(?P<message>\w+)With(?P<delivery>\w+)$)/', $method, $arg);
		if ($arg) {
			$message = Inflector::underscore($arg['message']);
			$transport = isset($arg['delivery']) ? Inflector::underscore($arg['delivery']) : 'default';
			return static::deliver($message, $params + compact('delivery'));
		} else {
			$class = get_called_class();
			throw new BadMethodCallException("Method `{$method}` not defined or handled in class `{$class}`.");
		}
	}

	/**
	 * Get options for a given message.
	 */
	static protected function _options($message, array $options = array()) {
		if (isset($options[0])) {
			$data = $options;
			$to = $data[0];
			unset($data[0]);
			if (isset($data['subject'])) {
			    $subject = $data['subject'];
			    unset($data['subject']);
			}
			$options = compact('to', 'data');
			if (isset($subject)) {
			    $options['subject'] = $subject;
			}
		}
		if (array_key_exists($message, static::$_messages)) {
			$options = array_merge_recursive((array) static::$_messages[$message], $options);
		}
		return $options;
	}
}

?>