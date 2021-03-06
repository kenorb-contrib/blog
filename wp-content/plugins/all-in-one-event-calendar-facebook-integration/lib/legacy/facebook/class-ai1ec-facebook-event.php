<?php
/**
 * This class handle facebook events.
 *
 * @author The Seed Network
 *
 *
 */
class Ai1ec_Facebook_Event extends Ai1ec_Base {
	// Exception that happens when something goes wrong
	const EX_UNSUPPORTED_POST_REQ = "Unsupported post request.";
	// This exception happens when you try to update a deleted event
	const EX_PERMISSION_ERROR     = "(#200) Permissions error";
	// This is usually throwen when you try to create an event in the past
	const EX_INVALID_TIME         = "(#100) You must enter a valid date and time.";
	// This is what facebook throws when the exported event on Facebook is deleted and we try to update it (from 4th July 2012)
	const EX_INVALID_EID          = "(#150) Invalid eid";

	/**
	 * The id of the event
	 *
	 * @var bigint
	 */
	private $id;
	/**
	 * the name of the event
	 *
	 * @var string
	 */
	private $name;
	/**
	 * the description of the event
	 *
	 * @var string
	 */
	private $description;
	/**
	 * The start time of the event
	 *
	 * @var int
	 */
	private $start_time;
	/**
	 * The end time of the event
	 *
	 * @var int
	 */
	private $end_time;
	/**
	 * The location of the event
	 *
	 * @var string
	 */
	private $location;
	/**
	 * The venue of the event
	 *
	 * @var string
	 */
	private $venue;
	/**
	 * 
	 * @var string
	 */
	private $access_token;
	/**
	 *
	 * @return the $id
	 */
	public function get_id() {
		return $this->id;
	}
	/**
	 *
	 * @return the $name
	 */
	public function get_name() {
		return $this->name;
	}
	/**
	 *
	 * @return the $description
	 */
	public function get_description() {
		return $this->description;
	}
	/**
	 *
	 * @return the $start_time
	 */
	public function get_start_time() {
		return $this->start_time;
	}
	/**
	 *
	 * @return the $end_time
	 */
	public function get_end_time() {
		return $this->end_time;
	}
	/**
	 *
	 * @return the $location
	 */
	public function get_location() {
		return $this->location;
	}
	/**
	 *
	 * @param $id bigint
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}
	/**
	 *
	 * @param $name string
	 */
	public function set_name( $name ) {
		$this->name = $name;
	}
	/**
	 *
	 * @param $description string
	 */
	public function set_description( $description ) {
		$this->description = $description;
	}
	/**
	 *
	 * @param $start_time number
	 */
	public function set_start_time( $start_time ) {
		$this->start_time = $start_time;
	}
	/**
	 *
	 * @param $end_time number
	 */
	public function set_end_time( $end_time ) {
		$this->end_time = $end_time;
	}
	/**
	 *
	 * @param $location string
	 *
	 */
	public function set_location( $location ) {
		$this->location = $location;
	}

	/**
	 *
	 * @param $country string
	 */
	public function set_country( $country ) {
		$this->country = $country;
	}
	/**
	 * Converts the time returned from facebook to GMT. Since the 4th of July Facebook started rolling out changes, so we need to handle both cases, a timestamp and a string (which should be the default as of now)
	 *
	 * @param $timestamp int
	 *        the timestamp returned from Facebook
	 *
	 * @param $user_timezone int
	 *        the timezone of the user currently logged into Facebook
	 *
	 * @return number the timestamp converted into GMT
	 */
	static public function convert_facebook_time_to_gmt_timestamp( $time, $user_timezone ) {
		$timestamp = 0;
		if( is_numeric( $time ) ) {
			// This offset does the first conversion, you see the time the user has
			// entered
			/**
			 * @TODO Justas attention here there is teh function. i backported everything 
			 * and import is correct. 
			 */
			$offset = Ai1ec_Time_Utility_Legacy::get_timezone_offset( 'PST', 'UTC', $time );
			// We add the user offset to get gmt
			$offset += ( ( int ) $user_timezone * 3600 );
			$timestamp = $time - $offset;
		} else {
			$timestamp = strtotime( $time );
		}

		return $timestamp;
	}
	/**
	 * Return the current timestamp to make correct queries using restrictions
	 * for starting time.
	 *
	 * This works as follows, it takes the time on the current server. Imagine
	 * i'm at 22.30 on GMT + 2.
	 * Facebook treats this as Pacific Time so i calculate the offset between
	 * PST and UTC ( -7 ), i take into account the offset from GMT ( that's 2 i
	 * subtract from -7 so i get -9) and then i subtract the offset of the
	 * server from my starting time.
	 *
	 * @return number
	 */
	static public function get_facebook_actual_time( $timestamp = NULL ) {
		if ( $timestamp === NULL ) {
			$timestamp = $this->_registry->get( 'date.system' )->current_time();
		}
		/**
		 * @TODO Justas attention here there is teh function. i backported everything
		 * and import is correct.
		 */
		$offset = Ai1ec_Time_Utility_Legacy::get_timezone_offset( 'UTC', 'PST', $timestamp );
		$offset -= ( Ai1ec_Time_Utility_Legacy::get_gmt_offset() * 3600 );
		return $timestamp - $offset;
	}

