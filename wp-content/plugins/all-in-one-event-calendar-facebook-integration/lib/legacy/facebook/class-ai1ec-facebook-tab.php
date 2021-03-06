<?php
/**
 * @author time.ly
 *
 * This class handles the rendering of the HTML for the Facebook Tab.
 */
class Ai1ec_Facebook_Tab extends Ai1ec_Base {
	const FB_MULTISELECT_NAME = 'ai1ec-facebook-';
	/**
	 * Holds any informational messages that will be printed
	 *
	 * @var array
	 */
	private $_info_messages;
	/**
	 * Holds any error messages that will be printed
	 *
	 * @var array
	 */
	private $_error_messages;

	/**
	 * @param array: $_error_messages
	 */
	public function set_error_messages( array $_error_messages ) {
		$this->_error_messages = $_error_messages;
	}

	/**
	 *
	 * @return string
	 */
	public function render_question_mark_for_facebook() {
		$html = '<a class="ai1ec-btn ai1ec-btn-default" ' .
			'target="_blank" ' .
			'href="http://support.time.ly/connect-your-calendar-to-facebook/" ' .
			'title="' . __( 'How do I use Facebook import?', AI1ECFI_PLUGIN_NAME ) .
			'"><i class="ai1ec-fa ai1ec-fa-question-circle ai1ec-fa-lg"></i></a>';
		return $html;
	}

	/**
	 * @param array: $_info_messages
	 */
	public function set_informational_messages( array $_info_messages ) {
		$this->_info_messages = $_info_messages;
	}

	/**
	 * Creates the HTML for the specified multiselect
	 *
	 * @param string $type the type of multiselect to return
	 *
	 * @param $current_id The id of the currently logged on user which must be excluded from subscribers and multiselects
	 *
	 * @return string The HTML of the multiselect
	 */
	public function render_multi_select( $type, $current_id ) {
		$data = $this->get_data_for_multiselect( $type, $current_id );
		// If there is no data in the DB show a standard message.
		if ( count($data) === 0 ) {
			// Add the div so that the ajax refresh of the multiselect works as expecte also when there is no multiselect.
			return '<div class="ai1ec-facebook-multiselect">' . __( 'Nothing to display', AI1ECFI_PLUGIN_NAME ) . '</div>';
		}
		// Build the name.
		$name = self::FB_MULTISELECT_NAME . $type;

		// Start building the multiselect
		$html = "<select multiple='multiple' id='ai1ec-facebook-$type' class='ai1ec-facebook-multiselect ai1ec-form-control' name='{$name}[]' size='8'>";
		foreach ( $data as $option ) {
			// Build the final text.
			$text = $option['user_name'];
			// the value that will be posted is the user id.
			$value = $option['user_id'];
			$html .= "<option value='$value'>$text</option>";
		}
		// Close the select.
		$html .= "</select>";
		// Return it.
		return $html;
	}

