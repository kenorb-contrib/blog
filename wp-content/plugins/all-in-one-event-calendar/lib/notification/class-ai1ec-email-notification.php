<?php

/**
 * Comcrete implementation for email notifications.
 *
 * @author     Timely Network Inc
 * @since      2013.04.22
 *
 * @package    AllInOneEventCalendar
 * @subpackage AllInOneEventCalendar.Lib.Notification
 */

class Ai1ec_Email_Notification extends Ai1ec_Notification {

	/**
	 * @var string
	 */
	private $subject;
	
	/**
	 * @var array
	 */
	private $translations = array();

	/**
	 * @param array: $translations
	 */
	public function set_translations( array $translations ) {
		$this->translations = $translations;
	}

	public function __construct( array $recipients, $message, $subject ) {
		parent::__construct( $recipients, $message );
		$this->subject = $subject;
	}

	public function send() {
		$recipients = implode( ';', $this->recipients );
		$this->parse_text();
		return wp_mail( $recipients, $this->subject, $this->message );
	}

	private function parse_text() {
		$this->message = strtr( $this->message, $this->translations );
		$this->subject = strtr( $this->subject, $this->translations );
	}
}