	/**
	 * Substract one day from events
	 *
	 * @param string $timestamp
	 * @return number
	 */
	static public function get_facebook_actual_time_for_events( $timestamp = NULL ) {
		$timestamp = self::get_facebook_actual_time( $timestamp );
		$timestamp -= 60 * 60 * 24;
		return $timestamp;
	}

	/**
	 * Remove the last character from a string if it's a comma.
	 *
	 * @param string $str The string to check
	 *
	 * @return string the string with the last char removed if it was a comma
	 */
	private function remove_last_char_if_comma( $str ) {
		if( empty( $str ) ) {
			return '';
		}
		$lng = strlen( $str );
		if( $str[$lng - 1] === ',' ) {
			$str = substr( $str,0 ,-1 );
		}
		return $str;
	}
	/**
	 * The street field in Ai1ec event is made up also of city and country and zip, so i have tro strip out things
	 * when posting them to facebook
	 *
	 * @param string $street_to_trim
	 *
	 * @param string $zip
	 *
	 * @param string $country
	 *
	 * @param string $city
	 */
	private function get_street_from_ai1ec_event_street( $street_to_trim, $zip, $country, $city ) {
		// We must take the zip code, the city and the country from the address.
		$array_for_replace = array(
				$zip        => '',
				$country    => '',
				$city . "," => '',
		);
		// We trim the result
		$street = trim( strtr( $street_to_trim, $array_for_replace ) );
		// We take away a trailing comma.
		$street = $this->remove_last_char_if_comma( $street );
		if( empty( $street ) ) {
			$street = $street_to_trim;
		}
		return $street;
	}
	/**
	 * Populate event taking data from an ai1ec_event
	 *
	 * @param Ai1ec_Event $event
	 */
	public function populate_event_from_ai1ec_event( Ai1ec_Event $event ) {
		$this->start_time  = self::get_facebook_actual_time( $event->get( 'start' )->format_to_gmt() );
		$this->end_time    = self::get_facebook_actual_time( $event->get( 'end' )->format_to_gmt() );
		$this->name        = apply_filters(
			'the_title',
			$event->get( 'post' )->post_title,
			$event->get( 'post_id' )
		);
		$this->description = apply_filters(
			'the_content',
			$event->get( 'post' )->post_content
		);

		$this->location      = $this->get_street_from_ai1ec_event_street( 
			$event->get( 'address' ), 
			$event->get( 'postal_code' ), 
			$event->get( 'country' ), 
			$event->get( 'city' )
		);
		$this->venue    = $event->get( 'venue' );
	}
	/**
	 * Populate events variable from an ai1ec event and the posted data
	 *
	 * @param $post array
	 *        The posted data
	 *
	 * @param $event Ai1ec_Event
	 *        The event which has been saved.
	 *
	 */
	public function populate_event_from_post_and_ai1ec_event( array $post, Ai1ec_Event $event, Ai1ec_Facebook_Current_User $current_user ) {
		$facebook_meta = $this->_registry->get( 'facebook.meta' );
		$meta = $facebook_meta->get_facebook_meta( $event->get( 'post_id' ) );

		// We must set the id, that means that we would be updating later on. First we check if the
		// eid is set and that the event was not imported from facebook
		if( $meta['facebook_eid'] && $meta['facebook_status'] !== Ai1ecFacebookConnectorPlugin::FB_IMPORTED_EVENT ) {
			// Then we check that the eid is not 0
			$this->id = ( int ) $meta['facebook_eid'] === 0 ? NULL : $meta['facebook_eid'];
		}
		/** 
		 * @TODO Justas attention here format_to_gmt() anf format() return the same time (UTC) probably because you have 
		 * added a separate field for the timezone. How should i handle it?Times are not exported correctly on Facebook.
		 * To make an example, if i esxport an event which is from 10PM to 11PM in UTC +1 here is seen as 10PM and 11PM
		 * UTC + 0 thus is not correctly exported.
		 */
		// Starting from september 2012 Facebook doesn't accept timestamps anymore. No need for conversions.
		if( isset( $_POST['ai1ec_all_day_event'] ) ) {
			$this->start_time  = date( 'Y-m-d\TH:i:sP', $event->get( 'start' )->format_to_gmt() );
			$this->end_time    = date( 'Y-m-d\TH:i:sP', $event->get( 'end' )->format_to_gmt() - 60 );
		} else {
			$this->start_time  = date( 'Y-m-d\TH:i:sP', $event->get( 'start' )->format_to_gmt() );
			$this->end_time    = date( 'Y-m-d\TH:i:sP', $event->get( 'end' )->format_to_gmt() );
		}

		$this->name        = $post['post_title'];
		$this->description = $post['content'];

		// Clone the address. We must clone it as we are handling a refernce and we don't want to modify this.
		$street = $post['ai1ec_address'] . "";
		$this->location = $this->get_street_from_ai1ec_event_street( $street, $post['ai1ec_postal_code'], $post['ai1ec_country'], $post['ai1ec_city'] );
		$this->venue   = $post['ai1ec_venue'];
		if( $event->get( 'facebook_user' ) ) {
			$this->access_token = $this->get_facebook_page_access_token_from_id( $event->get( 'facebook_user' ) );
		}
		// let's check if the radio were present

		if( isset( $post[Ai1ecFacebookConnectorPlugin::EXPORT_PAGE_RADIO_NAME] ) ) {
			// get the id
			$id = ( string ) $post[Ai1ecFacebookConnectorPlugin::EXPORT_PAGE_RADIO_NAME];

			if( ( string ) $current_user->get_id() !== $id ) {
				try {
					$this->access_token = $this->get_facebook_page_access_token_from_id( $id );
				} catch (Exception $e) {
					// no token found, do nothing for now and keep the normal flow
				}
			}
			
		}
	}
	