	/**
	 * Renders the modal that is used to input facebook app id and secret
	 *
	 * @return string
	 */
	public function render_modal_for_facebook_app_id_and_secret_and_return_html( $app_id, $app_secret ) {
		$info = '<p><a ' .
			'href="http://support.time.ly/connect-your-calendar-to-facebook/"' .
			' target="_blank">' .
			__( 'How do I use Facebook import?', AI1ECFI_PLUGIN_NAME ) .
			'</a></p>';
		$label_app_id = esc_html__( Ai1ecFacebookConnectorPlugin::FB_APP_ID_DESCRIPTION_TEXT, AI1ECFI_PLUGIN_NAME );
		$label_app_secret = esc_html__( Ai1ecFacebookConnectorPlugin::FB_APP_SECRET_DESCRIPTION_TEXT, AI1ECFI_PLUGIN_NAME );
		$modal_body = <<<HTML
<div id="form_fields" class="ai1ec-form-horizontal">
	$info
	<div class="ai1ec-form-group">
		<label for="ai1ec_facebook_app_id_modal" id="label_app_id"
			class="ai1ec-control-label ai1ec-col-sm-4">
			$label_app_id
		</label>
		<div class="ai1ec-col-sm-8">
			<input type="text" name="ai1ec_facebook_app_id_modal"
				id="ai1ec_facebook_app_id_modal" value="$app_id" class="ai1ec-form-control">
		</div>
	</div>
	<div class="ai1ec-form-group">
		<label for="ai1ec_facebook_app_secret_modal" id="label_app_secret"
			class="ai1ec-control-label ai1ec-col-sm-4">
			$label_app_secret
		</label>
		<div class="ai1ec-col-sm-8">
			<input type="text" name="ai1ec_facebook_app_secret_modal"
				id="ai1ec_facebook_app_secret_modal" value="$app_secret" class="ai1ec-form-control">
		</div>
	</div>
</div>
HTML;
		$twitter_bootstrap_modal = $this->_registry->get(
			'html.element.legacy.bootstrap.modal', $modal_body
		);
		$twitter_bootstrap_modal->set_keep_button_text( esc_html__( "Save", AI1ECFI_PLUGIN_NAME ) );
		$twitter_bootstrap_modal->set_header_text( esc_html__( "Facebook Configuration", AI1ECFI_PLUGIN_NAME ) );
		$twitter_bootstrap_modal->set_id( "ai1ec_facebook_connect_modal" );
		return $twitter_bootstrap_modal->render_as_html();
	}

	/**
	 * Gets the data to populate the multiselect
	 *
	 * @param string $type
	 *
	 * @param $current_id The id of the currently logged on user which must be excluded from subscribers and multiselects
	 *
	 * @return array the results
	 */
	private function get_data_for_multiselect( $type, $current_id ) {
		global $wpdb;
		$table_name = Ai1ec_Facebook_Factory::get_plugin_table();
		$data = $wpdb->get_results( $wpdb->prepare(
				"
				SELECT
					user_id,
					user_name,
					user_pic
				FROM
					$table_name
				WHERE
					subscribed = 0 AND
					type = %s AND
					user_id != %s
				ORDER BY
					user_name ASC
				",
				$type,
				$current_id
		), ARRAY_A );
		return $data;
	}

	/**
	 * Gets the subscribers data from the db
	 *
	 * @param string $type the type to get
	 *
	 * @param $current_id The id of the currently logged on user which must be excluded from subscribers and multiselects
	 *
	 * @return array the results
	 */
	private function get_data_for_subscribed_items( $type, $current_id ) {
		global $wpdb;
		$table_name = Ai1ec_Facebook_Factory::get_plugin_table();
		$data = $wpdb->get_results( $wpdb->prepare(
				"
				SELECT
					user_id,
					user_name,
					user_pic,
					category,
					tag,
					comments_enabled,
					map_display_enabled
				FROM
					$table_name
				WHERE
					subscribed = 1 AND
					type = %s AND
					user_id != %s
				ORDER BY
					user_name ASC
				",
				$type,
				$current_id
		), ARRAY_A );
		return $data;
	}

	/**
	 * Calls the appropriate function to render either the multiselect or the subscriber of the specified types
	 *
	 * @param array $types the Facebook Graph Object types
	 *
	 * @param boolean $multiselect Whether we should render the multiselect or the subscribers
	 *
	 * @param $current_id The id of the currently logged on user which must be excluded from subscribers and multiselects
	 *
	 * @return array
	 */
	private function render_all_elements( array $types, $multiselect = TRUE, $current_id ) {
		$elements = array();
		$function = ($multiselect === TRUE) ? 'render_multi_select' : 'render_subscribed_items';
		foreach( $types as $type ) {
			$elements[$type] = $this->$function( $type, $current_id );
		}
		return $elements;
	}

