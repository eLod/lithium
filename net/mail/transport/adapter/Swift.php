<?php

namespace lithium\net\mail\transport\adapter;

use Swift_MailTransport;
use Swift_SendmailTransport;
use Swift_SmtpTransport;
use Swift_Mailer;
use Swift_Message;

class Swift extends \lithium\net\mail\Transport {
	protected $_options = array('to', 'from', 'charset', 'headers', 'body', 'types', 'subject');
	protected $_transport = array('host', 'port', 'username', 'password', 'encryption', 'command');

	public function deliver($message, array $options = array()) {
		$options = $this->_options($message, $options);
		$config = $this->_config;
		$transport_options = array_combine($this->_transport, array_map(function($name) use ($options, $config) {
		    if (isset($options[$name])) {
			return $options[$name];
		    } else if (isset($config[$name])) {
			return $config[$name];
		    } else {
			return null;
		    }
		}, $this->_transport));
		$transport = isset($options['transport']) ? $options['transport'] : null;
		switch ($transport) {
			case 'mail':
				$transport = Swift_MailTransport::newInstance();
				break;
			case 'sendmail':
				$transport = Swift_SendmailTransport::newInstance($transport_options['command']);
				break;
			case 'smtp':
			default:
				$transport = Swift_SmtpTransport::newInstance();
				foreach (array('host', 'port', 'username', 'password', 'encryption') as $prop) {
					if (isset($transport_options[$prop])) {
						$method = "set" . ucfirst($prop);
						$transport->$method($transport_options[$prop]);
					}
				}
		}
		$mailer = Swift_Mailer::newInstance($transport);
		$swift_message = Swift_Message::newInstance()
			->setSubject($options['subject'])
			->setFrom($options['from'])
			->setTo($options['to']);
		foreach ($options['types'] as $type => $ctype) {
			$swift_message->addPart(join("\n", $options['body'][$type]), $ctype);
		}
		$headers = $swift_message->getHeaders();
		foreach ($options['headers'] as $header => $value) {
			$headers->addTextHeader($header, $value);
		}
		return $mailer->send($swift_message);
	}
}

?>