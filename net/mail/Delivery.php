<?php

namespace lithium\net\mail;

/**
 * The `Delivery` class provides a consistent interface for configuring
 * email delivery.
 *
 * A simple example configuration (**please note**: you'll need the
 * SwiftMailer library for the `'Swift'` adapter to work):
 *
 * {{{Delivery::config(array(
 *     'local' => array('adapter' => 'Simple', 'from' => 'you@example.com'),
 *     'default' => array(
 *         'adapter' => 'Swift',
 *         'from' => 'you@example.com',
 *         'transport' => 'smtp',
 *         'host' => 'example.com'
 *     )
 * ));}}}
 */
class Delivery extends \lithium\core\Adaptable {
	/**
	 * A dot-separated path for use by `Libraries::locate()`. Used to look up the correct type of
	 * adapters for this class.
	 *
	 * @var string
	 */
	protected static $_adapters = 'adapter.net.mail.transport';
}

?>