	/**
	 * Create the text of the message that is visualized after the user refreshes the events
	 *
	 * @param array $response
	 *
	 * @return string
	 */
	public function create_refresh_message( $response ) {
		$type = isset( $response['type'] ) ? sprintf( __( "Syncing events for %s.", AI1ECFI_PLUGIN_NAME ), $response['type'] ) : '';
		$errors    = $response['errors'] ? __( 'Something went wrong while updating events, but some events might still be updated / inserted correctly', AI1ECFI_PLUGIN_NAME ) . "<br />" : '';
		$updated   = $response['events_updated'] > 0 ? sprintf( _n( " %d event was updated.", ' %d events were updated.', (int) $response['events_updated'], AI1ECFI_PLUGIN_NAME ), $response['events_updated'] ) . "<br />" : '';
		$inserted  = $response['events_inserted'] > 0 ? sprintf( _n( " %d event was added.", ' %d events were added.', (int) $response['events_inserted'], AI1ECFI_PLUGIN_NAME ), $response['events_inserted'] ) . "<br />" : '';
		$deleted   = $response['events_deleted'] > 0 ? sprintf( _n( " %d event was deleted.", ' %d events were deleted.', (int) $response['events_deleted'], AI1ECFI_PLUGIN_NAME ), $response['events_deleted'] ) . "<br />" : '';
		$to_return = $errors . $updated . $inserted . $deleted;
		return empty( $to_return ) ? $type . __( " No events to add or update.", AI1ECFI_PLUGIN_NAME ) : $type . $to_return ;
	}

	/**
	 * Render the html for the category and tags div
	 *
	 * @param int $category the category
	 * @param string $tag the tags
	 * @param int $comments_enabled enable comments on events
	 * @param int $map_display_enabled enable map display on events
	 *
	 * @return the HTML
	 */
	private function render_category_tag_div(
		$category,
		$tag,
		$comments_enabled,
		$map_display_enabled
	) {
		$category = explode( ',', $category );
		// Check if we have something.
		$category_empty = empty( $category ) || $category[0] === 0;
		$tag_empty = empty( $tag );
		$html = '';
		// If nothing is set, just return (by default category is 0 and tag ''
		if (
			$category_empty &&
			$tag_empty &&
			! empty( $comments_enabled ) &&
			empty( $map_display_enabled )
		) {
			return $html;
		}
		$html = '<div class="ai1ec-facebook-category-tag-wrapper">';
		if( ! $category_empty ) {
			$cats = array();
			foreach ( $category as $cat ) {
				if( empty( $cat ) ) {
					continue;
				}
				$feed_category = get_term( $cat, 'events_categories' );
				$cats[] = $feed_category->name;
			}
			$category_name = implode( '</strong>, <strong>', $cats );

			$label_category = __( "Event category: ", AI1ECFI_PLUGIN_NAME );

			$html .= "<span class='ai1ec-facebook-category'>$label_category<strong>$category_name</strong></span>";
		}
		if( ! $tag_empty ) {
			$tag_label = __( "Tag with: ", AI1ECFI_PLUGIN_NAME );
			$html .= "<span class='ai1ec-facebook-tag'>$tag_label<strong>$tag</strong></span>";
		}
		$html .= '<span class="ai1ec-facebook-comments">' .
			__( 'Allow comments', AI1ECFI_PLUGIN_NAME )
			. ': <strong>';
		$html .= ( $comments_enabled )
			? __( 'Yes', AI1ECFI_PLUGIN_NAME )
			: __( 'No',  AI1ECFI_PLUGIN_NAME );
		$html .= '</strong></span>';
		$html .= '<span class="ai1ec-facebook-map">' .
			__( 'Show map', AI1ECFI_PLUGIN_NAME )
			. ': <strong>';
		$html .= ( $map_display_enabled )
			? __( 'Yes', AI1ECFI_PLUGIN_NAME )
			: __( 'No',  AI1ECFI_PLUGIN_NAME );
		$html .= '</strong></span>';
		$html .= "</div>";
		return $html;
	}