	/**
	 * Get the access token for a page from the id
	 * 
	 * @param string $id
	 * @throws Exception
	 */
	private function get_facebook_page_access_token_from_id( $id ) {
		try {
			$facebook_plugin = $this->_registry->get( 'calendar-feeds.facebook' );
			return $facebook_plugin->get_facebook_page_token_from_id( $id );
		} catch ( Exception $e ) {
			throw $e;
		}

	}
	/**
	 * Saves the data to facebook
	 *
	 * @param $facebook Ai1ec_Facebook_Proxy
	 *        an instance of the facebook class
	 * @param $data array
	 *        the data to send to Facebook
	 * @throws WP_FacebookApiException if something goes wrong
	 */
	private function save( Ai1ec_Facebook_Proxy $facebook, array $data ) {
		try {
			$target = 'me';
			if( isset( $data['page_id'] ) ) {
				$target = $data['page_id'];
				unset( $data['page_id'] );
			}
			$result = $facebook->api( "/$target/events", "POST", $data );
		} catch ( WP_FacebookApiException $e ) {
			throw $e;
		}
		return $result;
	}
	/**
	 * Update the data on Facebook
	 *
	 * @param Ai1ec_Facebook_Proxy $facebook
	 *        an instance of the facebook class
	 * @param array $data
	 *        the updated data to send to Facebook
	 * @throws WP_FacebookApiException
	 */
	private function update(  Ai1ec_Facebook_Proxy $facebook, array $data ) {
		$id = $data['id'];
		unset( $data['id'] );
		try {
			$res = $facebook->api( "/$id", "POST", $data );
		} catch (WP_FacebookApiException $e) {
			throw $e;
		}
	}
	/**
	 * Saves / updates the event to facebook.
	 * The action that is taken depends from the $id: if it's set, we perform
	 * an update, otherwise we do an insert
	 *
	 * @param $facebook Ai1ec_Facebook_Proxy
	 *        an instance of the Facebook class
	 * @return array void array with the id of the newly created event
	 */
	public function save_to_facebook( Ai1ec_Facebook_Proxy $facebook, $page_id = null ) {
		$data_to_send = get_object_vars( $this );
		// if the access token is present it means we must post to a page.
		if( empty( $data_to_send['access_token'] ) ) {
			unset( $data_to_send['access_token'] );
		}
		if ( strlen( $data_to_send['name'] ) > 74 ) {
			$data_to_send['name'] = substr( $data_to_send['name'], 0, 74 );
		}
		if( null !== $page_id ) {
			$data_to_send['page_id'] = $page_id;
		}

		$data_to_send['description'] = strip_shortcodes( 
			strip_tags( $data_to_send['description'] )
		);

		// if the access token is present it means we must post to a page.
		if( empty( $data_to_send['access_token'] ) ) {
			unset( $data_to_send['access_token'] );
		}
		if( null !== $page_id ) {
			$data_to_send['page_id'] = $page_id;
		}

		if ( $data_to_send['id'] === NULL ) {
			$result = array ();
			unset( $data_to_send['id'] );
			try {
				$result = $this->save( $facebook, $data_to_send );
			} catch ( WP_FacebookApiException $e ) {
					$message = array(
							"label"        => __( 'All-in-One Event Calendar Facebook Event Creation Error', AI1ECFI_PLUGIN_NAME ),
							"message"      => __( "Something went wrong while creating the event on Facebook.", AI1ECFI_PLUGIN_NAME ),
							"message_type" => "error"
					);
					if( $e->getMessage() === self::EX_INVALID_TIME ) {
						$message['message'] .= '<br />' . __( "You didn't enter a valid date. Remember that only events which end in the future can be exported to Facebook.", AI1ECFI_PLUGIN_NAME );
					} elseif( isset( $data_to_send['access_token'] ) ) {
						$message['message'] .= '<br />' . __( "If you are trying to create an event for a page, try refreshing the access Tokens. This is the error message: " , AI1ECFI_PLUGIN_NAME ) . $e->getMessage();
					} else {
						$message['message'] .= '<br />' . __( "This is the error message: " , AI1ECFI_PLUGIN_NAME ) . $e->getMessage();
					}
					update_option( Ai1ecFacebookConnectorPlugin::FB_OPTION_CRON_NOTICE, $message );
			}
			return $result;
		} else {
			try {
				$this->update( $facebook, $data_to_send );
			} catch ( WP_FacebookApiException $e ) {
				// If the message is one of those three, the event on Facebook was deleted, so i try to create it again
				if( in_array( $e->getMessage(), array( self::EX_UNSUPPORTED_POST_REQ, self::EX_PERMISSION_ERROR, self::EX_INVALID_EID ) ) ) {
					// Set the id to null
					$this->id = NULL;
					// Call this function recursively.
					return $this->save_to_facebook( $facebook );
				} else {
					$message = array(
							"label"        => __( 'All-in-One Event Calendar Facebook Event Update Error', AI1ECFI_PLUGIN_NAME ),
							"message"      => __( "Something went wrong while updating the event on Facebook.", AI1ECFI_PLUGIN_NAME ),
							"message_type" => "error"
					);
					$message['message'] .= '<br />' . __( "This is the error message: {$e->getMessage()}", AI1ECFI_PLUGIN_NAME );
					update_option( Ai1ecFacebookConnectorPlugin::FB_OPTION_CRON_NOTICE, $message );
				}
			}
		}
	}
	/**
	 * When a user deletes an event from ai1ec and that event was exported to facebook, we try do delete the event from FB.
	 *
	 * @param Ai1ec_Facebook_Proxy $facebook
	 */
	public function delete_from_facebook( Ai1ec_Facebook_Proxy $facebook ) {
		$id = $this->id;
		try {
			$result = $facebook->api( "/$id", "DELETE" );
		} catch ( WP_FacebookApiException  $e ) {
			// Usually if the event has already been canceled on Facebook those are the messages, if we get one of them
			// we do nothing as our intention was deleting the event
			if( ! in_array( $e->getMessage(), array( self::EX_UNSUPPORTED_POST_REQ, self::EX_PERMISSION_ERROR, self::EX_INVALID_EID ) ) ) {
				$message = array(
						"label"        => __( 'All-in-One Event Calendar Facebook Event Deletion Error', AI1ECFI_PLUGIN_NAME ),
						"message"      => __( "Something went wrong while deleting the event from Facebook.", AI1ECFI_PLUGIN_NAME ),
						"message_type" => "error"
				);
				$message['message'] .= '<br />' . __( "This is the error message: {$e->getMessage()}", AI1ECFI_PLUGIN_NAME );
				update_option( Ai1ecFacebookConnectorPlugin::FB_OPTION_CRON_NOTICE, $message );
			}
		}
	}
	/**
	 * Check if the current id is present in the user_events table. This is useful to know if the event can be deleted.
	 *
	 * @return boolean TRUE if the event is present, FALSE if it's not.
	 */
	public function check_if_someone_refers_the_event_in_the_user_events_table() {
		global $wpdb;
		$user_event_table = Ai1ec_Facebook_Factory::get_user_events_table();
		// Check if at least on row exist
		$query = $wpdb->prepare( "SELECT 1 as how_many FROM $user_event_table where eid = %s LIMIT 1", $this->id );
		$how_many = $wpdb->get_var( $query );
		return ( int ) $how_many === 1;
	}
	/**
	 * Get the wordpress post id for this facebook event
	 *
	 * @return int the wordpress post id
	 */
	public function get_wordpress_post_id_from_facebook_event_id() {
		global $wpdb;
		$ai1ec_event_Table = Ai1ec_Facebook_Factory::get_events_table();
		$query = $wpdb->prepare(
			"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_ai1ec_facebook_eid' AND meta_value = %s",
			$this->id
		);
		$post_id = $wpdb->get_var( $query );
		return $post_id;
	}
}
