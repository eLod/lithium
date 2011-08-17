<?php

namespace lithium\net\mail\transport\adapter;

class Simple extends \lithium\net\mail\Transport {
	public function deliver($message, array $options = array()) {
		$options = $this->_options($message, $options);
		$addresses = array('from', 'to');
		foreach ($addresses as $address) {
			$header = ucfirst($address);
			$$address = $this->address($options[$address]);
			if (!isset($options['headers'][$header])) {
				$options['headers'][$header] = $$address;
			}
		}
		$subject = $options['subject'];
		$boundary = uniqid('LI3_MAILER_SIMPLE_');
		$options['headers']['MIME-Version'] = "1.0";
		$options['headers']['Content-Type'] = "multipart/alternative; boundary=\"{$boundary}\"";
		$message = "This is a multi-part message in MIME format.\n\n";
		foreach ($options['types'] as $type => $ctype) {
			$message .= "--{$boundary}\n";
			$message .= "Content-Type: {$ctype};charset=\"{$options['charset']}\"\n\n";
			$message .= wordwrap(join("\n", $options['body'][$type]), 70) . "\n";
		}
		$message .= "--{$boundary}--";
		$headers = join("\r\n", array_map(function($name, $value) {
			return "{$name}: {$value}";
		}, array_keys($options['headers']), $options['headers']));
		return mail($to, $subject, $message, $headers);
	}

	protected function address($address) {
		if (is_array($address)) {
			return join(", ", array_map(function($name, $address) {
				return "{$name} <{$address}>";
			}, array_keys($address), $address));
		} else {
			return $address;
		}
	}
}

?>