	/**
	 * Renders the HTML for the subscribed items of the specified type.
	 *
	 * @param string $type
	 *
	 * @param $current_id The id of the currently logged on user which must be excluded from subscribers and multiselects
	 *
	 * @return string the HTML
	 */
	private function render_subscribed_items( $type, $current_id ) {
		$data = $this->get_data_for_subscribed_items( $type, $current_id );
		if ( count($data) === 0 ) {
			return __( 'No subscriptions yet.', AI1ECFI_PLUGIN_NAME );
		}
		$html = "";
		$chunked_data = array_chunk( $data, 2 );
		foreach ( $chunked_data as $row ) {
			$html .= '<div class="ai1ec-row">';
			foreach ( $row as $subscriber ) {
				$name         = esc_html__( $subscriber['user_name'] );
				$id           = $subscriber['user_id'];
				$pic          = $subscriber['user_pic'];
				$cat_tag_html = $this->render_category_tag_div( $subscriber["category"], $subscriber["tag"], $subscriber['comments_enabled'], $subscriber['map_display_enabled'] );
				$refresh_tip  = esc_attr__( 'Refresh these events', AI1ECFI_PLUGIN_NAME );
				$remove_tip   = esc_attr__( 'Unsubscribe from these events', AI1ECFI_PLUGIN_NAME );

				$html .= '<div class="ai1ec-facebook-subscriber ai1ec-col-sm-6"><div class="ai1ec-well ai1ec-well-sm">';
				if ( ! empty( $pic ) ) {
					$html .= <<<HTML
					<img src="$pic" alt="$name" class="ai1ec-facebook-pic ai1ec-pull-left">
HTML;
				}
				$html .= <<<HTML
					<img src="https://graph.facebook.com/$id/picture" alt="$name" class="ai1ec-facebook-pic ai1ec-pull-left">
					<div class="ai1ec-pull-right ai1ec-facebook-buttons ai1ec-btn-group">
						<a class="ai1ec-facebook-refresh ai1ec-btn ai1ec-btn-default ai1ec-btn-xs ai1ec-fa ai1ec-fa-refresh ai1ec-text-success" data-id="$id" title="$refresh_tip"></a>
						<a class="ai1ec-facebook-remove ai1ec-btn ai1ec-btn-default ai1ec-btn-xs ai1ec-fa ai1ec-fa-times ai1ec-text-danger" data-id="$id" title="$remove_tip"></a>
					</div>
					<img src="images/wpspin_light.gif" class="ajax-loading ajax-loading-user ai1ec-pull-right" alt="">
					<div class="ai1ec-facebook-name">$name</div>
					$cat_tag_html
				</div></div>
HTML;
			}
			$html .= '</div>';
		}
		return $html;
	}

	/**
	 * Generate the HTML for the alerts that are set.
	 *
	 * @return string the HTML
	 */
	private function generate_html_for_alerts() {
		$html = '';
		if( isset( $this->_error_messages ) ) {
			foreach( $this->_error_messages as $message ) {
				$error = esc_html( __( $message, AI1ECFI_PLUGIN_NAME ) );
				$html .= "<div id='message' class='ai1ec-alert ai1ec-alert-danger'>
							<a class='ai1ec-close' data-dismiss='ai1ec-alert' href='#'>x</a>
							$error
						</div>";
			}
		}
		if( isset( $this->_info_messages ) ) {
			foreach( $this->_info_messages as $message ) {
				$class = ( $message['errors'] === TRUE ) ? '' : 'ai1ec-alert-success';
				$text = $this->create_refresh_message( $message );
				$html .= "<div id='message' class='ai1ec-alert $class'>
				<a class='ai1ec-close' data-dismiss='ai1ec-alert' href='#'>x</a>
				$text
				</div>";
			}
			}
		return $html;
	}

