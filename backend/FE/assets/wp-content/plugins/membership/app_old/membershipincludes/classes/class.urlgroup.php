<?php
if(!class_exists('M_Urlgroup')) {

	class M_Urlgroup {

		var $build = 1;

		var $db;
		var $tables = array('membership_levels', 'membership_rules', 'subscriptions', 'subscriptions_levels', 'membership_relationships', 'membermeta', 'communications', 'urlgroups');

		var $membership_levels;
		var $membership_rules;
		var $membership_relationships;
		var $subscriptions;
		var $subscriptions_levels;
		var $membermeta;
		var $communications;
		var $urlgroups;

		// if the data needs reloaded, or hasn't been loaded yet
		var $dirty = true;

		var $group;

		function __construct( $id = false) {

			global $wpdb;

			$this->db =& $wpdb;

			foreach($this->tables as $table) {
				$this->$table = membership_db_prefix($this->db, $table);
			}

			$this->id = $id;

		}

		function M_Urlgroup( $id = false ) {
			$this->__construct( $id );
		}

		function get_group() {
			$sql = $this->db->prepare( "SELECT * FROM {$this->urlgroups} WHERE id = %d ", $this->id );

			return $this->db->get_row( $sql );
		}

		function editform() {

			$this->group = $this->get_group();

			echo '<table class="form-table">';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __('Group name','membership') . '</th>';
			echo '<td valign="top"><input name="groupname" type="text" size="50" title="' . __('Group name','membership') . '" style="width: 50%;" value="' . esc_attr(stripslashes($this->group->groupname)) . '" /></td>';
			echo '</tr>';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __('Page URLs','membership') . '</th>';
			echo '<td valign="top"><textarea name="groupurls" rows="15" cols="40">' . esc_html(stripslashes($this->group->groupurls)) . '</textarea>';
			// Display some instructions for the message.
			echo "<br/><em style='font-size:smaller;'>" . __("You should place each page URL or expression on a new line.",'membership') . "</em>";
			echo '</td>';
			echo '</tr>';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __('Strip query strings from URL','membership') . '</th>';
			echo '<td valign="top" align="left">';
			echo '<select name="stripquerystring">';
				echo '<option value="0"';
				if($this->group->stripquerystring == 0) echo ' selected="selected"';
				echo '>' . __('No', 'membership') . '</option>';
				echo '<option value="1"';
				if($this->group->stripquerystring == 1) echo ' selected="selected"';
				echo '>' . __('Yes', 'membership') . '</option>';
			echo '</select>';
			echo "<br/><em style='font-size:smaller;'>" . __("Remove any query string values prior to checking URL.",'membership') . "</em>";
			echo '</td></tr>';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __('Regular Expression','membership') . '</th>';
			echo '<td valign="top" align="left">';
			echo '<select name="isregexp">';
				echo '<option value="0"';
				if($this->group->isregexp == 0) echo ' selected="selected"';
				echo '>' . __('No', 'membership') . '</option>';
				echo '<option value="1"';
				if($this->group->isregexp == 1) echo ' selected="selected"';
				echo '>' . __('Yes', 'membership') . '</option>';
			echo '</select>';
			echo "<br/><em style='font-size:smaller;'>" . __("If any of the page URLs are regular expressions then set this to yes.",'membership') . "</em>";
			echo '</td></tr>';

			echo '</table>';

		}

		function addform() {

			echo '<table class="form-table">';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __('Group name','membership') . '</th>';
			echo '<td valign="top"><input name="groupname" type="text" size="50" title="' . __('Group name','membership') . '" style="width: 50%;" value="" /></td>';
			echo '</tr>';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __('Page URLs','membership') . '</th>';
			echo '<td valign="top"><textarea name="groupurls" rows="15" cols="40"></textarea>';
			// Display some instructions for the message.
			echo "<br/><em style='font-size:smaller;'>" . __("You should place each page URL or expression on a new line.",'membership') . "</em>";
			echo '</td>';
			echo '</tr>';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __('Strip query strings from URL','membership') . '</th>';
			echo '<td valign="top" align="left">';
			echo '<select name="stripquerystring">';
				echo '<option value="0"';
				echo '>' . __('No', 'membership') . '</option>';
				echo '<option value="1"';
				echo '>' . __('Yes', 'membership') . '</option>';
			echo '</select>';
			echo "<br/><em style='font-size:smaller;'>" . __("Remove any query string values prior to checking URL.",'membership') . "</em>";
			echo '</td></tr>';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __('Regular Expression','membership') . '</th>';
			echo '<td valign="top" align="left">';
			echo '<select name="isregexp">';
				echo '<option value="0"';
				echo '>' . __('No', 'membership') . '</option>';
				echo '<option value="1"';
				echo '>' . __('Yes', 'membership') . '</option>';
			echo '</select>';
			echo "<br/><em style='font-size:smaller;'>" . __("If any of the page URLs are regular expressions then set this to yes.",'membership') . "</em>";
			echo '</td></tr>';

			echo '</table>';

		}

		function add() {

			$insert = array(
								"groupname"	=> 	$_POST['groupname'],
								"groupurls"	=>	$_POST['groupurls'],
								"isregexp"	=>	$_POST['isregexp'],
								"stripquerystring"	=> $_POST['stripquerystring']
							);

			return $this->db->insert( $this->urlgroups, $insert );

		}

		function update() {

			$updates = array(
								"groupname"	=> 	$_POST['groupname'],
								"groupurls"	=>	$_POST['groupurls'],
								"isregexp"	=>	$_POST['isregexp'],
								"stripquerystring"	=> $_POST['stripquerystring']
							);

			return $this->db->update( $this->urlgroups, $updates, array( "id" => $this->id) );

		}

		function delete() {

			$sql = $this->db->prepare( "DELETE FROM {$this->urlgroups} WHERE id = %d", $this->id );

			return $this->db->query( $sql );

		}

		// processing
		function url_matches( $host, $exclude = array() ) {

			$this->group = $this->get_group();

			$groups = array_map('strtolower', array_map('trim', explode("\n", $this->group->groupurls)));

			if($this->group->stripquerystring == 1 && strpos($host, '?') !== false) {
				$host = substr( $host, 0, strpos($host, '?'));
			}

			if($this->group->isregexp == 0) {
				// straight match
				$newgroups = array_map('untrailingslashit', $groups);
				$groups = array_merge($groups,$newgroups);
				if(in_array( strtolower($host), $groups ) ) {
					return true;
				} else {
					return false;
				}
			} else {
				//reg expression match
				$matchstring = "";
				foreach($groups as $key => $value) {
					if($matchstring != "") $matchstring .= "|";

					if( stripos($value, '\/') ) {
						$matchstring .= stripcslashes($value);
					} else {
						$matchstring .= $value;
					}

				}
				// switched to using a character that won't be in a url as the start and end markers
				$matchstring = "#" . $matchstring . "#i";

				if(preg_match($matchstring, $host, $matches) ) {
					return true;
				} else {
					return false;
				}
			}

		}

	}
}

