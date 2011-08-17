<?php

namespace lithium\net\mail;

use lithium\net\mail\MediaException;

/**
 * The mail `Media` class facilitates content-type mapping (mapping between content-types and file
 * extensions), handling static assets and globally configuring how the framework handles output in
 * different formats for generating and sending emails.
 */
class Media extends \lithium\net\http\Media {
	/**
	 * Renders data (usually the result of a mailer delivery action) and generates a string
	 * representation of it, based on the types of expected output.
	 *
	 * @param object $message A reference to a Message object into which the operation will be
	 *        rendered. The content of the render operation will be assigned to the `$body`
	 *        property of the object.
	 * @param mixed $data
	 * @param array $options
	 * @return void
	 * @filter
	 */
	public static function render(&$message, $data = null, array $options = array()) {
		$params = array('message' => &$message) + compact('data', 'options');
		$types = static::_types();
		$handlers = static::_handlers();

		static::_filter(__FUNCTION__, $params, function($self, $params) use ($types, $handlers) {
			$defaults = array('encode' => null, 'template' => null, 'layout' => 'default', 'view' => null);
			$message =& $params['message'];
			$data = $params['data'];
			$options = $params['options'] + array('types' => $message->types());

			foreach($options['types'] as $type => $ctype) {
				if (!isset($handlers[$type])) {
					throw new MediaException("Unhandled media type `{$type}`.");
				}
				$handler = $options + $handlers[$type] + $defaults + array('type' => $type);
				$filter = function($v) { return $v !== null; };
				$handler = array_filter($handler, $filter) + $handlers['default'] + $defaults;

				$message->body($type, $self::invokeMethod('_handle', array($handler, $data, $message)));
			}
		});
	}

	/**
	 * Called by `Media::render()` to render message content. Given a content handler and data,
	 * calls the content handler and passes in the data, receiving back a rendered content string.
	 *
	 * @see lithium\net\mail\Message
	 * @param array $handler
	 * @param array $data
	 * @param object $message A reference to the `Message` object for this delivery.
	 * @return string
	 * @filter
	 */
	protected static function _handle($handler, $data, &$message) {
		$params = array('message' => &$message) + compact('handler', 'data');

		return static::_filter(__FUNCTION__, $params, function($self, $params) {
			$message = $params['message'];
			$handler = $params['handler'];
			$data = $params['data'];
			$options = $handler;

			switch (true) {
				case ($handler['template'] === false) && is_string($data):
					return $data;
				case $handler['view']:
					unset($options['view']);
					$instance = $self::view($handler, $data, $message, $options);
					return $instance->render('all', (array) $data, $options);
				default:
					throw new MediaException("Could not interpret type settings for handler.");
			}
		});
	}


	/**
	 * Helper method for listing registered media types. Returns all types, or a single
	 * content type if a specific type is specified.
	 *
	 * @param string $type Type to return.
	 * @return mixed Array of types, or single type requested.
	 */
	protected static function _types($type = null) {
		$types = static::$_types + array(
			'html'         => array('text/html'),
			'htm'          => array('alias' => 'html'),
			'text'         => 'text/plain',
			'txt'          => array('alias' => 'text')
		);

		if (!$type) {
			return $types;
		}
		if (strpos($type, '/') === false) {
			return isset($types[$type]) ? $types[$type] : null;
		}
		if (strpos($type, ';')) {
			list($type) = explode(';', $type);
		}
		$result = array();

		foreach ($types as $name => $cTypes) {
			if ($type == $cTypes || (is_array($cTypes) && in_array($type, $cTypes))) {
				$result[] = $name;
			}
		}
		if (count($result) == 1) {
			return reset($result);
		}
		return $result ?: null;
	}

	/**
	 * Helper method for listing registered type handlers. Returns all handlers, or the
	 * handler for a specific media type, if requested.
	 *
	 * @param string $type The type of handler to return.
	 * @return mixed Array of all handlers, or the handler for a specific type.
	 */
	protected static function _handlers($type = null) {
		$handlers = static::$_handlers + array(
			'default' => array(
				'view'     => 'lithium\template\View',
				'paths'    => array(
					'template' => array(
					    '{:library}/mails/{:mailer}/{:template}.{:type}.php',
					    '{:library}/mails/{:template}.{:type}.php'
					),
					'layout'   => '{:library}/mails/layouts/{:layout}.{:type}.php',
					'element'  => '{:library}/mails/elements/{:template}.{:type}.php'
				)
			),
			'html' => array(),
			'text' => array()
		);

		if ($type) {
			return isset($handlers[$type]) ? $handlers[$type] : null;
		}
		return $handlers;
	}
}

?>