	/**
	 * Renders the user pictur and name rendering the appropriate icons
	 *
	 * @param Ai1ec_Facebook_Current_User $user the User currently logged into Facebook
	 *
	 * @return string the generated HTML
	 */
	private function render_user_pic_and_icons( Ai1ec_Facebook_Current_User $user ) {
		$name                = $user->get_name();
		$id                  = $user->get_id();
		$alt_img             = esc_attr__( 'Profile image', AI1ECFI_PLUGIN_NAME );
		$logged_in_text      = esc_html__( 'Hi, you are signed in as:', AI1ECFI_PLUGIN_NAME );
		$type                = Ai1ec_Facebook_Graph_Object_Collection::FB_USER;
		$cat_tag_html        = $this->render_category_tag_div( $user->get_category(), $user->get_tag(), $user->get_enable_comments(), $user->get_display_map() );
		$refresh_label       = esc_attr__( ' Refresh', AI1ECFI_PLUGIN_NAME );
		$remove_label        = esc_attr__( ' Unsubscribe', AI1ECFI_PLUGIN_NAME );
		$refresh_tip         = esc_attr__( 'Refresh my events', AI1ECFI_PLUGIN_NAME );
		$remove_tip          = esc_attr__( 'Unsubscribe from my events', AI1ECFI_PLUGIN_NAME );

		if ( ! $user->get_subscribed() ) {
			$cat_tag_html = '';
		}

		$html = <<<HTML
<div id="profile-name">$logged_in_text</div>
<div class="ai1ec-facebook-subscriber ai1ec-facebook-items ai1ec-well ai1ec-well-sm ai1ec-pull-left" data-type="$type">
	<img id="profile-img" class="ai1ec-facebook-pic ai1ec-pull-left" alt="$alt_img" src="https://graph.facebook.com/$id/picture" />
HTML;

			if( $user->get_subscribed() ) {
				$html .= <<<HTML
	<div class="ai1ec-pull-right ai1ec-btn-group ai1ec-facebook-buttons">
		<a class="ai1ec-facebook-refresh ai1ec-btn ai1ec-btn-default ai1ec-btn-xs ai1ec-text-success" data-id="$id" title="$refresh_tip"><i class="ai1ec-fa ai1ec-fa-refresh ai1ec-fa-fw"></i>$refresh_label</a>
		<a class="ai1ec-facebook-remove ai1ec-btn ai1ec-btn-default ai1ec-btn-xs ai1ec-text-danger logged" data-id="$id" title="$remove_tip"><i class="ai1ec-fa ai1ec-fa-times ai1ec-fa-fw"></i>$remove_label</a>
	</div>
HTML;
			}

			$html .= <<<HTML
	<img src="images/wpspin_light.gif" class="ajax-loading ajax-loading-user ai1ec-pull-right" alt="" />
	<div class="ai1ec-facebook-name"><big>$name</big></div>
	$cat_tag_html
</div>
HTML;

		return $html;
	}

	/**
	 * Returns the upper part of the tab, with any errors
	 *
	 * @param Ai1ec_Facebook_Current_User $user The user currently logged in to Facebook
	 *
	 * @return string the HTML
	 */
	private function render_user_html( Ai1ec_Facebook_Current_User $user ) {
		// Create the variables and then create the HTML.

		$submit_logout_value           = esc_attr__( 'Sign out of Facebook', AI1ECFI_PLUGIN_NAME );
		$submit_subscribe_your_events  = esc_attr__( 'Subscribe to your events', AI1ECFI_PLUGIN_NAME );
		$alerts                        = $this->generate_html_for_alerts();
		$submit_your_events            = $user->get_subscribed() ? '' : '<button type="submit" id="ai1ec_facebook_subscribe_yours" class="ai1ec-btn ai1ec-btn-primary" name="ai1ec_facebook_subscribe_yours"><i class="ai1ec-fa ai1ec-fa-plus ai1ec-fa-fw"></i> ' . $submit_subscribe_your_events . '</button>';
		$user                          = $this->render_user_pic_and_icons( $user );
		$question_mark                 = $this->render_question_mark_for_facebook();
		$html = <<<HTML
<div class="ai1ec-clearfix">
	<div id="alerts">$alerts</div>
	<div id="ai1ec-facebook" class="ai1ec-clearfix">
	$user
	</div>
	<div class="ai1ec_submit_wrapper ai1ec-btn-toolbar">
		<div class="ai1ec-btn-group">
			$submit_your_events
			<button type="submit" id="ai1ec_logout_facebook"
				class="ai1ec-btn ai1ec-btn-default ai1ec-text-danger"
				name="ai1ec_logout_facebook">
				<i class="ai1ec-fa ai1ec-fa-sign-out ai1ec-fa-fw"></i>
				$submit_logout_value
			</button>
		</div>
		<div class="ai1ec-btn-group">
			$question_mark
		</div>
	</div>
</div>
HTML;
		return $html;
	}