function M_create_internal_URL_group( $rule, $post, $level_id ) {

	global $wpdb;

	switch( $rule ) {
		case 'posts':		$permalinks = array();
							foreach( $_POST[$rule] as $rule ) {
								$thelink = get_permalink( $rule );
								$thelink = str_replace('http://', 'https?://', $thelink );
								$permalinks[] = untrailingslashit($thelink) . '(/.*)';
							}

							$sql = $wpdb->prepare( "SELECT id FROM " . membership_db_prefix($wpdb, 'urlgroups') . " WHERE groupname = %s ORDER BY id DESC LIMIT 0,1", '_posts-' . $level_id );
							$id = $wpdb->get_var( $sql );

							$data = array( 	"groupname"	=> 	'_posts-' . $level_id,
											"groupurls"	=>	implode("\n", $permalinks),
											"isregexp"	=>	1,
											"stripquerystring"	=> 1
											);

							if(!empty($id)) {
								// exists so we're going to do an update
								$wpdb->update( membership_db_prefix($wpdb, 'urlgroups'), $data, array( "id" => $id) );
							} else {
								// doesn't exist so we're going to do an add.
								$wpdb->insert( membership_db_prefix($wpdb, 'urlgroups'), $data );
							}

							break;

		case 'pages':		$permalinks = array();
							foreach( $_POST[$rule] as $rule ) {
								$thelink = get_permalink( $rule );
								$thelink = str_replace('http://', 'https?://', $thelink );
								$permalinks[] = untrailingslashit($thelink) . '(/.*)';
							}

							$sql = $wpdb->prepare( "SELECT id FROM " . membership_db_prefix($wpdb, 'urlgroups') . " WHERE groupname = %s ORDER BY id DESC LIMIT 0,1", '_pages-' . $level_id );
							$id = $wpdb->get_var( $sql );

							$data = array( 	"groupname"	=> 	'_pages-' . $level_id,
											"groupurls"	=>	implode("\n", $permalinks),
											"isregexp"	=>	1,
											"stripquerystring"	=> 1
											);

							if(!empty($id)) {
								// exists so we're going to do an update
								$wpdb->update( membership_db_prefix($wpdb, 'urlgroups'), $data, array( "id" => $id) );
							} else {
								// doesn't exist so we're going to do an add.
								$wpdb->insert( membership_db_prefix($wpdb, 'urlgroups'), $data );
							}

							break;

			case 'bppages':		$permalinks = array();
								foreach( $_POST[$rule] as $rule ) {
									$thelink = get_permalink( $rule );
									$thelink = str_replace('http://', 'https?://', $thelink );
									$permalinks[] = untrailingslashit($thelink) . '(/.*)';
								}

								$sql = $wpdb->prepare( "SELECT id FROM " . membership_db_prefix($wpdb, 'urlgroups') . " WHERE groupname = %s ORDER BY id DESC LIMIT 0,1", '_bppages-' . $level_id);
								$id = $wpdb->get_var( $sql );

								$data = array( 	"groupname"	=> 	'_bppages-' . $level_id,
												"groupurls"	=>	implode("\n", $permalinks),
												"isregexp"	=>	1,
												"stripquerystring"	=> 1
												);

								if(!empty($id)) {
									// exists so we're going to do an update
									$wpdb->update( membership_db_prefix($wpdb, 'urlgroups'), $data, array( "id" => $id) );
								} else {
									// doesn't exist so we're going to do an add.
									$wpdb->insert( membership_db_prefix($wpdb, 'urlgroups'), $data );
								}

								break;

			case 'bpgroups':	$permalinks = array();
								if(function_exists('bp_get_group_permalink')) {
									foreach( $_POST[$rule] as $rule ) {
										$group = new BP_Groups_Group( $rule );
										$thelink = bp_get_group_permalink( $group );
										$thelink = str_replace('http://', 'https?://', $thelink );
										$permalinks[] = untrailingslashit($thelink) . '(/.*)';
									}
								}


								$sql = $wpdb->prepare( "SELECT id FROM " . membership_db_prefix($wpdb, 'urlgroups') . " WHERE groupname = %s ORDER BY id DESC LIMIT 0,1", '_bpgroups-' . $level_id);
								$id = $wpdb->get_var( $sql );

								$data = array( 	"groupname"	=> 	'_bpgroups-' . $level_id,
												"groupurls"	=>	implode("\n", $permalinks),
												"isregexp"	=>	1,
												"stripquerystring"	=> 1
												);

								if(!empty($id)) {
									// exists so we're going to do an update
									$wpdb->update( membership_db_prefix($wpdb, 'urlgroups'), $data, array( "id" => $id) );
								} else {
									// doesn't exist so we're going to do an add.
									$wpdb->insert( membership_db_prefix($wpdb, 'urlgroups'), $data );
								}

								break;

	}

}

add_action( 'membership_update_positive_rule', 'M_create_internal_URL_group', 10, 3 );
add_action( 'membership_update_negative_rule', 'M_create_internal_URL_group', 10, 3 );
add_action( 'membership_add_positive_rule', 'M_create_internal_URL_group', 10, 3 );
add_action( 'membership_add_negative_rule', 'M_create_internal_URL_group', 10, 3 );

?>