	/**
	 * Generate the HTML for the select category
	 *
	 * @return string the HTML
	 */
	public function create_select_category( $id ) {
		$select = '<select class="ai1ec-categories-selector ai1ec-form-control" name="' . $id . '" id="' . $id . '">';
		// Set an empty option
		$select .= "<option value=''>" . esc_html__( "Choose a category", AI1ECFI_PLUGIN_NAME );
		foreach( get_terms( 'events_categories', array( 'hide_empty' => false ) ) as $term ) {
			$select .= "<option value='{$term->term_id}'>{$term->name}</option>";
		}
		$select .= '</select>';
		return $select;
	}

	/**
	 * Renders the HTML for the Multiselect of the specified types
	 *
	 * @param array $types The type of Facebook Graph Object to render
	 *
	 * @param $current_id The id of the currently logged on user which must be excluded from subscribers and multiselects
	 *
	 * @return the HTML
	 */
	private function render_multiselects( array $types, $current_id ) {
		$multiselects = $this->render_all_elements( $types, TRUE, $current_id );
		$factory = $this->_registry->get(
			'factory.html'
		);
		$category_select = $factory->create_select2_multiselect(
			array(
				'id'          => 'ai1ec_facebook_feed_category',
				'name'        => 'ai1ec_facebook_feed_category[]',
				'use_id'      => true,
				'type'        => 'category',
				'placeholder' => __( 'Categories (optional)', AI1ECFI_PLUGIN_NAME ),
				'class'       => 'categories'
			),
			get_terms( 'events_categories', array( 'hide_empty' => false ) )
		);
		$tags = $factory->create_select2_input(
			array(
				'id' => 'ai1ec_facebook_feed_tags'
			)
		);

		$label_category = esc_html__( 'Event category', AI1ECFI_PLUGIN_NAME );
		$label_tag = esc_html__( 'Tag with', AI1ECFI_PLUGIN_NAME );
		$label_refresh = esc_html__( 'Refresh list', AI1ECFI_PLUGIN_NAME );
		$html = '<h1>' . esc_html__( 'Subscribe to more events', AI1ECFI_PLUGIN_NAME ) . '</h1>';
		$html .= '<p>';
		$html .= esc_html__( "You can subscribe to the shared calendars of friends, pages, and groups you are connected with. Select those calendars you're interested in below.", AI1ECFI_PLUGIN_NAME );
		$html .= '</p>';
		$html .= '<div class="ai1ec-row">';
		foreach ( $types as $type ) {
			// Get the correct text for the type.
			$text = Ai1ec_Facebook_Graph_Object_Collection::get_type_printable_text( $type );
			$html .= <<<HTML
<div class="ai1ec-col-sm-6 ai1ec-facebook-multiselect-container" data-type="$type">
	<div class="ai1ec-facebook-multiselect-title-wrapper">
		<h2 class="ai1ec-facebook-header ai1ec-pull-left">$text</h2>
		<a class="ai1ec-facebook-refresh-multiselect ai1ec-btn ai1ec-btn-default ai1ec-btn-xs ai1ec-text-success">
			<i class="ai1ec-fa ai1ec-fa-refresh ai1ec-fa-fw"></i>
			$label_refresh
		</a>
		<img src="images/wpspin_light.gif" class="ajax-loading ai1ec-pull-right" alt="" />
	</div>
	<div class="clear"></div>
	{$multiselects[$type]}
</div>
HTML;
		}
		$html .= "</div>";
		$submit_subscribe_value = __( 'Subscribe to selected', AI1ECFI_PLUGIN_NAME );
		$comments_label = __( 'Allow comments on imported events', AI1ECFI_PLUGIN_NAME );
		$map_display_label = __( 'Show map on imported events', AI1ECFI_PLUGIN_NAME );
		$category_html = $category_select->get_content();
		$tags_html = $tags->get_content();
		$nonce = wp_create_nonce( 'ai1ec_facebook_feeds' );
		$html .= <<<HTML
<div class="ai1ec-row ai1ec_submit_wrapper">
	<div class="ai1ec-col-sm-6">
		$category_html
	</div>
	<div class="ai1ec-col-sm-6">
		$tags_html
	</div>
</div>
<div class="ai1ec_facebook_comments_enabled_wrapper">
	<label for="ai1ec_facebook_comments_enabled">
		<input type="checkbox" name="ai1ec_facebook_comments_enabled"
    	id="ai1ec_facebook_comments_enabled" value="1">
		$comments_label
	</label>
</div>
<div class="ai1ec_facebook_map_display_enabled_wrapper">
	<label for="ai1ec_facebook_map_display_enabled">
		<input type="checkbox" name="ai1ec_facebook_map_display_enabled"
			id="ai1ec_facebook_map_display_enabled" value="1">
		$map_display_label
	</label>
</div>
<input type="hidden" value="$nonce" id="ai1ec_facebook_feeds" name="ai1ec_facebook_feeds" />
<div class="ai1ec-clearfix ai1ec_submit_wrapper">
	<button type="submit" id="ai1ec_subscribe_users"
		class="ai1ec-btn ai1ec-btn-primary"
		name="ai1ec_subscribe_users">
		<i class="ai1ec-fa ai1ec-fa-plus ai1ec-fa-fw"></i> $submit_subscribe_value
	</button>
</div>
HTML;
		return $html;
	}

	/**
	 * Renders the Facebook Graph Object that user has subscribed to.
	 *
	 * @param array $types the Type of Facebook Graph Objects to render
	 *
	 * @param $current_id The id of the currently logged on user which must be excluded from subscribers and multiselects
	 *
	 * @return string the HTML
	 */
	private function render_subscribers( array $types, $current_id ) {
		$subscribers = $this->render_all_elements( $types, FALSE, $current_id );
		$html = '';
		foreach ( $types as $type ) {
			$text = Ai1ec_Facebook_Graph_Object_Collection::get_type_printable_text( $type );
			$html .= <<<HTML
<div class="ai1ec-facebook-items" data-type="$type">
	<h2 class="ai1ec-facebook-header">$text</h2>
	{$subscribers[$type]}
</div>
HTML;
		}
		$keep_events = __( "Keep Events", AI1ECFI_PLUGIN_NAME );
		$remove_events = __( "Remove Events", AI1ECFI_PLUGIN_NAME );
		$body = __( "Would you like to remove these events from your calendar, or preserve them?", AI1ECFI_PLUGIN_NAME );
		$removing = __( "Removing the following subscription: ", AI1ECFI_PLUGIN_NAME );
		$header_text = $removing . '<span id="ai1ec-facebook-user-modal"></span>';
		// Attach the modal for when you unsubscribe.
		$twitter_bootstrap_modal = $this->_registry->get( 'html.element.legacy.bootstrap.modal', $body );
		$twitter_bootstrap_modal->set_id( 'ai1ec-facebook-modal' );
		$twitter_bootstrap_modal->set_delete_button_text( $remove_events );
		$twitter_bootstrap_modal->set_keep_button_text( $keep_events );
		$twitter_bootstrap_modal->set_header_text( $header_text );
		$html .= $twitter_bootstrap_modal->render_as_html();
		return $html;
	}

	/**
	 * echoes the HTML for the Facebook Tab
	 *
	 * @param Ai1ec_Facebook_Current_User $user the currently logged user
	 *
	 * @param array $types The types to render
	 */
	public function render_tab( Ai1ec_Facebook_Current_User $user, array $types ) {
		// Render the part with the user and the submit buttons
		$html = $this->render_user_html( $user );
		// Render the multiselects
		$html .= $this->render_multiselects( $types, $user->get_id() );
		// Render the subscribers.
		$html .= $this->render_subscribers( $types, $user->get_id() );
		echo $html;
	}
}
