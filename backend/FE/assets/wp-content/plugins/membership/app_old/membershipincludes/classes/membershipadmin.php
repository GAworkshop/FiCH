<?php
if(!class_exists('membershipadmin')) {

	class membershipadmin {

		var $build = 14;
		var $db;

		//
		var $showposts = 25;
		var $showpages = 100;

		var $tables = array('membership_levels', 'membership_rules', 'subscriptions', 'subscriptions_levels', 'membership_relationships', 'membermeta', 'communications', 'urlgroups', 'ping_history', 'pings', 'coupons');

		var $membership_levels;
		var $membership_rules;
		var $membership_relationships;
		var $subscriptions;
		var $subscriptions_levels;
		var $membermeta;
		var $communications;
		var $urlgroups;
		var $ping_history;
		var $pings;
		var $coupons;

		// Class variable to hold a link to the tooltips class
		var $_tips;

		// The Wizard
		var $potter;

		// The tutorial
		var $tutorial;

		// Coupons
		var $_coupons;

		// For the coupons datepicker
		var $language;


		function __construct() {

			global $wpdb;

			$this->db =& $wpdb;

			foreach($this->tables as $table) {
				$this->$table = membership_db_prefix($this->db, $table);
			}

			// Instantiate the tooltips class and set the icon
			$this->_tips = new WpmuDev_HelpTooltips();
			$this->_tips->set_icon_url(membership_url('membershipincludes/images/information.png'));

			// Initiate the wizard class
			$this->potter = new M_Wizard();

			// Add administration actions
			add_action('init', array(&$this, 'initialise_plugin'), 1);

			// Add in admin area membership levels
			add_action('init', array(&$this, 'initialise_membership_protection'), 999);

			if( (function_exists('is_plugin_active_for_network') && is_plugin_active_for_network('membership/membership.php')) && (defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true)) {
				add_action('network_admin_menu', array(&$this, 'add_admin_menu'));
			} else {
				add_action('admin_menu', array(&$this, 'add_admin_menu'));
			}

			add_action( 'plugins_loaded', array(&$this, 'load_textdomain'));

			// Header actions for users page
			add_action( 'load-users.php', array(&$this, 'add_header_users_page'));

			// Custom header actions
			add_action('load-toplevel_page_membership', array(&$this, 'add_admin_header_membership'));
			add_action('load-membership_page_membershipmembers', array(&$this, 'add_admin_header_members'));
			add_action('load-membership_page_membershiplevels', array(&$this, 'add_admin_header_membershiplevels'));
			add_action('load-membership_page_membershipsubs', array(&$this, 'add_admin_header_membershipsubs'));
			add_action('load-membership_page_membershipcoupons', array(&$this, 'add_admin_header_membershipcoupons'));
			add_action('load-membership_page_membershipgateways', array(&$this, 'add_admin_header_membershipgateways'));
			add_action('load-membership_page_membershipoptions', array(&$this, 'add_admin_header_membershipoptions'));
			add_action('load-membership_page_membershipcommunication', array(&$this, 'add_admin_header_membershipcommunication'));
			add_action('load-membership_page_membershipurlgroups', array(&$this, 'add_admin_header_membershipurlgroups'));
			add_action('load-membership_page_membershippings', array(&$this, 'add_admin_header_membershippings'));

			add_action('load-users_page_membershipuser', array(&$this, 'add_admin_header_membershipuser'));

			add_filter('membership_level_sections', array(&$this, 'default_membership_sections'));

			// Media management additional fields
			add_filter('attachment_fields_to_edit', array(&$this, 'add_media_protection_settings'), 99, 2);
			add_filter('attachment_fields_to_save', array(&$this, 'save_media_protection_settings'), 99, 2);

			// rewrites
			add_action('generate_rewrite_rules', array(&$this, 'add_rewrites'));
			add_filter('query_vars', array(&$this, 'add_queryvars') );

			// profile field for feeds
			add_action('show_user_profile', array(&$this, 'add_profile_feed_key') );

			// Pings
			add_action('membership_subscription_form_after_levels', array(&$this, 'show_subscription_ping_information'));
			add_action('membership_subscription_add', array(&$this, 'update_subscription_ping_information'));
			add_action('membership_subscription_update', array(&$this, 'update_subscription_ping_information'));

			add_action('membership_level_form_after_rules', array(&$this, 'show_level_ping_information'));
			add_action('membership_level_add', array(&$this, 'update_level_ping_information'));
			add_action('membership_level_update', array(&$this, 'update_level_ping_information'));

			// Ajax calls have to go here because admin-ajax.php is an admin call even though we're calling it from the front end.
			add_action( 'wp_ajax_nopriv_buynow', array(&$this, 'popover_signup_form') );

			//login and register are no-priv only because, well they aren't logged in or registered
			add_action( 'wp_ajax_nopriv_register_user', array(&$this, 'popover_register_process') );
			add_action( 'wp_ajax_nopriv_login_user', array(&$this, 'popover_login_process') );

			// if logged in:
			add_action( 'wp_ajax_buynow', array(&$this, 'popover_sendpayment_form') );
			add_action( 'wp_ajax_extra_form', array(&$this, 'popover_extraform_process') );
			add_action( 'wp_ajax_register_user', array(&$this, 'popover_register_process') );
			add_action( 'wp_ajax_login_user', array(&$this, 'popover_login_process') );

			// Helper actions
			add_action( 'membership_activate_addon', array(&$this, 'activate_addon'), 10, 1 );
			add_action( 'membership_deactivate_addon', array(&$this, 'deactivate_addon'), 10, 1 );

			// Level shortcodes filters
			add_filter( 'membership_level_shortcodes', array(&$this, 'build_level_shortcode_list' ) );

			add_action( 'plugins_loaded', array(&$this, 'load_tutorial'), 11); //init tutorial after translation loaded


			// Remove relationships when user gets deleted
			add_action( 'delete_user', array(&$this, 'remove_relationships_on_delete' ) );


		}

		function membershipadmin() {
			$this->__construct();
		}

		function load_textdomain() {

			$locale = apply_filters( 'membership_locale', get_locale() );
			$mofile = membership_dir( "membershipincludes/languages/membership-$locale.mo" );

			if ( file_exists( $mofile ) ) {
				load_textdomain( 'membership', $mofile );
			}

			//setup language code for jquery datepicker translation
		    $temp_locales = explode('_', get_locale());
		  	$this->language = ($temp_locales[0]) ? $temp_locales[0] : 'en';

		}

		function load_tutorial() {
			// Add in pointer tutorial
			$this->tutorial = new M_Tutorial();
			$this->tutorial->serve();
		}

		function initialise_plugin() {

			global $user, $M_options;

			$installed = get_option('M_Installed', false);

			if(empty($user) || !method_exists($user, 'has_cap')) {
				$user = wp_get_current_user();
			}

			if($installed === false || $installed != $this->build) {
				include_once(membership_dir('membershipincludes/classes/upgrade.php') );

				M_Upgrade($installed);
				update_option('M_Installed', $this->build);

				// Add in our new capability
				if(!$user->has_cap('membershipadmin') && defined('MEMBERSHIP_SETACTIVATORAS_ADMIN') && MEMBERSHIP_SETACTIVATORAS_ADMIN == 'yes') {
					$user->add_cap('membershipadmin');
				}

				$this->create_defaults();
			}

			// Add in our new capability
			if($user->user_login == MEMBERSHIP_MASTER_ADMIN && !$user->has_cap('membershipadmin')) {
				$user->add_cap('membershipadmin');
			}

			// Update the membership capabillities for the new layout
			if($user->has_cap('membershipadmin') && !$user->has_cap('membershipadminupdatepermissions') ) {
				// We are here is the user has the old permissions but doesn't have the new default dashboard permissions
				// Which likely means that they have not been upgraded - so let's do that :)

				$updated = get_user_meta( $user->ID, 'membership_permissions_updated', true );

				if(empty($updated) || $updated != 'yes') {
					$user->add_cap('membershipadmindashboard');
					$user->add_cap('membershipadminmembers');
					$user->add_cap('membershipadminlevels');
					$user->add_cap('membershipadminsubscriptions');
					$user->add_cap('membershipadmincoupons');
					$user->add_cap('membershipadminpurchases');
					$user->add_cap('membershipadmincommunications');
					$user->add_cap('membershipadmingroups');
					$user->add_cap('membershipadminpings');
					$user->add_cap('membershipadmingateways');
					$user->add_cap('membershipadminoptions');
					// New permissions setting
					$user->add_cap('membershipadminupdatepermissions');

					update_user_meta( $user->ID, 'membership_permissions_updated', 'yes');
				}
			}


			if($user->has_cap('membershipadminupdatepermissions')) {
				// user permissions on the user form
				add_filter( 'manage_users_columns', array(&$this, 'add_user_permissions_column') );
				add_filter( 'wpmu_users_columns', array(&$this, 'add_user_permissions_column') );
				add_filter( 'manage_users_custom_column', array(&$this, 'show_user_permissions_column'), 10, 3 );

				add_action( 'wp_ajax_editusermembershippermissions', array(&$this, 'edit_user_permissions') );
			}

			if($user->has_cap('membershipadmin')) {
				// If the user is a membershipadmin user then we can add in notices
				add_action('all_admin_notices', array(&$this, 'show_membership_status_notice'));
			}

			if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true) {
				if(function_exists('get_blog_option')) {
					$M_options = get_blog_option(MEMBERSHIP_GLOBAL_MAINSITE, 'membership_options', array());
				} else {
					$M_options = get_option('membership_options', array());
				}
			} else {
				$M_options = get_option('membership_options', array());
			}

			// Short codes
			if(!empty($M_options['membershipshortcodes'])) {
				foreach($M_options['membershipshortcodes'] as $key => $value) {
					if(!empty($value)) {
						add_shortcode(stripslashes(trim($value)), array(&$this, 'do_fake_shortcode') );
					}
				}
			}

			// Admin only Shortcodes setup
			if(!empty($M_options['membershipadminshortcodes'])) {
				foreach($M_options['membershipadminshortcodes'] as $key => $value) {
					if(!empty($value)) {
						add_shortcode(stripslashes(trim($value)), array(&$this, 'do_fake_shortcode') );
					}
				}
			}

			do_action('membership_register_shortcodes');

			add_action( 'wp_ajax_m_set_coupon', array(&$this, 'set_membership_coupon_cookie'));
			add_action( 'wp_ajax_nopriv_m_set_coupon', array(&$this, 'set_membership_coupon_cookie'));

		}

		function add_user_permissions_column( $columns ) {
			$columns['membershippermissions'] = __('Permissions', 'membership');

			return $columns;
		}

		function show_user_permissions_column( $content, $column_name, $user_id ) {

			if($column_name == 'membershippermissions') {
				// We are on our column
				$theuser = get_user_by( 'id', $user_id );

				$perms = array();
				if( $theuser->has_cap('membershipadmindashboard') ) {
					$perms[] = __('Dashboard','membership');
				}
				if( $theuser->has_cap('membershipadminmembers') ) {
					$perms[] = __('Members','membership');
				}
				if( $theuser->has_cap('membershipadminlevels') ) {
					$perms[] = __('Levels','membership');
				}
				if( $theuser->has_cap('membershipadminsubscriptions') ) {
					$perms[] = __('Subscriptions','membership');
				}
				if( $theuser->has_cap('membershipadmincoupons') ) {
					$perms[] = __('Coupons','membership');
				}

				if( $theuser->has_cap('membershipadminpurchases') ) {
					$perms[] = __('Purchases','membership');
				}
				if( $theuser->has_cap('membershipadmincommunications') ) {
					$perms[] = __('Communications','membership');
				}
				if( $theuser->has_cap('membershipadmingroups') ) {
					$perms[] = __('URL Groups','membership');
				}
				if( $theuser->has_cap('membershipadminpings') ) {
					$perms[] = __('Pings','membership');
				}
				if( $theuser->has_cap('membershipadmingateways') ) {
					$perms[] = __('Gateways','membership');
				}

				if( $theuser->has_cap('membershipadminoptions') ) {
					$perms[] = __('Options','membership');
				}

				if( $theuser->has_cap('membershipadminupdatepermissions') ) {
					$perms[] = __('Permissions','membership');
				}


				if(empty($perms)) {
					$perms[] = __('None', 'membership');
				}

				$content .= implode( ', ', $perms );

				$content .= '<div class="row-actions">';
				$content .= '<span class="edit">';
				$content .= '<a class="membershipeditlink" href="' . wp_nonce_url( admin_url("admin-ajax.php?action=editusermembershippermissions&amp;user_id=" . $user_id . ""), 'edit_user_membership_' . $user_id) . '">' . __('Edit','membership') . '</a>';
				$content .= '</span>';
				$content .= '</div>';

				/*
				if($user->has_cap('membershipadmin') && !$user->has_cap('membershipadmincoupons') ) {
					// We are here is the user has the old permissions but doesn't have the new default dashboard permissions
					// Which likely means that they have not been upgraded - so let's do that :)
					$user->add_cap('membershipadmindashboard');
					$user->add_cap('membershipadminmembers');
					$user->add_cap('membershipadminlevels');
					$user->add_cap('membershipadminsubscriptions');
					$user->add_cap('membershipadmincoupons');
					$user->add_cap('membershipadminpurchases');
					$user->add_cap('membershipadmincommunications');
					$user->add_cap('membershipadmingroups');
					$user->add_cap('membershipadminpings');
					$user->add_cap('membershipadmingateways');
					$user->add_cap('membershipadminoptions');
				}
				*/


			}

			return $content;

		}

		// Code from this function based on code from AJAX Media Upload function
		function edit_user_permissions() {

			_wp_admin_html_begin();
			?>
			<title><?php _e('Post Indexer Settings','postindexer'); ?></title>
			<?php

			wp_enqueue_style( 'colors' );
			//wp_enqueue_style( 'media' );
			//wp_enqueue_style( 'ie' );
			wp_enqueue_script( 'jquery' );

			do_action('admin_print_styles');
			do_action('admin_print_scripts');
			do_action('admin_head');

			?>
			</head>
			<body<?php if ( isset($GLOBALS['body_id']) ) echo ' id="' . $GLOBALS['body_id'] . '"'; ?> class="no-js">
			<script type="text/javascript">
				document.body.className = document.body.className.replace('no-js', 'js');
			</script>
			<?php
				$this->edit_users_permissions_content();

				do_action('admin_print_footer_scripts');
			?>
			<script type="text/javascript">if(typeof wpOnload=='function')wpOnload();</script>
			</body>
			</html>
			<?php
			exit;
		}

		function edit_users_permissions_content() {

			if( !isset($_GET['user_id'])) {
				wp_die( __( 'Cheatin&#8217; uh?' ) );
			} else {

				$user_id = $_GET['user_id'];
				check_admin_referer( 'edit_user_membership_' . $user_id );

				?>
					<form action="" class="" id="membership-form" method='get'>

						<input type='hidden' name='action' value='updatemembershippermissionsesettings' />
						<input type='hidden' name='user_id' value='<?php echo $user_id; ?>' />
						<input type='hidden' name='comefrom' value='<?php echo esc_attr( wp_get_referer() ); ?>' />
						<?php
							wp_nonce_field('membership_update_permissions_settings_' . $user_id);
						?>

						<h3 class="media-title"><?php echo __("Membership Permissions","membership"); ?></h3>
						<p class='description'><?php _e('Select the areas you want this user to be able to administrate.','membership'); ?></p>

						<table>
							<tbody>
								<tr>
									<th style='min-width: 150px; vertical-align: top;'><?php _e('Current Permissions', 'membership'); ?></th>
									<td>
									<?php
										$theuser = get_user_by( 'id', $user_id );

										$perms = array();
										if( $theuser->has_cap('membershipadmindashboard') ) {
											$perms[] = 'dashboard';
										}
										if( $theuser->has_cap('membershipadminmembers') ) {
											$perms[] = 'members';
										}
										if( $theuser->has_cap('membershipadminlevels') ) {
											$perms[] = 'levels';
										}
										if( $theuser->has_cap('membershipadminsubscriptions') ) {
											$perms[] = 'subscriptions';
										}
										if( $theuser->has_cap('membershipadmincoupons') ) {
											$perms[] = 'coupons';
										}
										if( $theuser->has_cap('membershipadminpurchases') ) {
											$perms[] = 'purchases';
										}
										if( $theuser->has_cap('membershipadmincommunications') ) {
											$perms[] = 'communications';
										}
										if( $theuser->has_cap('membershipadmingroups') ) {
											$perms[] = 'urlgroups';
										}
										if( $theuser->has_cap('membershipadminpings') ) {
											$perms[] = 'pings';
										}
										if( $theuser->has_cap('membershipadmingateways') ) {
											$perms[] = 'gateways';
										}
										if( $theuser->has_cap('membershipadminoptions') ) {
											$perms[] = 'options';
										}
										if( $theuser->has_cap('membershipadminupdatepermissions') ) {
											$perms[] = 'permissions';
										}


										$headings = array();
										$headings['dashboard'] = __('Dashboard','membership');
										$headings['members'] = __('Members','membership');
										$headings['levels'] = __('Levels','membership');
										$headings['subscriptions'] = __('Subscriptions','membership');
										$headings['coupons'] = __('Coupons','membership');
										$headings['purchases'] = __('Purchases','membership');
										$headings['communications'] = __('Communications','membership');
										$headings['urlgroups'] = __('URL Groups','membership');
										$headings['pings'] = __('Pings','membership');
										$headings['gateways'] = __('Gateways','membership');
										$headings['options'] = __('Options','membership');
										$headings['permissions'] = __('Permissions','membership');

										?>
										<ul style='margin:0; padding:0;'>
										<?php
										foreach($headings as $heading => $label) {
											?>
												<li><label><input style='margin-top: 0; margin-right: 5px;' type='checkbox' name='membership_permission[]' value='<?php echo $heading; ?>' <?php if(in_array($heading, $perms)) { echo "checked='checked'"; } ?> />&nbsp;<?php echo $label; ?></label></li>
											<?php
										}
										?>
										</ul>
										<?php

									?>
								</td>
							</tr>
						</tbody>
					</table>


					<p class="savebutton ml-submit">
						<input name="save" id="save" class="button-primary" value="<?php _e('Save all changes','postindexer'); ?>" type="submit">
					</p>
				</form>

				<?php
			}

		}

		function show_membership_status_notice() {

			global $user, $M_options;

			// Membership active check
			$membershipactive = M_get_membership_active();
			if($membershipactive == 'no') {
				echo '<div class="error fade"><p>' . sprintf(__("The Membership plugin is not enabled. To ensure your content is protected you should <a href='%s'>enable it</a>", 'membership'), wp_nonce_url("?page=membership&amp;action=activate", 'toggle-plugin')) . '</p></div>';
			}

			// Membership admin check
			if(empty($user) || !method_exists($user, 'has_cap')) {
				$user = wp_get_current_user();
			}

			if($user->has_cap('membershipadmin')) {
				// Show a notice to say that they are logged in as the membership admin user and protection isn't enabled on the front end
				echo '<div class="update-nag">' . __("You are logged in as a <strong>Membership Admin</strong> user, you will therefore see all protected content on this site.", 'membership') . '</div>';
			}


		}

		function add_admin_menu() {

			global $menu, $admin_page_hooks;

			if(current_user_can('membershipadmindashboard')) {
				// Add the menu page
				add_menu_page(__('Membership','membership'), __('Membership','membership'), 'membershipadmindashboard',  'membership', array(&$this,'handle_membership_panel'), membership_url('membershipincludes/images/members.png'));
				//echo $hook;
				// Fix WP translation hook issue
				if(isset($admin_page_hooks['membership'])) {
					$admin_page_hooks['membership'] = 'membership';
				}

				do_action('membership_add_menu_items_top');
				// Add the sub menu
				add_submenu_page('membership', __('Members','membership'), __('All Members','membership'), 'membershipadminmembers', "membershipmembers", array(&$this,'handle_members_panel'));
				do_action('membership_add_menu_items_after_members');

				add_submenu_page('membership', __('Membership Levels','membership'), __('Access Levels','membership'), 'membershipadminlevels', "membershiplevels", array(&$this,'handle_levels_panel'));
				do_action('membership_add_menu_items_after_levels');

				add_submenu_page('membership', __('Membership Subscriptions','membership'), __('Subscription Plans','membership'), 'membershipadminsubscriptions', "membershipsubs", array(&$this,'handle_subs_panel'));
				do_action('membership_add_menu_items_after_subscriptions');

				add_submenu_page('membership', __('Membership URL Groups','membership'), __('URL Groups','membership'), 'membershipadmingroups', "membershipurlgroups", array(&$this,'handle_urlgroups_panel'));
				do_action('membership_add_menu_items_after_urlgroups');

				add_submenu_page('membership', __('Membership Pings','membership'), __('Remote Pings','membership'), 'membershipadminpings', "membershippings", array(&$this,'handle_pings_panel'));
				do_action('membership_add_menu_items_after_pings');

				add_submenu_page('membership', __('Membership Gateways','membership'), __('Payment Gateways','membership'), 'membershipadmingateways', "membershipgateways", array(&$this,'handle_gateways_panel'));
				do_action('membership_add_menu_items_after_gateways');

				add_submenu_page('membership', __('Membership Options','membership'), __('Options','membership'), 'membershipadminoptions', "membershipoptions", array(&$this,'handle_options_panel'));
				do_action('membership_add_menu_items_after_options');

				do_action('membership_add_menu_items_bottom');
			}


		}

		// Admin area protection
		function initialise_membership_protection() {

			global $user, $member, $M_options, $M_Rules, $wp_query, $wp_rewrite, $M_active;
			// Set up some common defaults

			static $initialised = false;

			if($initialised) {
				// ensure that this is only called once, so return if we've been here already.
				return;
			}

			$M_options = get_option('membership_options', array());
			// Check if the membership plugin is active
			$M_active = get_option('membership_active', 'no');

			if(empty($user) || !method_exists($user, 'has_cap')) {
				$user = wp_get_current_user();
			}

			if(!method_exists($user, 'has_cap') || $user->has_cap('membershipadmin') || $M_active == 'no') {
				// Admins can see everything
				return;
			}

			// Users
			$member = new M_Membership($user->ID);

			if($user->ID > 0 && $member->has_levels()) {
				// Load the levels for this member - and associated rules
				$member->load_admin_levels( true );
			} else {
				// need to grab the stranger settings
				if(isset($M_options['strangerlevel']) && $M_options['strangerlevel'] != 0) {
					$member->assign_admin_level($M_options['strangerlevel'], true );
				}
			}

			do_action('membership-admin-add-shortcodes');

			// Set the initialisation status
			$initialised = true;

		}

		// Add admin headers

		function add_admin_header_core() {

			// Add in help pages
			$screen = get_current_screen();
			$help = new M_Help( $screen );
			$help->attach();

			// Add in default style sheet with common styling elements
			wp_enqueue_style('defaultcss', membership_url('membershipincludes/css/default.css'), array(), $this->build);

		}

		function add_header_users_page() {

			wp_enqueue_script('thickbox');

			wp_register_script('membership-users-js', membership_url('membershipincludes/js/users.js'), array('jquery', 'thickbox'));
			wp_enqueue_script('membership-users-js');

			wp_localize_script('membership-users-js', 'membership', array( 'useredittitle'		=>	__('Membership Permissions','membership') ));
			wp_enqueue_style('thickbox');

			$this->process_users_page();
		}

		function process_users_page() {

			if( isset( $_GET['action'] ) ) {
				switch( $_GET['action'] ) {
					case 'updatemembershippermissionsesettings':

															$user_id = $_GET['user_id'];
															//check_admin_referer( 'membership_update_permissions_settings_' . $user_id );
															$theuser = get_user_by( 'id', $user_id );


															if(!empty( $_GET['membership_permission'] )) {
																$new = (array) $_GET['membership_permission'];
															} else {
																$new = array();
															}

															if( in_array('dashboard', $new ) ) {
																$theuser->add_cap('membershipadmindashboard');
															} else {
																$theuser->remove_cap('membershipadmindashboard');
															}
															if( in_array('members', $new ) ) {
																$theuser->add_cap('membershipadminmembers');
															} else {
																$theuser->remove_cap('membershipadminmembers');
															}
															if( in_array('levels', $new ) ) {
																$theuser->add_cap('membershipadminlevels');
															} else {
																$theuser->remove_cap('membershipadminlevels');
															}
															if( in_array('subscriptions', $new ) ) {
																$theuser->add_cap('membershipadminsubscriptions');
															} else {
																$theuser->remove_cap('membershipadminsubscriptions');
															}
															if( in_array('coupons', $new ) ) {
																$theuser->add_cap('membershipadmincoupons');
															} else {
																$theuser->remove_cap('membershipadmincoupons');
															}
															if( in_array('purchases', $new ) ) {
																$theuser->add_cap('membershipadminpurchases');
															} else {
																$theuser->remove_cap('membershipadminpurchases');
															}
															if( in_array('communications', $new ) ) {
																$theuser->add_cap('membershipadmincommunications');
															} else {
																$theuser->remove_cap('membershipadmincommunications');
															}
															if( in_array('urlgroups', $new ) ) {
																$theuser->add_cap('membershipadmingroups');
															} else {
																$theuser->remove_cap('membershipadmingroups');
															}
															if( in_array('pings', $new ) ) {
																$theuser->add_cap('membershipadminpings');
															} else {
																$theuser->remove_cap('membershipadminpings');
															}
															if( in_array('gateways', $new ) ) {
																$theuser->add_cap('membershipadmingateways');
															} else {
																$theuser->remove_cap('membershipadmingateways');
															}
															if( in_array('options', $new ) ) {
																$theuser->add_cap('membershipadminoptions');
															} else {
																$theuser->remove_cap('membershipadminoptions');
															}

															if( in_array('permissions', $new ) ) {
																$theuser->add_cap('membershipadminupdatepermissions');
															} else {
																$theuser->remove_cap('membershipadminupdatepermissions');
															}

															wp_safe_redirect( $_GET['comefrom'] );
															break;
				}
			}

		}

		function add_admin_header_membership() {
			// The dashboard - top level menu

			global $wp_version;

			// Load the core first
			$this->add_admin_header_core();

			wp_enqueue_script('dashjs', membership_url('membershipincludes/js/dashboard.js'), array( 'jquery' ), $this->build);

			if(version_compare( preg_replace('/-.*$/', '', $wp_version), "3.3", '<')) {
				wp_enqueue_style('dashcss', membership_url('membershipincludes/css/dashboard.css'), array('widgets'), $this->build);
			} else {
				wp_enqueue_style('dashcss', membership_url('membershipincludes/css/dashboard.css'), array(), $this->build);
			}
			// Add localisation for the wizard
			wp_localize_script('dashjs', 'membershipwizard', array(	'ajaxurl'	=>	admin_url( 'admin-ajax.php' ),
																	'membershiploading' => __('Loading...', 'membership'),
																	'membershipnextstep' => __('Next Step &raquo;','membership'),
																	'membershipgonewrong' => __('Something has gone wrong with the Wizard, please try clicking the button again.', 'membership'),
																	'membershiplevel' => __('Level', 'membership'),
																	));

			$this->handle_membership_dashboard_updates();
		}

		function add_admin_header_membershiplevels() {

			global $wp_version;

			$this->add_admin_header_core();

			wp_enqueue_script('levelsjs', membership_url('membershipincludes/js/levels.js'), array( 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable' ), $this->build);

			if(version_compare( preg_replace('/-.*$/', '', $wp_version), "3.3", '<')) {
				wp_enqueue_style('levelscss', membership_url('membershipincludes/css/levels.css'), array('widgets'), $this->build);
			} else {
				wp_enqueue_style('levelscss', membership_url('membershipincludes/css/levels.css'), array(), $this->build);
			}

			wp_localize_script( 'levelsjs', 'membership', array( 	'deletelevel' => __('Are you sure you want to delete this level?','membership'),
																	'deactivatelevel' => __('Are you sure you want to deactivate this level?','membership'),
																	'movetopositive' => __('Moving to the Positive area will remove any Negative rules you have set - is that ok?','membership'),
																	'movetonegative' => __('Moving to the Negative area will remove any Positive rules you have set - is that ok?','membership')
																) );

			$this->handle_levels_updates();
		}

		function add_admin_header_membershipsubs() {

			global $wp_version;
			// Run the core header
			$this->add_admin_header_core();

			// Queue scripts and localise
			wp_enqueue_script('subsjs', membership_url('membershipincludes/js/subscriptions.js'), array( 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable' ), $this->build);

			if(version_compare( preg_replace('/-.*$/', '', $wp_version), "3.3", '<')) {
				wp_enqueue_style('subscss', membership_url('membershipincludes/css/subscriptions.css'), array('widgets'), $this->build);
			} else {
				wp_enqueue_style('subscss', membership_url('membershipincludes/css/subscriptions.css'), array(), $this->build);
			}

			wp_localize_script( 'subsjs', 'membership', array( 'deletesub' => __('Are you sure you want to delete this subscription?','membership'), 'deactivatesub' => __('Are you sure you want to deactivate this subscription?','membership') ) );

			$this->handle_subscriptions_updates();

		}

		function add_admin_header_members() {

			global $wp_version;
			// Run the core header
			$this->add_admin_header_core();

			wp_enqueue_script('membersjs', membership_url('membershipincludes/js/members.js'), array(), $this->build);

			if(version_compare( preg_replace('/-.*$/', '', $wp_version), "3.3", '<')) {
				// Using the level css file for now - maybe switch to a members specific one later
				wp_enqueue_style('memberscss', membership_url('membershipincludes/css/levels.css'), array('widgets'), $this->build);
			} else {
				// Using the level css file for now - maybe switch to a members specific one later
				wp_enqueue_style('memberscss', membership_url('membershipincludes/css/levels.css'), array(), $this->build);
			}

			wp_localize_script( 'membersjs', 'membership', array( 'deactivatemember' => __('Are you sure you want to deactivate this member?','membership') ) );


			$this->handle_members_updates();

		}

		function add_admin_header_membershipgateways() {
			$this->add_admin_header_core();

			$this->handle_gateways_panel_updates();
		}

		function add_admin_header_membershipoptions() {
			$this->add_admin_header_core();

			wp_enqueue_style('optionscss', membership_url('membershipincludes/css/options.css'), array(), $this->build);

			$this->handle_options_panel_updates();
		}

		function add_admin_header_membershipuser() {
			$this->add_admin_header_core();

			wp_enqueue_style('optionscss', membership_url('membershipincludes/css/options.css'), array(), $this->build);
		}

		function add_admin_header_membershipurlgroups() {
			// Run the core header
			$this->add_admin_header_core();

			wp_enqueue_script('groupsjs', membership_url('membershipincludes/js/urlgroup.js'), array(), $this->build);
			wp_localize_script( 'groupsjs', 'membership', array( 'deletegroup' => __('Are you sure you want to delete this url group?','membership') ) );


			$this->handle_urlgroups_updates();
		}

		function add_admin_header_membershippings() {
			// Run the core header
			$this->add_admin_header_core();

			wp_enqueue_script('pingsjs', membership_url('membershipincludes/js/ping.js'), array(), $this->build);
			wp_localize_script( 'pingsjs', 'membership', array( 'deleteping' => __('Are you sure you want to delete this ping and the associated history?','membership') ) );

			$this->handle_ping_updates();
		}

		// Panel handling functions

		function build_signup_stats() {

			$sql = $this->db->prepare( "SELECT YEAR(startdate) as year, MONTH(startdate)as month, DAY(startdate) as day, count(*) AS signedup FROM {$this->membership_relationships} WHERE startdate > DATE_SUB(CURDATE(), INTERVAL %d DAY) GROUP BY YEAR(startdate), MONTH(startdate), DAY(startdate) ORDER BY startdate DESC", 10 );

			$results = $this->db->get_results( $sql );

			if(!empty($results)) {

				$stats = array();
				$ticks = array();
				$data = array();
				foreach($results as $key => $res) {

					$stats[strtotime($res->year . "-" . $res->month . "-" . $res->day)] = (int) $res->signedup;

				}

				$startat = time();
				for($n = 0; $n < 11; $n++) {
					$switch = 10 - $n;
					$rdate = strtotime('-' . $switch . ' DAYS', $startat);

					$ticks[$n] = '"' . date('n', $rdate) . "/" . date('j', $rdate) . '"';

					if(isset($stats[strtotime(date("Y", $rdate) . "-" . date("n", $rdate) . "-" . date("j", $rdate))])) {
						$data[$n] = $stats[strtotime(date("Y", $rdate) . "-" . date("n", $rdate) . "-" . date("j", $rdate))];
					} else {
						$data[$n] = 0;
					}
				}

				$stats = $data;

				return compact('stats', 'ticks');

			} else {
				return false;
			}

		}

		function build_levels_stats() {

			$sql = "SELECT l.id, l.level_title, count(m.rel_id) as users FROM {$this->membership_levels} as l, {$this->membership_relationships} as m WHERE l.id = m.level_id GROUP BY l.id, l.level_title ORDER BY users DESC";

			$results = $this->db->get_results( $sql );

			if(!empty($results)) {

				$stats = array();
				$ticks = array();
				foreach($results as $key => $res) {

					$stats[] = (int) $res->users;
					$ticks[] = '"' . esc_html($res->level_title) . '"';
				}

				return compact('stats', 'ticks');

			} else {
				return false;
			}

		}

		function build_subs_stats() {

			$sql = "SELECT s.id, s.sub_name, count(m.rel_id) as users FROM {$this->subscriptions} as s, {$this->membership_relationships} as m WHERE s.id = m.sub_id GROUP BY s.id, s.sub_name ORDER BY users DESC";

			$results = $this->db->get_results( $sql );

			if(!empty($results)) {

				$stats = array();
				$ticks = array();
				foreach($results as $key => $res) {

					$stats[] = (int) $res->users;
					$ticks[] = '"' . esc_html($res->sub_name) . '"';
				}

				return compact('stats', 'ticks');

			} else {
				return false;
			}

		}

		function get_data($results) {

			$data = array();

			foreach( (array) $results as $key => $res) {
				$data[] = "[ " . $key . ", " . $res . " ]";
			}

			return "[ " . implode(", ", $data) . " ]";

		}

		function handle_membership_dashboard_updates() {

			global $page, $action;

			wp_reset_vars( array('action', 'page') );

			switch($action) {

				case 'activate':	check_admin_referer('toggle-plugin');
									update_option('membership_active', 'yes');
									wp_safe_redirect( wp_get_referer() );
									break;

				case 'deactivate':	check_admin_referer('toggle-plugin');
									update_option('membership_active', 'no');
									wp_safe_redirect( wp_get_referer() );
									break;

				default:			do_action('membership_dashboard_' . $action);
									break;

			}

			wp_enqueue_script('flot_js', membership_url('membershipincludes/js/jquery.flot.min.js'), array('jquery'));
			wp_enqueue_script('mdash_js', membership_url('membershipincludes/js/dashboard.js'), array('jquery'));

			wp_localize_script( 'mdash_js', 'membership', array( 'signups' => __('Signups','membership'), 'members' => __('Members','membership') ) );


			add_action ('admin_head', array(&$this, 'dashboard_iehead'));
			add_action ('admin_head', array(&$this, 'dashboard_chartdata'));

		}

		function dashboard_chartdata() {
			$returned = $this->build_signup_stats();
			$levels = $this->build_levels_stats();
			$subs = $this->build_subs_stats();

			echo "\n" . '<script type="text/javascript">';
			echo "\n" . '/* <![CDATA[ */ ' . "\n";

			echo "var membershipdata = {\n";
				echo "chartonestats : " . $this->get_data($returned['stats']) . ",\n";
				echo "chartoneticks : " . $this->get_data($returned['ticks']) . ",\n";

				echo "charttwostats : " . $this->get_data($levels['stats']) . ",\n";
				echo "charttwoticks : " . $this->get_data($levels['ticks']) . ",\n";

				echo "chartthreestats : " . $this->get_data($subs['stats']) . ",\n";
				echo "chartthreeticks : " . $this->get_data($subs['ticks']) . "\n";
			echo "};\n";

			echo "\n" . '/* ]]> */ ';
			echo '</script>';
		}

		function dashboard_iehead() {
			echo '<!--[if IE]><script language="javascript" type="text/javascript" src="' . membership_url('membershipincludes/js/excanvas.min.js') . '"></script><![endif]-->';
		}

		function dashboard_members() {

			global $page, $action;

			$plugin = get_plugin_data(membership_dir('membership.php'));

			$membershipactive = M_get_membership_active();

			echo __('Membership protection ','membership');
			echo __(' is ', 'membership');

			// Membership active toggle
			if($membershipactive == 'no') {
				echo '<strong>' . __('disabled', 'membership') . '</strong> <a id="enablemembership" href="' . wp_nonce_url("?page=" . $page. "&amp;action=activate", 'toggle-plugin') . '" title="' . __('Click here to enable the plugin','membership') . '">' . __('[Enable it]','membership') . '</a>';
			} else {
				echo '<strong>' . __('enabled', 'membership') . '</strong> <a id="enablemembership" href="' . wp_nonce_url("?page=" . $page. "&amp;action=deactivate", 'toggle-plugin') . '" title="' . __('Click here to enable the plugin','membership') . '">' . __('[Disable it]','membership') . '</a>';
			}

			echo '<br/><br/>';

			echo "<strong>" . __('Member breakdown', 'membership') . "</strong><br/>";

			$detail = $this->get_subscriptions_and_levels(array('sub_status' => 'active'));
			$subs = $this->get_subscriptions(array('sub_status' => 'active'));

			$levels = $this->get_membership_levels(array('level_id' => 'active'));

			echo "<table style='width: 100%;'>";
			echo "<tbody>";
			echo "<tr>";
			echo "<td style='width: 48%' valign='top'>";
			if($levels) {
				$levelcount = 0;
				echo "<table style='width: 100%;'>";
				echo "<tbody>";
					echo "<tr>";
					echo "<td colspan='2'><strong>" . __('Levels','membership') . "</strong></td>";
					echo "</tr>";
					foreach($levels as $key => $level) {
						echo "<tr>";
							echo "<td><a href='" . admin_url('admin.php?page=membershiplevels&action=edit&level_id=') . $level->id . "'>" . esc_html($level->level_title) . "</a></td>";
							// find out how many people are in this level
							$thiscount = $this->count_on_level( $level->id );

							echo "<td style='text-align: right;'>" . (int) $thiscount . "</td>";
							$levelcount += (int) $thiscount;
						echo "</tr>";
					}
				echo "</tbody>";
				echo "</table>";
			}
			echo "</td>";

			echo "<td style='width: 48%' valign='top'>";
			if($subs) {
				$subcount = 0;
				echo "<table style='width: 100%;'>";
				echo "<tbody>";
					echo "<tr>";
					echo "<td colspan='2'><strong>" . __('Subscriptions','membership') . "</strong></td>";
					echo "</tr>";
					foreach($subs as $key => $sub) {
						echo "<tr>";
							echo "<td><a href='" . admin_url('admin.php?page=membershipsubs&action=edit&sub_id=') . $sub->id . "'>" . $sub->sub_name . "</a></td>";
							// find out how many people are in this sub
							$thiscount = $this->count_on_sub( $sub->id );

							echo "<td style='text-align: right;'>" . (int) $thiscount . "</td>";
							$subcount += (int) $thiscount;
						echo "</tr>";
					}
				echo "</tbody>";
				echo "</table>";
			}
			echo "</td>";

			echo "</tr>";
			echo "</tbody>";
			echo "</table>";

			echo "<br/><strong>" . __('Member counts', 'membership') . "</strong><br/>";

			echo "<table style='width: 100%;'>";
			echo "<tbody>";
			echo "<tr>";
			echo "<td style='width: 48%' valign='top'>";

				echo "<table style='width: 100%;'>";
				echo "<tbody>";

					$usercount = $this->db->get_var( "SELECT count(*) FROM {$this->db->users} INNER JOIN {$this->db->usermeta} ON {$this->db->users}.ID = {$this->db->usermeta}.user_id WHERE {$this->db->usermeta}.meta_key = '{$this->db->prefix}capabilities'" );

					echo "<tr>";
						echo "<td>" . __('Total Members', 'membership') . "</td>";
						echo "<td style='text-align: right;'>" . $usercount . "</td>";
					echo "</tr>";

					$deactivecount = $this->db->get_var( $this->db->prepare("SELECT count(*) FROM {$this->db->usermeta} WHERE meta_key = %s AND meta_value = %s", $this->db->prefix . 'membership_active' , 'no') );

					echo "<tr>";
						echo "<td>" . __('Deactivated Members', 'membership') . "</td>";
						echo "<td style='text-align: right;'>" . $deactivecount . "</td>";
					echo "</tr>";

				echo "</tbody>";
				echo "</table>";

			echo "</td>";

			echo "<td style='width: 48%' valign='top'></td>";

			echo "</tr>";
			echo "</tbody>";
			echo "</table>";

		}

		function dashboard_statistics() {

			echo "<div id='memchartone'></div>";
			echo "<div id='memcharttwo'></div>";
			echo "<div id='memchartthree'></div>";

			do_action( 'membership_dashboard_statistics' );
		}

		function handle_membership_panel() {

			?>
			<div class='wrap nosubsub'>
				<div class="icon32" id="icon-index"><br></div>
				<h2><?php _e('Membership dashboard','membership'); ?></h2>

				<?php
					$this->potter->conditional_show();
				?>

				<div id="dashboard-widgets-wrap">

				<div class="metabox-holder" id="dashboard-widgets">
					<div style="width: 49%;" class="postbox-container">
						<div class="meta-box-sortables ui-sortable" id="normal-sortables">

							<div class="postbox " id="dashboard_right_now">
								<h3 class="hndle"><span><?php _e('Members','membership'); ?></span></h3>
								<div class="inside">
									<?php $this->dashboard_members(); ?>
									<br class="clear">
								</div>
							</div>

							<?php
							do_action( 'membership_dashboard_left' );
							?>
						</div>
					</div>

					<div style="width: 49%;" class="postbox-container">
						<div class="meta-box-sortables ui-sortable" id="side-sortables">

							<?php
							do_action( 'membership_dashboard_right_top' );
							?>

							<div class="postbox " id="dashboard_quick_press">
								<h3 class="hndle"><span><?php _e('Statistics','membership'); ?></span></h3>
								<div class="inside">
									<?php $this->dashboard_statistics(); ?>
									<br class="clear">
								</div>
							</div>

							<?php
							do_action( 'membership_dashboard_right' );
							?>

						</div>
					</div>

					<div style="display: none; width: 49%;" class="postbox-container">
						<div class="meta-box-sortables ui-sortable" id="column3-sortables" style="">
						</div>
					</div>

					<div style="display: none; width: 49%;" class="postbox-container">
						<div class="meta-box-sortables ui-sortable" id="column4-sortables" style="">
						</div>
					</div>
				</div>

				<div class="clear"></div>
				</div>

			</div> <!-- wrap -->
			<?php

		}

		function handle_members_updates() {

			global $action, $page;

			wp_reset_vars( array('action', 'page') );

			if(isset($_GET['doaction']) || isset($_GET['doaction2'])) {
				if(addslashes($_GET['action']) == 'toggle' || addslashes($_GET['action2']) == 'toggle') {
					$action = 'bulk-toggle';
				}
			}

			switch(addslashes($action)) {

				case 'removeheader':	$this->dismiss_user_help( $page );
										wp_safe_redirect( remove_query_arg( 'action' ) );
										break;

				case 'toggle':	if(isset($_GET['member_id'])) {
									$user_id = (int) $_GET['member_id'];

									check_admin_referer('toggle-member_' . $user_id);

									$member = new M_Membership($user_id);

									if( $member->toggle_activation() ) {
										wp_safe_redirect( add_query_arg( 'msg', 7, wp_get_referer() ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 8, wp_get_referer() ) );
									}

								}
								break;

				case 'bulk-toggle':
								check_admin_referer('bulk-members');
								foreach($_GET['users'] AS $value) {
									if(is_numeric($value)) {
										$user_id = (int) $value;

										$member = new M_Membership($user_id);

										$member->toggle_activation();
									}
								}

								wp_safe_redirect( add_query_arg( 'msg', 7, wp_get_referer() ) );
								break;

				case 'bulkaddlevel-level-complete':
				case 'addlevel-level-complete':
								check_admin_referer($action);
								$members_id = $_POST['member_id'];

								$members = explode(',', $members_id);
								if($members) {
									foreach($members as $member_id) {
										$member = new M_Membership($member_id);

										$tolevel_id = (int) $_POST['tolevel_id'];
										if($tolevel_id) {
											$member->add_level($tolevel_id);
										}
									}
								}

								$this->update_levelcounts();

								wp_safe_redirect( add_query_arg( 'msg', 3, wp_get_original_referer() ) );
								break;

				case 'bulkdroplevel-level-complete':
				case 'droplevel-level-complete':
								check_admin_referer($action);
								$members_id = $_POST['member_id'];

								$members = explode(',', $members_id);
								if($members) {
									foreach($members as $member_id) {
										$member = new M_Membership($member_id);

										$fromlevel_id = (int) $_POST['fromlevel_id'];
										if($fromlevel_id) {
											$member->drop_level($fromlevel_id);
										}
									}
								}

								$this->update_levelcounts();

								wp_safe_redirect( add_query_arg( 'msg', 3, wp_get_original_referer() ) );
								break;

				case 'bulkmovelevel-level-complete':
				case 'movelevel-level-complete':
								check_admin_referer($action);
								$members_id = $_POST['member_id'];

								$members = explode(',', $members_id);
								if($members) {
									foreach($members as $member_id) {
										$member = new M_Membership($member_id);

										$fromlevel_id = (int) $_POST['fromlevel_id'];
										$tolevel_id = (int) $_POST['tolevel_id'];
										if($fromlevel_id && $tolevel_id) {
											$member->move_level($fromlevel_id, $tolevel_id);
										}
									}
								}

								$this->update_levelcounts();

								wp_safe_redirect( add_query_arg( 'msg', 3, wp_get_original_referer() ) );
								break;

				case 'bulkaddsub-sub-complete':
				case 'addsub-sub-complete':
								check_admin_referer($action);
								$members_id = $_POST['member_id'];

								$members = explode(',', $members_id);
								if($members) {
									foreach($members as $member_id) {
										$member = new M_Membership($member_id);

										$tosub_id = $_POST['tosub_id'];
										if($tosub_id) {
											$subs = explode('-',$tosub_id);
											if(count($subs) == 3) {
												$member->add_subscription($subs[0], $subs[1], $subs[2]);
											}
										}
									}
								}

								$this->update_levelcounts();
								$this->update_subcounts();

								wp_safe_redirect( add_query_arg( 'msg', 3, wp_get_original_referer() ) );
								break;

				case 'bulkdropsub-sub-complete':
				case 'dropsub-sub-complete':
								check_admin_referer($action);
								$members_id = $_POST['member_id'];

								$members = explode(',', $members_id);
								if($members) {
									foreach($members as $member_id) {
										$member = new M_Membership($member_id);

										$fromsub_id = (int) $_POST['fromsub_id'];
										if($fromsub_id) {
											$member->drop_subscription($fromsub_id);
										}
									}
								}

								$this->update_levelcounts();
								$this->update_subcounts();

								wp_safe_redirect( add_query_arg( 'msg', 3, wp_get_original_referer() ) );
								break;

				case 'bulkmovesub-sub-complete':
				case 'movesub-sub-complete':
								check_admin_referer($action);
								$members_id = $_POST['member_id'];

								$members = explode(',', $members_id);
								if($members) {
									foreach($members as $member_id) {
										$member = new M_Membership($member_id);

										$fromsub_id = (int) $_POST['fromsub_id'];
										$tosub_id = $_POST['tosub_id'];
										if($fromsub_id && $tosub_id) {
											$subs = explode('-',$tosub_id);
											if(count($subs) == 3) {
												$member->move_subscription($fromsub_id, $subs[0], $subs[1], $subs[2]);
											}
										}
									}
								}

								$this->update_levelcounts();
								$this->update_subcounts();

								wp_safe_redirect( add_query_arg( 'msg', 3, wp_get_original_referer() ) );
								break;

					case 'bulkmovegateway-gateway-complete':
					case 'movegateway-gateway-complete':

									check_admin_referer($action);
									$members_id = $_POST['member_id'];

									$members = explode(',', $members_id);
									if($members) {
										foreach($members as $member_id) {
											$member = new M_Membership($member_id);

											$fromgateway = $_POST['fromgateway'];
											$togateway = $_POST['togateway'];
											if(!empty($fromgateway) && !empty($togateway)) {

												$relationships = $member->get_relationships();
												foreach($relationships as $rel) {
													if($rel->usinggateway == $fromgateway) {
														$member->update_relationship_gateway( $rel->rel_id, $fromgateway, $togateway );

													}
												}
											}
										}
									}

									wp_safe_redirect( add_query_arg( 'msg', 3, wp_get_original_referer() ) );
									break;

			}

		}

		function handle_edit_member() {

			global $action, $page;

			wp_reset_vars( array('action', 'page') );

		}

		function handle_member_gateway_op( $operation = 'move', $member_id = false ) {

			global $action, $page, $action2, $M_Gateways;

			wp_reset_vars( array('action', 'page', 'action2') );

			if(empty($action) && !empty($action2)) $action = $action2;

			$gateways = apply_filters('M_gateways_list', array());

			$active = get_option('membership_activated_gateways', array());

			if(isset($_GET['fromgateway']) && !empty($_GET['fromgateway'])) {
				$fromgateway = stripslashes($_GET['fromgateway']);
			} else {
				$fromgateway = '';
			}

			switch($operation) {

				case 'move':	$title = __('Move subscription to another gateway','membership');
								$formdescription = __('A subscription gateway handles the payment and renewal forms displayed for a subscription. Changing this should not be undertaken lightly, it can seriously mess up the subscriptions of your members.','membership') . "<br/><br/>";

								$html = "<h3>" . __('Gateway to move from for this / these member(s)','membership') . "</h3>";
								$html .= "<div class='level-details'>";
								$html .= "<select name='fromgateway' id='fromgateway' class='wide'>\n";
								$html .= "<option value='0'>" . __('Select the gateway to move from.','membership') . "</option>\n";
								$html .= "<option value='admin'>" . esc_html('admin' . " - " . "admin default gateway") . "</option>\n";
								if($gateways) {
									foreach($gateways as $key => $gateway) {
										if(in_array($key, $active)) {
											$html .= "<option value='" . esc_attr($key) . "'";
											if( $fromgateway == $key ) {
												$html .= " selected='selected'";
											}
											$html .= ">" . esc_html($key . " - " . $gateway) . "</option>\n";
										}
									}
								}
								$html .= "</select>\n";
								$html .= "</div>";

								$html .= "<h3>" . __('Gateway to move to for this / these member(s)','membership') . "</h3>";
								$html .= "<div class='level-details'>";
								$html .= "<select name='togateway' id='togateway' class='wide'>\n";
								$html .= "<option value='0'>" . __('Select the gateway to move to.','membership') . "</option>\n";
								$html .= "<option value='admin'>" . esc_html('admin' . " - " . "admin default gateway") . "</option>\n";
								reset($gateways);
								if($gateways) {
									foreach($gateways as $key => $gateway) {
										if(in_array($key, $active)) {
											$html .= "<option value='" . esc_attr($key) . "'>" . esc_html($key . " - " . $gateway) . "</option>\n";
										}
									}
								}
								$html .= "</select>\n";
								$html .= "</div>";

								$button = "Move";
								break;

			}

			?>
			<div class='wrap nosubsub'>
				<div class="icon32" id="icon-users"><br></div>
				<h2><?php echo $title; ?></h2>
				<form action='admin.php?page=<?php echo $page; ?>' method='post'>

					<div class='level-liquid-left'>

						<div id='level-left'>
							<div id='edit-level' class='level-holder-wrap'>
								<div class='sidebar-name no-movecursor'>
									<h3><?php echo esc_html($title); ?></h3>
								</div>
								<div class='level-holder'>
									<br />
									<p class='description'><?php echo $formdescription;  ?></p>
									<?php
										echo $html;
									?>

									<div class='buttons'>
										<?php
											wp_original_referer_field(true, 'previous'); wp_nonce_field($action . '-gateway-complete');
										?>
										<a href='?page=<?php echo $page; ?>' class='cancellink' title='Cancel add'><?php _e('Cancel', 'membership'); ?></a>
										<input type='submit' value='<?php _e($button, 'membership'); ?>' class='button-primary' />
										<input type='hidden' name='action' value='<?php esc_attr_e($action . '-gateway-complete'); ?>' />
										<?php
											if(is_array($member_id)) {
												?>
												<input type='hidden' name='member_id' value='<?php esc_attr_e(implode(',',$member_id)); ?>' />
												<?php
											} else {
												?>
												<input type='hidden' name='member_id' value='<?php esc_attr_e($member_id); ?>' />
												<?php
											}

										?>
									</div>

								</div>
							</div>
						</div>

					</div> <!-- level-liquid-left -->

				</form>
			</div> <!-- wrap -->
			<?php

		}

		function handle_member_level_op($operation = 'add', $member_id = false) {

			global $action, $page, $action2;

			wp_reset_vars( array('action', 'page', 'action2') );

			if(empty($action) && !empty($action2)) $action = $action2;

			if(isset($_GET['fromlevel']) && !empty($_GET['fromlevel'])) {
				$fromlevel = $_GET['fromlevel'];
			} else {
				$fromlevel = '';
			}

			switch($operation) {

				case 'add':		$title = __('Add member to a level','membership');
								$formdescription = __('A membership level controls the amount of access to the sites content this member will have.','membership') . "<br/><br/>";
								$formdescription .= __('By adding a membership level, you may actually be removing existing access to content.','membership');

								$html = "<h3>" . __('Level to add for this / these member(s)','membership') . "</h3>";
								$html .= "<div class='level-details'>";
								$html .= "<select name='tolevel_id' id='tolevel_id' class='wide'>\n";
								$html .= "<option value='0'>" . __('Select the level to add.','membership') . "</option>\n";
								$levels = $this->get_membership_levels(array('level_id' => 'active'));
								if($levels) {
									foreach($levels as $key => $level) {
										$html .= "<option value='" . esc_attr($level->id) . "'";
										$html .= ">" . esc_html($level->level_title) . "</option>\n";
									}
								}
								$html .= "</select>\n";
								$html .= "</div>";

								$button = "Add";

								break;

				case 'move':	$title = __('Move member to another level','membership');
								$formdescription = __('A membership level controls the amount of access to the sites content this member will have.','membership') . "<br/><br/>";

								$html = "<h3>" . __('Level to move from for this / these member(s)','membership') . "</h3>";
								$html .= "<div class='level-details'>";
								$html .= "<select name='fromlevel_id' id='fromlevel_id' class='wide'>\n";
								$html .= "<option value='0'>" . __('Select the level to move from.','membership') . "</option>\n";
								$levels = $this->get_membership_levels(array('level_id' => 'active'));
								if($levels) {
									foreach($levels as $key => $level) {
										$html .= "<option value='" . esc_attr($level->id) . "'";
										if($fromlevel == $level->id) $html .= " selected='selected'";
										$html .= ">" . esc_html($level->level_title) . "</option>\n";
									}
								}
								$html .= "</select>\n";
								$html .= "</div>";

								$html .= "<h3>" . __('Level to move to for this / these member(s)','membership') . "</h3>";
								$html .= "<div class='level-details'>";
								$html .= "<select name='tolevel_id' id='tolevel_id' class='wide'>\n";
								$html .= "<option value='0'>" . __('Select the level to move to.','membership') . "</option>\n";
								reset($levels);
								if($levels) {
									foreach($levels as $key => $level) {
										$html .= "<option value='" . esc_attr($level->id) . "'";
										$html .= ">" . esc_html($level->level_title) . "</option>\n";
									}
								}
								$html .= "</select>\n";
								$html .= "</div>";

								$button = "Move";
								break;

				case 'drop':	$title = __('Drop member from level','membership');

								$formdescription = __('A membership level controls the amount of access to the sites content this member will have.','membership') . "<br/><br/>";
								$formdescription .= __('By removing a membership level, you may actually be increasing existing access to content.','membership');

								$html = "<h3>" . __('Level to drop for this / these member(s)','membership') . "</h3>";
								$html .= "<div class='level-details'>";
								$html .= "<select name='fromlevel_id' id='fromlevel_id' class='wide'>\n";
								$html .= "<option value=''>" . __('Select the level to remove.','membership') . "</option>\n";
								$levels = $this->get_membership_levels(array('level_id' => 'active'));
								if($levels) {
									foreach($levels as $key => $level) {
										$html .= "<option value='" . esc_attr($level->id) . "'";
										if($fromlevel == $level->id) $html .= " selected='selected'";
										$html .= ">" . esc_html($level->level_title) . "</option>\n";
									}
								}
								$html .= "</select>\n";
								$html .= "</div>";

								$button = "Drop";

								break;


			}

			?>
			<div class='wrap nosubsub'>
				<div class="icon32" id="icon-users"><br></div>
				<h2><?php echo $title; ?></h2>
				<form action='admin.php?page=<?php echo $page; ?>' method='post'>

					<div class='level-liquid-left'>

						<div id='level-left'>
							<div id='edit-level' class='level-holder-wrap'>
								<div class='sidebar-name no-movecursor'>
									<h3><?php echo esc_html($title); ?></h3>
								</div>
								<div class='level-holder'>
									<br />
									<p class='description'><?php echo $formdescription;  ?></p>
									<?php
										echo $html;
									?>

									<div class='buttons'>
										<?php
											wp_original_referer_field(true, 'previous'); wp_nonce_field($action . '-level-complete');
										?>
										<a href='?page=<?php echo $page; ?>' class='cancellink' title='Cancel add'><?php _e('Cancel', 'membership'); ?></a>
										<input type='submit' value='<?php _e($button, 'membership'); ?>' class='button-primary' />
										<input type='hidden' name='action' value='<?php esc_attr_e($action . '-level-complete'); ?>' />
										<?php
											if(is_array($member_id)) {
												?>
												<input type='hidden' name='member_id' value='<?php esc_attr_e(implode(',',$member_id)); ?>' />
												<?php
											} else {
												?>
												<input type='hidden' name='member_id' value='<?php esc_attr_e($member_id); ?>' />
												<?php
											}

										?>
									</div>

								</div>
							</div>
						</div>

					</div> <!-- level-liquid-left -->

				</form>
			</div> <!-- wrap -->
			<?php

		}

		function handle_member_subscription_op($operation = 'add', $member_id = false) {

			global $action, $page, $action2;

			wp_reset_vars( array('action', 'page', 'action2') );

			if(empty($action) && !empty($action2)) $action = $action2;

			if(isset($_GET['fromsub']) && !empty($_GET['fromsub'])) {
				$fromsub = $_GET['fromsub'];
			} else {
				$fromsub = '';
			}

			switch($operation) {

				case 'add':		$title = __('Add member to a subscription','membership');
								$formdescription = __('A subscription controls the levels a site member has access to / passes through.','membership') . "<br/><br/>";
								$formdescription .= __('Depending on your payment gateway, adding a subscription here may not set up a payment subscription.','membership');

								$html = "<h3>" . __('Subscription and level to add for this / these member(s)','membership') . "</h3>";
								$html .= "<div class='level-details'>";
								$html .= "<select name='tosub_id' id='tosub_id' class='wide'>\n";
								$html .= "<option value='0'>" . __('Select the level to add.','membership') . "</option>\n";

								$subs = $this->get_subscriptions_and_levels( array('sub_status' => 'active') );
								if($subs) {
									$sub_id = false;
									foreach($subs as $key => $sub) {
										if($sub_id != $sub->sub_id) {
											$sub_id = $sub->sub_id;

											$html .= "<optgroup label='";
											$html .= $sub->sub_name;
											$html .= "'>";

										}
										$html .= "<option value='" . esc_attr($sub->sub_id) . "-" . esc_attr($sub->level_id) . "-" . esc_attr($sub->level_order) . "'";
										$html .= ">" . $sub->level_order . " : " . esc_html($sub->sub_name . " - " . $sub->level_title) . "</option>\n";
									}
								}
								$html .= "</select>\n";
								$html .= "</div>";

								$button = "Add";
								break;

				case 'move':	$title = __('Move member to another subscription level','membership');
								$formdescription = __('A subscription controls the levels a site member has access to / passes through.','membership') . "<br/><br/>";
								$formdescription .= __('Depending on your payment gateway, moving a subscription here may not alter a members existing payment subscription.','membership');

								$html = "<h3>" . __('Subscription to move from for this / these member(s)','membership') . "</h3>";
								$html .= "<div class='level-details'>";
								$html .= "<select name='fromsub_id' id='fromsub_id' class='wide'>\n";
								$html .= "<option value='0'>" . __('Select the subscription to move from.','membership') . "</option>\n";
								$subs = $this->get_subscriptions( array('sub_status' => 'active'));
								if($subs) {
									foreach($subs as $key => $sub) {
										$html .= "<option value='" . esc_attr($sub->id) . "'";
										if($fromsub == $sub->id) $html .= " selected='selected'";
										$html .= ">" . esc_html($sub->sub_name) . "</option>\n";
									}
								}
								$html .= "</select>\n";
								$html .= "</div>";

								$html .= "<h3>" . __('Subscription and Level to move to for this / these member(s)','membership') . "</h3>";
								$html .= "<div class='level-details'>";
								$html .= "<select name='tosub_id' id='tosub_id' class='wide'>\n";
								$html .= "<option value='0'>" . __('Select the level to move to.','membership') . "</option>\n";
								$subs = $this->get_subscriptions_and_levels( array('sub_status' => 'active') );
								if($subs) {
									$sub_id = false;
									foreach($subs as $key => $sub) {
										if($sub_id != $sub->sub_id) {
											$sub_id = $sub->sub_id;

											$html .= "<optgroup label='";
											$html .= $sub->sub_name;
											$html .= "'>";

										}
										$html .= "<option value='" . esc_attr($sub->sub_id) . "-" . esc_attr($sub->level_id) . "-" . esc_attr($sub->level_order) . "'>" . $sub->level_order . " : " . esc_html($sub->sub_name . " - " . $sub->level_title) . "</option>\n";
									}
								}
								$html .= "</select>\n";
								$html .= "</div>";

								$button = "Move";
								break;

				case 'drop':	$title = __('Drop member from subscription','membership');

								$formdescription = __('A subscription controls the levels a site member has access to / passes through.','membership') . "<br/><br/>";
								$formdescription .= __('Depending on the payment gateway, removing a subscription will not automatically cancel a payment subscription.','membership');

								$html = "<h3>" . __('Subscription to drop for this / these member(s)','membership') . "</h3>";
								$html .= "<div class='level-details'>";
								$html .= "<select name='fromsub_id' id='fromsub_id' class='wide'>\n";
								$html .= "<option value=''>" . __('Select the subscription to remove.','membership') . "</option>\n";
								$subs = $this->get_subscriptions( array('sub_status' => 'active'));
								if($subs) {
									foreach($subs as $key => $sub) {
										$html .= "<option value='" . esc_attr($sub->id) . "'";
										if($fromsub == $sub->id) $html .= " selected='selected'";
										$html .= ">" . esc_html($sub->sub_name) . "</option>\n";
									}
								}
								$html .= "</select>\n";
								$html .= "</div>";

								$button = "Drop";
								break;


			}

			?>
			<div class='wrap nosubsub'>
				<div class="icon32" id="icon-users"><br></div>
				<h2><?php echo $title; ?></h2>
				<form action='admin.php?page=<?php echo $page; ?>' method='post'>

					<div class='level-liquid-left'>

						<div id='level-left'>
							<div id='edit-level' class='level-holder-wrap'>
								<div class='sidebar-name no-movecursor'>
									<h3><?php echo esc_html($title); ?></h3>
								</div>
								<div class='level-holder'>
									<br />
									<p class='description'><?php echo $formdescription;  ?></p>
									<?php
										echo $html;
									?>

									<div class='buttons'>
										<?php
											wp_original_referer_field(true, 'previous'); wp_nonce_field($action . '-sub-complete');
										?>
										<a href='?page=<?php echo $page; ?>' class='cancellink' title='Cancel add'><?php _e('Cancel', 'membership'); ?></a>
										<input type='submit' value='<?php _e($button, 'membership'); ?>' class='button-primary' />
										<input type='hidden' name='action' value='<?php esc_attr_e($action . '-sub-complete'); ?>' />
										<?php
											if(is_array($member_id)) {
												?>
												<input type='hidden' name='member_id' value='<?php esc_attr_e(implode(',',$member_id)); ?>' />
												<?php
											} else {
												?>
												<input type='hidden' name='member_id' value='<?php esc_attr_e($member_id); ?>' />
												<?php
											}

										?>
									</div>

								</div>
							</div>
						</div>

					</div> <!-- level-liquid-left -->

				</form>
			</div> <!-- wrap -->
			<?php


		}

		function handle_members_panel() {

			global $action, $page;

			wp_reset_vars( array('action', 'page') );

			require_once('class.membersearch.php');

			// bulk actions
			if(isset($_GET['doaction'])) {
				$action = $_GET['action'];

			} elseif(isset($_GET['doaction2'])) {
				$action = $_GET['action2'];
			}

			switch(addslashes($action)) {

				case 'addlevel':	if(isset($_GET['member_id'])) {
										$member_id = (int) $_GET['member_id'];
										$this->handle_member_level_op('add', $member_id);
										return;
									}
									break;

				case 'movelevel':	if(isset($_GET['member_id'])) {
										$member_id = (int) $_GET['member_id'];
										check_admin_referer('movelevel-member-' . $member_id);
										$this->handle_member_level_op('move', $member_id);
										return;
									}
									break;

				case 'droplevel':	if(isset($_GET['member_id'])) {
										$member_id = (int) $_GET['member_id'];
										check_admin_referer('droplevel-member-' . $member_id);
										$this->handle_member_level_op('drop', $member_id);
										return;
									}
									break;

				case 'bulkaddlevel':
									if(isset($_GET['users'])) {
										check_admin_referer('bulk-members');
										$this->handle_member_level_op('add', $_GET['users']);
										return;
									}
									break;

				case 'bulkmovelevel':
									if(isset($_GET['users'])) {
										check_admin_referer('bulk-members');
										$this->handle_member_level_op('move', $_GET['users']);
										return;
									}
									break;

				case 'bulkdroplevel':
									if(isset($_GET['users'])) {
										check_admin_referer('bulk-members');
										$this->handle_member_level_op('drop', $_GET['users']);
										return;
									}
									break;

				case 'addsub':		if(isset($_GET['member_id'])) {
										$member_id = (int) $_GET['member_id'];
										$this->handle_member_subscription_op('add', $member_id);
										return;
									}
									break;

				case 'movesub':		if(isset($_GET['member_id'])) {
										$member_id = (int) $_GET['member_id'];
										check_admin_referer('movesub-member-' . $member_id);
										$this->handle_member_subscription_op('move', $member_id);
										return;
									}
									break;

				case 'dropsub':		if(isset($_GET['member_id'])) {
										$member_id = (int) $_GET['member_id'];
										check_admin_referer('dropsub-member-' . $member_id);
										$this->handle_member_subscription_op('drop', $member_id);
										return;
									}
									break;

				case 'bulkaddsub':	if(isset($_GET['users'])) {
										check_admin_referer('bulk-members');
										$this->handle_member_subscription_op('add', $_GET['users']);
										return;
									}
									break;
				case 'bulkmovesub':
									if(isset($_GET['users'])) {
										check_admin_referer('bulk-members');
										$this->handle_member_subscription_op('move', $_GET['users']);
										return;
									}
									break;
				case 'bulkdropsub':
									if(isset($_GET['users'])) {
										check_admin_referer('bulk-members');
										$this->handle_member_subscription_op('drop', $_GET['users']);
										return;
									}
									break;

				case 'bulkmovegateway':
									if(isset($_GET['users'])) {
										check_admin_referer('bulk-members');
										$this->handle_member_gateway_op('move', $_GET['users']);
										return;
									}
									break;

				case 'movegateway':
									if(isset($_GET['member_id'])) {
										$member_id = (int) $_GET['member_id'];
										check_admin_referer('movegateway-member-' . $member_id);
										$this->handle_member_gateway_op('move', $member_id);
										return;
									}
									break;

				case 'edit':	if(isset($_GET['level_id'])) {
									$level_id = (int) $_GET['level_id'];
									$this->handle_level_edit_form($level_id);
									return; // So we don't see the rest of this page
								}
								break;

			}

			$filter = array();

			if(isset($_GET['s'])) {
				$s = stripslashes($_GET['s']);
				$filter['s'] = $s;
			} else {
				$s = '';
			}

			$sub_id = null; $level_id = null;

			if(isset($_GET['doactionsub'])) {
				if(addslashes($_GET['sub_op']) != '') {
					$sub_id = addslashes($_GET['sub_op']);
				}
			}

			if(isset($_GET['doactionsub2'])) {
				if(addslashes($_GET['sub_op2']) != '') {
					$sub_id = addslashes($_GET['sub_op2']);
				}
			}

			if(isset($_GET['doactionlevel'])) {
				if(addslashes($_GET['level_op']) != '') {
					$level_id = addslashes($_GET['level_op']);
				}
			}

			if(isset($_GET['doactionlevel2'])) {
				if(addslashes($_GET['level_op2']) != '') {
					$level_id = addslashes($_GET['level_op2']);
				}
			}

			if(isset($_GET['doactionactive'])) {
				if(addslashes($_GET['active_op']) != '') {
					$active_op = addslashes($_GET['active_op']);
				}
			}

			if(isset($_GET['doactionactive2'])) {
				if(addslashes($_GET['active_op2']) != '') {
					$active_op = addslashes($_GET['active_op2']);
				}
			}

			$usersearch = isset($_GET['s']) ? $_GET['s'] : null;
			$userspage = isset($_GET['userspage']) ? $_GET['userspage'] : null;
			$role = null;

			if(empty($active_op)) $active_op = '';

			// Query the users
			$wp_user_search = new M_Member_Search($usersearch, $userspage, $sub_id, $level_id, $active_op);

			$messages = array();
			$messages[1] = __('Member added.','membership');
			$messages[2] = __('Member deleted.','membership');
			$messages[3] = __('Member updated.','membership');
			$messages[4] = __('Member not added.','membership');
			$messages[5] = __('Member not updated.','membership');
			$messages[6] = __('Member not deleted.','membership');

			$messages[7] = __('Member activation toggled.','membership');
			$messages[8] = __('Member activation not toggled.','membership');

			$messages[9] = __('Members updated.','membership');

			?>
			<div class='wrap nosubsub'>
				<div class="icon32" id="icon-users"><br></div>
				<h2><?php _e('Edit Members','membership'); ?></h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('msg'), $_SERVER['REQUEST_URI']);
				}

				if($this->show_user_help( $page )) {
					?>
					<div class='screenhelpheader'>
						<a href="admin.php?page=<?php echo $page; ?>&amp;action=removeheader" class="welcome-panel-close"><?php _e('Dismiss','membership'); ?></a>
						<?php
						ob_start();
						include_once(membership_dir('membershipincludes/help/header.members.php'));
						echo ob_get_clean();
						?>
					</div>
					<?php
				}
				?>

				<form method="get" action="?page=<?php echo esc_attr($page); ?>" class="search-form">
				<p class="search-box">
					<input type='hidden' name='page' value='<?php echo esc_attr($page); ?>' />
					<label for="membership-search-input" class="screen-reader-text"><?php _e('Search Members','membership'); ?>:</label>
					<input type="text" value="<?php echo esc_attr($s); ?>" name="s" id="membership-search-input">
					<input type="submit" class="button" value="<?php _e('Search Members','membership'); ?>">
				</p>
				</form>

				<br class='clear' />

				<form method="get" action="?page=<?php echo esc_attr($page); ?>" id="members-filter">

				<input type='hidden' name='page' value='<?php echo esc_attr($page); ?>' />

				<div class="tablenav">

				<?php if ( $wp_user_search->results_are_paged() ) : ?>
					<div class="tablenav-pages"><?php $wp_user_search->page_links(); ?></div>
				<?php endif; ?>

				<div class="alignleft actions">
				<select name="action">
					<option selected="selected" value=""><?php _e('Bulk Actions','membership'); ?></option>
					<option value="toggle"><?php _e('Toggle activation','membership'); ?></option>

					<optgroup label="<?php _e('Subscriptions','membership'); ?>">
						<option value="bulkaddsub"><?php _e('Add subscription','membership'); ?></option>
						<option value="bulkmovesub"><?php _e('Move subscription','membership'); ?></option>
						<option value="bulkdropsub"><?php _e('Drop subscription','membership'); ?></option>
					</optgroup>

					<optgroup label="<?php _e('Levels','membership'); ?>">
						<option value="bulkaddlevel"><?php _e('Add level','membership'); ?></option>
						<option value="bulkmovelevel"><?php _e('Move level','membership'); ?></option>
						<option value="bulkdroplevel"><?php _e('Drop level','membership'); ?></option>
					</optgroup>

					<optgroup label="<?php _e('Gateways','membership'); ?>">
						<option value="bulkmovegateway"><?php _e('Move gateway','membership'); ?></option>
					</optgroup>
				</select>
				<input type="submit" class="button-secondary action" id="doaction" name="doaction" value="<?php _e('Apply','membership'); ?>" />

				<select name="sub_op" style='float:none;'>
					<option value=""><?php _e('Filter by subscription','membership'); ?></option>
					<?php
						$subs = $this->get_subscriptions();
						if($subs) {
							foreach($subs as $key => $sub) {
								?>
								<option value="<?php echo $sub->id; ?>" <?php if(isset($_GET['sub_op']) && $_GET['sub_op'] == $sub->id) echo 'selected="selected"'; ?>><?php echo esc_html($sub->sub_name); ?></option>
								<?php
							}
						}
					?>
				</select>
				<input type="submit" class="button-secondary action" id="doactionsub" name="doactionsub" value="<?php _e('Filter','membership'); ?>">

				<select name="level_op" style='float:none;'>
					<option value=""><?php _e('Filter by level','membership'); ?></option>
					<?php
						$levels = $this->get_membership_levels();
						if($levels) {
							foreach($levels as $key => $level) {
								?>
								<option value="<?php echo $level->id; ?>" <?php if(isset($_GET['level_op']) && $_GET['level_op'] == $level->id) echo 'selected="selected"'; ?>><?php echo esc_html($level->level_title); ?></option>
								<?php
							}
						}
					?>
				</select>
				<input type="submit" class="button-secondary action" id="doactionlevel" name="doactionlevel" value="<?php _e('Filter','membership'); ?>">

				<select name="active_op" style='float:none;'>
					<option value=""><?php _e('Filter by status','membership'); ?></option>
					<option value="yes" <?php if(isset($_GET['active_op']) && $_GET['active_op'] == 'yes') echo 'selected="selected"'; ?>><?php _e('Active','membership'); ?></option>
					<option value="no" <?php if(isset($_GET['active_op']) && $_GET['active_op'] == 'no') echo 'selected="selected"'; ?>><?php _e('Inactive','membership'); ?></option>
				</select>
				<input type="submit" class="button-secondary action" id="doactionactive" name="doactionactive" value="<?php _e('Filter','membership'); ?>">


				</div>

				<div class="alignright actions">
					<!-- <input type="button" class="button-secondary addnewlevelbutton" value="<?php _e('Add New','membership'); ?>" name="addnewlevel"> -->
				</div>

				<br class="clear">
				</div>
				<?php if ( is_wp_error( $wp_user_search->search_errors ) ) : ?>
					<div class="error">
						<ul>
						<?php
							foreach ( $wp_user_search->search_errors->get_error_messages() as $message )
								echo "<li>$message</li>";
						?>
						</ul>
					</div>
				<?php endif; ?>

				<?php if ( $wp_user_search->is_search() ) : ?>
					<p><a href="?page=<?php echo $page; ?>"><?php _e('&larr; Back to All Users','membership'); ?></a></p>
				<?php endif; ?>

				<div class="clear"></div>

				<?php
					wp_nonce_field('bulk-members');

					$columns = array(	"username" 	=> 	__('Username','membership'),
										"name" 		=> 	__('Name','membership'),
										"email" 	=> 	__('E-mail','membership'),
										"active"	=>	__('Active','membership'),
										"sub"		=>	__('Subscription','membership'),
										"level"		=>	__('Membership Level','membership'),
										"expires"	=>	__('Level Expires', 'membership'),
										"gateway"	=>	__('Gateway', 'membership')
									);

					$columns = apply_filters('members_columns', $columns);

					//$levels = $this->get_membership_levels($filter);

				?>

				<table cellspacing="0" class="widefat fixed">
					<thead>
					<tr>
					<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
					<?php
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
					?>
					</tr>
					</thead>

					<tfoot>
					<tr>
					<th style="" class="manage-column column-cb check-column" scope="col"><input type="checkbox"></th>
					<?php
						reset($columns);
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
					?>
					</tr>
					</tfoot>

					<tbody>
						<?php

						$style = '';
						foreach ( $wp_user_search->get_results() as $userid ) {
							$user_object = new M_Membership($userid);
							$roles = $user_object->roles;
							$role = array_shift($roles);

							$style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
							?>
							<tr id='user-<?php echo $user_object->ID; ?>' <?php echo $style; ?>>
								<th scope='row' class='check-column'>
									<input type='checkbox' name='users[]' id='user_<?php echo $user_object->ID; ?>' class='$role' value='<?php echo $user_object->ID; ?>' />
								</th>
								<td <?php echo $style; ?>>
									<strong><a href='<?php echo admin_url('user-edit.php?user_id=' . $user_object->ID); ?>' title='User ID: <?php echo $user_object->ID;  ?>'><?php echo $user_object->user_login; ?></a></strong>
									<?php
										$actions = array();
										//$actions['id'] = "<strong>" . __('ID : ', 'membership') . $user_object->ID . "</strong>";
										$actions['edit'] = "<span class='edit'><a href='" . admin_url('user-edit.php?user_id=' . $user_object->ID) . "'>" . __('Edit', 'membership') . "</a></span>";
										if($user_object->active_member()) {
											$actions['activate'] = "<span class='edit deactivate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=toggle&amp;member_id=" . $user_object->ID . "", 'toggle-member_' . $user_object->ID) . "'>" . __('Deactivate', 'membership') . "</a></span>";
										} else {
											$actions['activate'] = "<span class='edit activate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=toggle&amp;member_id=" . $user_object->ID . "", 'toggle-member_' . $user_object->ID) . "'>" . __('Activate', 'membership') . "</a></span>";
										}
										//$actions['history'] = "<span class='edit'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=history&amp;member_id=" . $user_object->ID . "", 'history-member_' . $user_object->ID) . "'>" . __('History', 'membership') . "</a></span>";
									?>
									<div class="row-actions"><?php echo implode(" | ", $actions); ?></div>
								</td>
								<td <?php echo $style; ?>><?php echo $user_object->first_name . " " . $user_object->last_name; ?></td>
								<td <?php echo $style; ?>><a href='mailto:<?php echo $user_object->user_email; ?>' title='<?php echo sprintf( __('e-mail: %s','membership' ), $user_object->user_email ); ?>'><?php echo $user_object->user_email; ?></a></td>
								<td <?php echo $style; ?>>
									<?php if($user_object->active_member()) {
										echo "<span class='membershipactivestatus'>" . __('Active', 'membership') . "</span>";
									} else {
										echo "<span class='membershipinactivestatus'>" . __('Inactive', 'membership') . "</span>";
									}
									?>
								</td>
								<td <?php echo $style; ?>>
									<?php
									$subs = $user_object->get_subscription_ids();
									if(!empty($subs)) {
										$rows = array();
										foreach((array) $subs as $key) {
											$sub = new M_Subscription ( $key );
											if(!empty($sub)) {
												$rows[] = $sub->sub_name();
											}
										}
										echo implode(", ", $rows);
									}

									$actions = array();

									if(!$user_object->has_cap('membershipadmin')) {
										$actions['add'] = "<span class='edit'><a href='?page={$page}&amp;action=addsub&amp;member_id={$user_object->ID}'>" . __('Add', 'membership') . "</a></span>";
									}

									if(!empty($subs)) {
										if(count($subs) == 1) {
											$actions['move'] = "<span class='edit'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=movesub&amp;member_id=" . $user_object->ID . "&amp;fromsub=" . $subs[0], 'movesub-member-' . $user_object->ID) . "'>" . __('Move', 'membership') . "</a></span>";
											$actions['drop'] = "<span class='edit delete'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=dropsub&amp;member_id=" . $user_object->ID . "&amp;fromsub=" . $subs[0], 'dropsub-member-' . $user_object->ID) . "'>" . __('Drop', 'membership') . "</a></span>";
										} else {
											$actions['move'] = "<span class='edit'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=movesub&amp;member_id=" . $user_object->ID . "", 'movesub-member-' . $user_object->ID) . "'>" . __('Move', 'membership') . "</a></span>";
											$actions['drop'] = "<span class='edit delete'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=dropsub&amp;member_id=" . $user_object->ID . "", 'dropsub-member-' . $user_object->ID) . "'>" . __('Drop', 'membership') . "</a></span>";
										}
									}

									?>
									<div class="row-actions"><?php echo implode(" | ", $actions); ?></div>
								</td>
								<td <?php echo $style; ?>>
									<?php
									$levels = $user_object->get_level_ids();
									if(!empty($levels)) {
										$rows = array();
										foreach((array) $levels as $key => $value) {
											$level = new M_Level ( $value->level_id );
											if(!empty($level)) {
												if((int) $value->sub_id != 0) {
													$rows[] = "<strong>" . $level->level_title() . "</strong>";
												} else {
													$rows[] = $level->level_title();
												}
											}
										}
										echo implode(", ", $rows);
									}

									$actions = array();
									if(!$user_object->has_cap('membershipadmin')) {
										$actions['add'] = "<span class='edit'><a href='?page={$page}&amp;action=addlevel&amp;member_id={$user_object->ID}'>" . __('Add', 'membership') . "</a></span>";
									}

									if(!empty($levels)) {
										if(count($levels) == 1) {
											$actions['move'] = "<span class='edit'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=movelevel&amp;member_id=" . $user_object->ID . "&amp;fromlevel=" . $levels[0]->level_id, 'movelevel-member-' . $user_object->ID) . "'>" . __('Move', 'membership') . "</a></span>";
											$actions['drop'] = "<span class='edit delete'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=droplevel&amp;member_id=" . $user_object->ID . "&amp;fromlevel=" . $levels[0]->level_id, 'droplevel-member-' . $user_object->ID) . "'>" . __('Drop', 'membership') . "</a></span>";
										} else {
											$actions['move'] = "<span class='edit'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=movelevel&amp;member_id=" . $user_object->ID . "", 'movelevel-member-' . $user_object->ID) . "'>" . __('Move', 'membership') . "</a></span>";
											$actions['drop'] = "<span class='edit delete'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=droplevel&amp;member_id=" . $user_object->ID . "", 'droplevel-member-' . $user_object->ID) . "'>" . __('Drop', 'membership') . "</a></span>";
										}
									}

									?>
									<div class="row-actions"><?php echo implode(" | ", $actions); ?></div>
								</td>
								<td <?php echo $style; ?>>
									<?php
										$subs = $user_object->get_relationships();
										if($subs) {
											$exps = array();
											foreach($subs as $sub) {
												$exps[] = date("Y-m-d H:i", mysql2date("U", $sub->expirydate));
											}
											echo implode(", ", $exps);
										}

									?>
								</td>
								<td <?php echo $style; ?>>
									<?php
										$subs = $user_object->get_relationships();
										//print_r($subs);
										if($subs) {
											$gates = array();
											foreach($subs as $sub) {
												$gates[] = $sub->usinggateway;
											}
											echo implode(", ", $gates);

											if($user_object->has_cap('membershipadmin')) {
												$actions = array();
											} else {
												$actions = array();

												if(count($gates) == 1) {
													$actions['move'] = "<span class='edit'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=movegateway&amp;member_id=" . $user_object->ID . "&amp;fromgateway=" . $gates[0], 'movegateway-member-' . $user_object->ID) . "'>" . __('Move', 'membership') . "</a></span>";
												} else {
													$actions['move'] = "<span class='edit'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=movegateway&amp;member_id=" . $user_object->ID . "", 'movegateway-member-' . $user_object->ID) . "'>" . __('Move', 'membership') . "</a></span>";
												}

											}

											?>
											<div class="row-actions"><?php echo implode(" | ", $actions); ?></div>
											<?php
										}
									?>
								</td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>


				<div class="tablenav">

				<div class="alignleft actions">
				<select name="action2">
					<option selected="selected" value=""><?php _e('Bulk Actions','membership'); ?></option>
					<option value="toggle"><?php _e('Toggle activation','membership'); ?></option>

					<optgroup label="<?php _e('Subscriptions','membership'); ?>">
						<option value="bulkaddsub"><?php _e('Add subscription','membership'); ?></option>
						<option value="bulkmovesub"><?php _e('Move subscription','membership'); ?></option>
						<option value="bulkdropsub"><?php _e('Drop subscription','membership'); ?></option>
					</optgroup>

					<optgroup label="<?php _e('Levels','membership'); ?>">
						<option value="bulkaddlevel"><?php _e('Add level','membership'); ?></option>
						<option value="bulkmovelevel"><?php _e('Move level','membership'); ?></option>
						<option value="bulkdroplevel"><?php _e('Drop level','membership'); ?></option>
					</optgroup>

					<optgroup label="<?php _e('Gateways','membership'); ?>">
						<option value="bulkmovegateway"><?php _e('Move gateway','membership'); ?></option>
					</optgroup>
				</select>
				<input type="submit" class="button-secondary action" id="doaction2" name="doaction2" value="Apply">

				<select name="sub_op2">
					<option value=""><?php _e('Filter by subscription','membership'); ?></option>
					<?php
						$subs = $this->get_subscriptions();
						if($subs) {
							foreach($subs as $key => $sub) {
								?>
								<option value="<?php echo $sub->id; ?>" <?php if(isset($_GET['sub_op2']) && $_GET['sub_op2'] == $sub->id) echo 'selected="selected"'; ?>><?php echo esc_html($sub->sub_name); ?></option>
								<?php
							}
						}
					?>
				</select>
				<input type="submit" class="button-secondary action" id="doactionsub2" name="doactionsub2" value="<?php _e('Filter','membership'); ?>">

				<select name="level_op2">
					<option value=""><?php _e('Filter by level','membership'); ?></option>
					<?php
						$levels = $this->get_membership_levels();
						if($levels) {
							foreach($levels as $key => $level) {
								?>
								<option value="<?php echo $level->id; ?>" <?php if(isset($_GET['level_op2']) && $_GET['level_op2'] == $level->id) echo 'selected="selected"'; ?>><?php echo esc_html($level->level_title); ?></option>
								<?php
							}
						}
					?>
				</select>
				<input type="submit" class="button-secondary action" id="doactionlevel2" name="doactionlevel2" value="<?php _e('Filter','membership'); ?>">

				<select name="active_op2">
					<option value=""><?php _e('Filter by status','membership'); ?></option>
					<option value="yes" <?php if(isset($_GET['active_op2']) && $_GET['active_op2'] == 'yes') echo 'selected="selected"'; ?>><?php _e('Active','membership'); ?></option>
					<option value="no" <?php if(isset($_GET['active_op2']) && $_GET['active_op2'] == 'no') echo 'selected="selected"'; ?>><?php _e('Inactive','membership'); ?></option>
				</select>
				<input type="submit" class="button-secondary action" id="doactionactive2" name="doactionactive2" value="<?php _e('Filter','membership'); ?>">


				</div>
				<div class="alignright actions">

				</div>
				<br class="clear">
				</div>

				</form>

			</div> <!-- wrap -->
			<?php

		}

		function handle_options_panel_updates() {

			global $action, $page, $wp_rewrite;

			wp_reset_vars( array('action', 'page') );

			if($action == 'updateoptions') {

				check_admin_referer('update-membership-options');

				if(isset($_GET['tab'])) {
					$tab = $_GET['tab'];
				} else {
					$tab = 'general';
				}

				if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true) {
					if(function_exists('get_blog_option')) {
						if(function_exists('switch_to_blog')) {
							switch_to_blog(MEMBERSHIP_GLOBAL_MAINSITE);
						}

						$M_options = get_blog_option(MEMBERSHIP_GLOBAL_MAINSITE, 'membership_options', array());
					} else {
						$M_options = get_option('membership_options', array());
					}
				} else {
					$M_options = get_option('membership_options', array());
				}

				switch($tab) {

					case 'general':			$M_options['strangerlevel'] = (isset($_POST['strangerlevel'])) ? (int) $_POST['strangerlevel'] : '';
											$M_options['freeusersubscription'] = (isset($_POST['freeusersubscription'])) ? (int) $_POST['freeusersubscription'] : '';
											$M_options['enableincompletesignups'] = (isset($_POST['enableincompletesignups'])) ? $_POST['enableincompletesignups'] : '';
											break;

					case 'pages':			$M_options['nocontent_page'] = (isset($_POST['nocontent_page'])) ? $_POST['nocontent_page'] : '';
											$M_options['account_page'] = (isset($_POST['account_page'])) ? $_POST['account_page'] : '';
											$M_options['registration_page'] = (isset($_POST['registration_page'])) ? $_POST['registration_page'] : '';
											$M_options['registrationcompleted_page'] = (isset($_POST['registrationcompleted_page'])) ? $_POST['registrationcompleted_page'] : '';
											$M_options['subscriptions_page'] = (isset($_POST['subscriptions_page'])) ? $_POST['subscriptions_page'] : '';
											$M_options['formtype'] = (isset($_POST['formtype'])) ? $_POST['formtype'] : '';
											break;

					case 'posts':			$M_options['membershipshortcodes'] = (isset($_POST['membershipshortcodes'])) ? explode("\n", $_POST['membershipshortcodes']) : array();
											$M_options['membershipadminshortcodes'] = (isset($_POST['membershipadminshortcodes'])) ? explode("\n", $_POST['membershipadminshortcodes']) : array();
											$M_options['shortcodemessage'] = (isset($_POST['shortcodemessage'])) ? $_POST['shortcodemessage'] : '';
											$M_options['moretagdefault'] = (isset($_POST['moretagdefault'])) ? $_POST['moretagdefault'] : '';
											$M_options['moretagmessage'] = (isset($_POST['moretagmessage'])) ? $_POST['moretagmessage'] : '';
											break;

					case 'downloads':		$M_options['original_url'] = (isset($_POST['original_url'])) ? $_POST['original_url'] : '';
											$M_options['masked_url'] = (isset($_POST['masked_url'])) ? $_POST['masked_url'] : '';
											$M_options['membershipdownloadgroups'] = (isset($_POST['membershipdownloadgroups'])) ? explode("\n", $_POST['membershipdownloadgroups']) : array();
											$M_options['protection_type'] = (isset($_POST['protection_type'])) ? $_POST['protection_type'] : '';

											// Refresh the rewrite rules in case they've switched to hybrid from an earlier version
											flush_rewrite_rules();
											break;

					case 'users':			$wp_user_search = new WP_User_Query( array( 'role' => 'administrator' ) );
											$admins = $wp_user_search->get_results();
											$user_id = get_current_user_id();
											foreach($admins as $admin) {
												if($user_id == $admin->ID) {
													continue;
												} else {
													if(in_array( $admin->ID, (array) $_POST['admincheck'])) {
														$user = new WP_User( $admin->ID );
														if(!$user->has_cap('membershipadmin')) {
															$user->add_cap('membershipadmin');
														}
													} else {
														$user = new WP_User( $admin->ID );
														if($user->has_cap('membershipadmin')) {
															$user->remove_cap('membershipadmin');
														}
													}
												}
											}
											break;

					case 'extras':			$M_options['paymentcurrency'] = (isset($_POST['paymentcurrency'])) ? $_POST['paymentcurrency'] : '';
											$M_options['upgradeperiod'] = (isset($_POST['upgradeperiod'])) ? $_POST['upgradeperiod'] : '';
											$M_options['renewalperiod'] = (isset($_POST['renewalperiod'])) ? $_POST['renewalperiod'] : '';

											if(isset($_POST['membershipwizard']) && $_POST['membershipwizard'] == 'yes') {
												if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true) {
													if(function_exists('update_blog_option')) {
														update_blog_option(MEMBERSHIP_GLOBAL_MAINSITE, 'membership_wizard_visible', 'yes');
													} else {
														update_option('membership_wizard_visible', 'yes');
													}
												} else {
													update_option('membership_wizard_visible', 'yes');
												}
											}
											break;

					default:
											break;


				}
				// included an action here so that it is processed for all tabs
				do_action('membership_option_menu_process_' . $tab);

				// For future upgrades
				$M_options['registration_tos'] = (isset($_POST['registration_tos'])) ? $_POST['registration_tos'] : '';

				if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true) {
					if(function_exists('update_blog_option')) {
						update_blog_option(MEMBERSHIP_GLOBAL_MAINSITE, 'membership_options', $M_options);
					} else {
						update_option('membership_options', $M_options);
					}
				} else {
					update_option('membership_options', $M_options);
				}

				do_action( 'membership_options_page_process' );

				// Always flush the rewrite rules
				$wp_rewrite->flush_rules();

				wp_safe_redirect( add_query_arg('msg', 1, wp_get_referer()) );

			} elseif( !empty($action) ) {

				if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true) {
					if(function_exists('get_blog_option')) {
						if(function_exists('switch_to_blog')) {
							switch_to_blog(MEMBERSHIP_GLOBAL_MAINSITE);
						}

						$M_options = get_blog_option(MEMBERSHIP_GLOBAL_MAINSITE, 'membership_options', array());
					} else {
						$M_options = get_option('membership_options', array());
					}
				} else {
					$M_options = get_option('membership_options', array());
				}

				switch($action) {
					case 'createregistrationpage':	check_admin_referer('create-registrationpage');
													$pagedetails = array('post_title' => __('Register', 'membership'), 'post_name' => 'register', 'post_status' => 'publish', 'post_type' => 'page', 'post_content' => '');
													$id = wp_insert_post( $pagedetails );
													$M_options['registration_page'] = $id;
													break;

					case 'createaccountpage':		check_admin_referer('create-accountpage');
													$pagedetails = array('post_title' => __('Account', 'membership'), 'post_name' => 'account', 'post_status' => 'publish', 'post_type' => 'page', 'post_content' => '');
													$id = wp_insert_post( $pagedetails );
													$M_options['account_page'] = $id;
													break;

					case 'createsubscriptionspage':	check_admin_referer('create-subscriptionspage');
													$pagedetails = array('post_title' => __('Subscriptions', 'membership'), 'post_name' => 'subscriptions', 'post_status' => 'publish', 'post_type' => 'page', 'post_content' => '');
													$id = wp_insert_post( $pagedetails );
													$M_options['subscriptions_page'] = $id;
													break;

					case 'createnoaccesspage':		check_admin_referer('create-noaccesspage');
													$content = '<p>' . __('The content you are trying to access is only available to members. Sorry.','membership') . '</p>';
													$pagedetails = array('post_title' => __('Protected Content', 'membership'), 'post_name' => 'protected', 'post_status' => 'publish', 'post_type' => 'page', 'post_content' => $content);
													$id = wp_insert_post( $pagedetails );
													$M_options['nocontent_page'] = $id;
													break;

					case 'createregistrationcompletedpage':
													check_admin_referer('create-registrationcompletedpage');
													$content = '<p>' . __('Thank you for subscribing. We hope you enjoy the content.','membership') . '</p>';
													$pagedetails = array('post_title' => __('Welcome', 'membership'), 'post_name' => 'welcome', 'post_status' => 'publish', 'post_type' => 'page', 'post_content' => $content);
													$id = wp_insert_post( $pagedetails );
													$M_options['registrationcompleted_page'] = $id;
													break;
				}

				if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true) {
					if(function_exists('update_blog_option')) {
						update_blog_option(MEMBERSHIP_GLOBAL_MAINSITE, 'membership_options', $M_options);
					} else {
						update_option('membership_options', $M_options);
					}
				} else {
					update_option('membership_options', $M_options);
				}

				do_action( 'membership_options_pagecreation_process' );

				wp_safe_redirect( add_query_arg('msg', 2, wp_get_referer()) );

			}

		}

		function show_general_options() {
			global $action, $page, $M_options;

			$messages = array();
			$messages[1] = __('Your options have been updated.','membership');

			?>
				<div class="icon32" id="icon-options-general"><br></div>
				<h2><?php _e('General Options','membership'); ?></h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}
				?>
				<div id="poststuff" class="metabox-holder m-settings">
				<form action='' method='post'>

					<input type='hidden' name='page' value='<?php echo $page; ?>' />
					<input type='hidden' name='action' value='updateoptions' />

					<?php
						wp_nonce_field('update-membership-options');
					?>
					<div class="postbox">
						<h3 class="hndle" style='cursor:auto;'><span><?php _e('Stranger settings','membership'); ?></span></h3>
						<div class="inside">
							<p class='description'><?php _e('A &quot;stranger&quot; is a visitor to your website who is either not logged in, or does not have an active membership or subscription to your website.','membership'); ?></p>
							<table class="form-table">
							<tbody>
								<tr valign="top">
									<th scope="row"><?php _e('Use membership level','membership'); ?></th>
									<td>
										<select name='strangerlevel' id='strangerlevel'>
											<option value="0"><?php _e('None - No access to content','membership'); ?></option>
										<?php
											$levels = $this->get_membership_levels();
											if($levels) {
												foreach($levels as $key => $level) {
													?>
													<option value="<?php echo $level->id; ?>" <?php if(isset($M_options['strangerlevel']) && $M_options['strangerlevel'] == $level->id) echo "selected='selected'"; ?>><?php echo esc_html($level->level_title); ?></option>
													<?php
												}
											}
										?>
										</select>
									</td>
								</tr>
							</tbody>
							</table>
							<p class='description'><?php _e('If the above is set to &quot;None&quot; then you can pick the page you want strangers directed to below in the &quot;Membership Pages&quot; options.','membership'); ?></p>

						</div>
					</div>

					<div class="postbox">
						<h3 class="hndle" style='cursor:auto;'><span><?php _e('User registration','membership'); ?></span></h3>
						<div class="inside">
							<p class='description'><?php _e('If you have free user registration enabled on your site, select the subscription they will be assigned to initially.','membership'); ?></p>
							<p class='description'><?php _e('If you are using a paid subscription model - it is probably best to set this to &quot;none&quot;.','membership'); ?></p>

							<table class="form-table">
							<tbody>
								<tr valign="top">
									<th scope="row"><?php _e('Use subscription','membership'); ?></th>
									<td>
										<select name='freeusersubscription' id='freeusersubscription'>
											<option value="0"><?php _e('None','membership'); ?></option>
										<?php
											$subs = $this->get_subscriptions( array('sub_status' => 'active'));
											if($subs) {
												foreach($subs as $key => $sub) {
													?>
													<option value="<?php echo $sub->id; ?>" <?php if(isset($M_options['freeusersubscription']) && $M_options['freeusersubscription'] == $sub->id) echo "selected='selected'"; ?>><?php echo esc_html($sub->sub_name); ?></option>
													<?php
												}
											}
										?>
										</select>
									</td>
								</tr>
							</tbody>
							</table>

							<?php
							/*
							<p class='description'><?php _e('The default setting for the membership plugin is to disable user accounts that do not complete their subscription signup.','membership'); ?></p>
							<p class='description'><?php _e('If you want to change this, then use the option below.','membership'); ?></p>

							<table class="form-table">
							<tbody>
								<tr valign="top">
									<th scope="row"><?php _e('Enable incomplete signup accounts','membership'); ?>
									</em>
									</th>
									<td>
										<?php
											if(!isset($M_options['enableincompletesignups'])) {
												$M_options['enableincompletesignups'] = 'no';
											}
										?>
										<input type='checkbox' name='enableincompletesignups' id='enableincompletesignups' value='yes' <?php checked('yes', $M_options['enableincompletesignups']); ?> />
									</td>
								</tr>
							</tbody>
							</table>
							*/
							?>
						</div>
					</div>

					<?php
						do_action( 'membership_generaloptions_page' );
					?>

					<p class="submit">
						<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes','membership'); ?>" />
					</p>

				</form>
				</div>
			<?php
		}

		function show_page_options() {
			global $action, $page, $M_options;

			$messages = array();
			$messages[1] = __('Your options have been updated.','membership');
			$messages[2] = __('Your page has been created.','membership');

			?>
				<div class="icon32" id="icon-options-general"><br></div>
				<h2><?php _e('Membership Page Options','membership'); ?></h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}
				?>
				<div id="poststuff" class="metabox-holder m-settings">
				<form action='' method='post'>

					<input type='hidden' name='page' value='<?php echo $page; ?>' />
					<input type='hidden' name='action' value='updateoptions' />

					<?php
						wp_nonce_field('update-membership-options');
					?>

					<div class="postbox">
						<h3 class="hndle" style='cursor:auto;'><span><?php _e('Registration page','membership'); ?></span></h3>
						<div class="inside">
							<p class='description'><?php _e('This is the page a new user will be redirected to when they want to register on your site.','membership'); ?></p>
							<p class='description'><?php _e('You can include an introduction on the page, for more advanced content around the registration form then you <strong>should</strong> include the [subscriptionform] shortcode in some location on that page. Alternatively leave the page blank for the standard Membership subscription forms.','membership'); ?></p>

							<table class="form-table">
							<tbody>
								<tr valign="top">
									<th scope="row"><?php _e('Registration page','membership'); ?>
										<?php echo $this->_tips->add_tip( __('Select a page to use for the registration form. If you do not have one already, then click on <strong>Create Page</strong> to make one.','membership') ); ?>
									</th>
									<td>
										<?php
										if(!isset($M_options['registration_page'])) {
											$M_options['registration_page'] = '';
										}
										$pages = wp_dropdown_pages(array('post_type' => 'page', 'selected' => $M_options['registration_page'], 'name' => 'registration_page', 'show_option_none' => __('None', 'membership'), 'sort_column'=> 'menu_order, post_title', 'echo' => 0));
										echo $pages;
										?>
										&nbsp;<a href='<?php echo wp_nonce_url("admin.php?page=" . $page. "&amp;tab=pages&amp;action=createregistrationpage", 'create-registrationpage'); ?>' class='button-primary' title='<?php _e('Create a default page for the registration page and assign it here.', 'membership'); ?>'><?php _e('Create page', 'membership'); ?></a>
										<?php if(!empty($M_options['registration_page'])) { ?>
										<br/>
										<a href='<?php echo get_permalink($M_options['registration_page']); ?>'><?php _e('view page','membership'); ?></a> | <a href='<?php echo admin_url('post.php?post=' . $M_options['registration_page'] . '&action=edit'); ?>'><?php _e('edit page','membership'); ?></a>
										<?php } ?>
									</td>
								</tr>
							</tbody>
							</table>

							<p class='description'><?php _e('There are two forms of registration form available, select the one you would like to use on your site below.','membership'); ?></p>

							<table class="form-table">
							<tbody>
								<tr valign="top">
									<th scope="row"><?php _e('Form type','membership'); ?>
									<?php echo $this->_tips->add_tip( __('Choose between the original multi-page or Pop up registration methods.','membership') ); ?>
									</th>
									<td>
										<select name='formtype' id='formtype'>
											<option value="original" <?php if(isset($M_options['formtype']) && $M_options['formtype'] == 'original') echo "selected='selected'"; ?>><?php _e('Original membership form','membership'); ?></option>
											<option value="new" <?php if(isset($M_options['formtype']) && $M_options['formtype'] == 'new') echo "selected='selected'"; ?>><?php _e('Popup registration form','membership'); ?></option>
										</select>
									</td>
								</tr>
							</tbody>
							</table>
						</div>
					</div>

					<div class="postbox">
						<h3 class="hndle" style='cursor:auto;'><span><?php _e('Registration completed page','membership'); ?></span></h3>
						<div class="inside">
							<p class='description'><?php _e('When a user has signed up for membership and completed any payments required, they will be redirected to this page.','membership'); ?></p>
							<p class='description'><?php _e('You should include a welcome message on this page and some details on what to do next.','membership'); ?></p>

							<table class="form-table">
							<tbody>
								<tr valign="top">
									<th scope="row"><?php _e('Registration completed page','membership'); ?>
									<?php echo $this->_tips->add_tip( __('Select a page to use for the Registration completed page. If you do not have one already, then click on <strong>Create Page</strong> to make one.','membership') ); ?>
									</th>
									<td>
										<?php
										if(!isset($M_options['registrationcompleted_page'])) {
											$M_options['registrationcompleted_page'] = '';
										}
										$pages = wp_dropdown_pages(array('post_type' => 'page', 'selected' => $M_options['registrationcompleted_page'], 'name' => 'registrationcompleted_page', 'show_option_none' => __('Select a page', 'membership'), 'sort_column'=> 'menu_order, post_title', 'echo' => 0));
										echo $pages;
										?>
										&nbsp;<a href='<?php echo wp_nonce_url("admin.php?page=" . $page. "&amp;tab=pages&amp;action=createregistrationcompletedpage", 'create-registrationcompletedpage'); ?>' class='button-primary' title='<?php _e('Create a default page for the registration completed page and assign it here.', 'membership'); ?>'><?php _e('Create page', 'membership'); ?></a>
										<?php if(!empty($M_options['registrationcompleted_page'])) { ?>
										<br/>
										<a href='<?php echo get_permalink($M_options['registrationcompleted_page']); ?>'><?php _e('view page','membership'); ?></a> | <a href='<?php echo admin_url('post.php?post=' . $M_options['registrationcompleted_page'] . '&action=edit'); ?>'><?php _e('edit page','membership'); ?></a>
										<?php } ?>
									</td>
								</tr>
							</tbody>
							</table>
						</div>
					</div>

					<div class="postbox">
						<h3 class="hndle" style='cursor:auto;'><span><?php _e('Account page','membership'); ?></span></h3>
						<div class="inside">
							<p class='description'><?php _e('This is the page a user will be redirected to when they want to view their account or make a payment on their account.','membership'); ?></p>
							<p class='description'><?php _e('It can be left blank to use the standard Membership interface, otherwise it can contain any content you want but <strong>should</strong> contain the [accountform] shortcode in some location.','membership'); ?></p>

							<table class="form-table">
							<tbody>
								<tr valign="top">
									<th scope="row"><?php _e('Account page','membership'); ?>
									<?php echo $this->_tips->add_tip( __('Select a page to use for the account form. If you do not have one already, then click on <strong>Create Page</strong> to make one.','membership') ); ?>
									</th>
									<td>
										<?php
										if(!isset($M_options['account_page'])) {
											$M_options['account_page'] = '';
										}
										$pages = wp_dropdown_pages(array('post_type' => 'page', 'selected' => $M_options['account_page'], 'name' => 'account_page', 'show_option_none' => __('Select a page', 'membership'), 'sort_column'=> 'menu_order, post_title', 'echo' => 0));
										echo $pages;
										?>
										&nbsp;<a href='<?php echo wp_nonce_url("admin.php?page=" . $page. "&amp;tab=pages&amp;action=createaccountpage", 'create-accountpage'); ?>' class='button-primary' title='<?php _e('Create a default page for the account page and assign it here.', 'membership'); ?>'><?php _e('Create page', 'membership'); ?></a>
										<?php if(!empty($M_options['account_page'])) { ?>
										<br/>
										<a href='<?php echo get_permalink($M_options['account_page']); ?>'><?php _e('view page','membership'); ?></a> | <a href='<?php echo admin_url('post.php?post=' . $M_options['account_page'] . '&action=edit'); ?>'><?php _e('edit page','membership'); ?></a>
										<?php } ?>
									</td>
								</tr>
							</tbody>
							</table>
						</div>
					</div>

					<div class="postbox">
						<h3 class="hndle" style='cursor:auto;'><span><?php _e('Subscriptions page','membership'); ?></span></h3>
						<div class="inside">
							<p class='description'><?php _e('This is the page a user will be redirected to when they want to view their subscription details and upgrade / renew them.','membership'); ?></p>
							<p class='description'><?php _e('It can be left blank to use the standard Membership interface, otherwise it can contain any content you want but <strong>should</strong> contain the [renewform] shortcode in some location.','membership'); ?></p>

							<table class="form-table">
							<tbody>
								<tr valign="top">
									<th scope="row"><?php _e('Subscriptions page','membership'); ?>
									<?php echo $this->_tips->add_tip( __('Select a page to use for the upgrade form. If you do not have one already, then click on <strong>Create Page</strong> to make one.','membership') ); ?>
									</th>
									<td>
										<?php
										if(!isset($M_options['subscriptions_page'])) {
											$M_options['subscriptions_page'] = '';
										}
										$pages = wp_dropdown_pages(array('post_type' => 'page', 'selected' => $M_options['subscriptions_page'], 'name' => 'subscriptions_page', 'show_option_none' => __('Select a page', 'membership'), 'sort_column'=> 'menu_order, post_title', 'echo' => 0));
										echo $pages;
										?>
										&nbsp;<a href='<?php echo wp_nonce_url("admin.php?page=" . $page. "&amp;tab=pages&amp;action=createsubscriptionspage", 'create-subscriptionspage'); ?>' class='button-primary' title='<?php _e('Create a default page for the upgrade / renewal page and assign it here.', 'membership'); ?>'><?php _e('Create page', 'membership'); ?></a>
										<?php if(!empty($M_options['subscriptions_page'])) { ?>
										<br/>
										<a href='<?php echo get_permalink($M_options['subscriptions_page']); ?>'><?php _e('view page','membership'); ?></a> | <a href='<?php echo admin_url('post.php?post=' . $M_options['subscriptions_page'] . '&action=edit'); ?>'><?php _e('edit page','membership'); ?></a>
										<?php } ?>
									</td>
								</tr>
							</tbody>
							</table>
						</div>
					</div>

					<div class="postbox">
						<h3 class="hndle" style='cursor:auto;'><span><?php _e('Protected content page','membership'); ?></span></h3>
						<div class="inside">
							<p class='description'><?php _e('If a post / page / content is not available to a user, this is the page that they user will be directed to.','membership'); ?></p>
							<p class='description'><?php _e('This page will only be displayed if the user has tried to access the post / page / content directly or via a link.','membership'); ?></p>

							<table class="form-table">
							<tbody>
								<tr valign="top">
									<th scope="row"><?php _e('Protected content page','membership'); ?>
									<?php echo $this->_tips->add_tip( __('Select a page to use for the Protected Content message. If you do not have one already, then click on <strong>Create Page</strong> to make one.','membership') ); ?>
									</th>
									<td>
										<?php
										if(!isset($M_options['nocontent_page'])) {
											$M_options['nocontent_page'] = '';
										}
										$pages = wp_dropdown_pages(array('post_type' => 'page', 'selected' => $M_options['nocontent_page'], 'name' => 'nocontent_page', 'show_option_none' => __('Select a page', 'membership'), 'sort_column'=> 'menu_order, post_title', 'echo' => 0));
										echo $pages;
										?>
										&nbsp;<a href='<?php echo wp_nonce_url("admin.php?page=" . $page. "&amp;tab=pages&amp;action=createnoaccesspage", 'create-noaccesspage'); ?>' class='button-primary' title='<?php _e('Create a default page for the protected content page and assign it here.', 'membership'); ?>'><?php _e('Create page', 'membership'); ?></a>
										<?php if(!empty($M_options['nocontent_page'])) { ?>
										<br/>
										<a href='<?php echo get_permalink($M_options['nocontent_page']); ?>'><?php _e('view page','membership'); ?></a> | <a href='<?php echo admin_url('post.php?post=' . $M_options['nocontent_page'] . '&action=edit'); ?>'><?php _e('edit page','membership'); ?></a>
										<?php } ?>
									</td>
								</tr>
							</tbody>
							</table>
						</div>
					</div>

					<?php
						do_action( 'membership_pageoptions_page' );
					?>

					<p class="submit">
						<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes','membership'); ?>" />
					</p>

				</form>
				</div>
			<?php
		}

		function show_downloads_options() {
			global $action, $page, $M_options;

			$messages = array();
			$messages[1] = __('Your options have been updated.','membership');

			?>
				<div class="icon32" id="icon-options-general"><br></div>
				<h2><?php _e('Download / Media Options','membership'); ?></h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}
				?>

				<div id="poststuff" class="metabox-holder m-settings">
				<form action='' method='post'>

					<input type='hidden' name='page' value='<?php echo $page; ?>' />
					<input type='hidden' name='action' value='updateoptions' />

					<?php
						wp_nonce_field('update-membership-options');
					?>

					<div class="postbox">
						<h3 class="hndle" style='cursor:auto;'><span><?php _e('Downloads / Media protection','membership'); ?></span></h3>
						<div class="inside">

							<p class='description'><?php _e('Downloads and media files can be protected by remapping their perceived location.','membership'); ?></p>
							<p class='description'><?php _e('Note: If a user determines a files actual location on your server, there is very little we can do to prevent its download, so please be careful about giving out URLs.','membership'); ?></p>

							<table class="form-table">
							<tbody>
								<tr valign="top">
									<th scope="row"><?php _e('Protection method','membership'); ?>
										<?php echo $this->_tips->add_tip( __('The method of protection can be changed depending on your needs. Membership offers three methods, <strong>Basic</strong> masks your media directory but leaves any filenames the same, <strong>Complete</strong> masks the media directory and changes the image filename as well and <strong>Hybrid</strong> is for use if you are using a host or server that has a problem with the system (such as some installs of nginx).','membership') ); ?>
									</th>
									<td>
										<?php if( empty($M_options['protection_type']) ) $M_options['protection_type'] = 'basic'; ?>
										<input type='radio' name='protection_type' value='basic' <?php checked($M_options['protection_type'], 'basic'); ?> />&nbsp;&nbsp;<?php echo __('Basic protection','membership'); ?><br/>
										<input type='radio' name='protection_type' value='complete' <?php checked($M_options['protection_type'], 'complete'); ?>/>&nbsp;&nbsp;<?php echo __('Complete protection','membership'); ?><br/>
										<input type='radio' name='protection_type' value='hybrid' <?php checked($M_options['protection_type'], 'hybrid'); ?>/>&nbsp;&nbsp;<?php echo __('Hybrid protection','membership'); ?><br/>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e('Your uploads location','membership'); ?>
										<?php echo $this->_tips->add_tip( __('This is where membership thinks you have your images stored, if this is not correct then download protection may not work correctly.','membership') ); ?>
									</th>
									<td>
										<?php echo membership_upload_url();  ?>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e('Masked download URL','membership'); ?>
										<?php echo $this->_tips->add_tip( __('This is the URL that the user will see. You can change the end part to something unique.','membership') ); ?>
									</th>
									<td>
										<?php
											if(!isset($M_options['masked_url'])) {
												$M_options['masked_url'] = '';
											}
											esc_html_e(trailingslashit(get_option('home')));  ?>&nbsp;<input type='text' name='masked_url' id='masked_url' value='<?php esc_attr_e($M_options['masked_url']);
										?>' />&nbsp;/
									</td>
								</tr>

								<tr valign="top">
									<th scope="row"><?php _e('Protected groups','membership'); ?>
									<?php echo $this->_tips->add_tip( __('Place each download group name on a new line, removing used groups will leave content visible to all users/members.','membership') ); ?>
									</th>
									<td>
										<textarea name='membershipdownloadgroups' id='membershipdownloadgroups' rows='10' cols='40'><?php
										if(!empty($M_options['membershipdownloadgroups'])) {
											foreach($M_options['membershipdownloadgroups'] as $key => $value) {
												if(!empty($value)) {
													esc_html_e(stripslashes($value)) . "\n";
												}
											}
										}
										?></textarea>
									</td>
								</tr>

							</tbody>
							</table>
						</div>
					</div>

					<?php
						do_action( 'membership_downloadsoptions_page' );
					?>

					<p class="submit">
						<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes','membership'); ?>" />
					</p>

				</form>
				</div>
			<?php
		}

		function show_posts_options() {
			global $action, $page, $M_options;

			$messages = array();
			$messages[1] = __('Your options have been updated.','membership');

			?>
				<div class="icon32" id="icon-options-general"><br></div>
				<h2><?php _e('Content Protection Options','membership'); ?></h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}
				?>

				<div id="poststuff" class="metabox-holder m-settings">
				<form action='' method='post'>

					<input type='hidden' name='page' value='<?php echo $page; ?>' />
					<input type='hidden' name='action' value='updateoptions' />

					<?php
						wp_nonce_field('update-membership-options');
					?>
					<div class="postbox">
						<h3 class="hndle" style='cursor:auto;'><span><?php _e('Shortcode protected content','membership'); ?></span></h3>
						<div class="inside">
							<p class='description'><?php _e('You can protect parts of a post or pages content by enclosing it in WordPress shortcodes.','membership'); ?></p>
							<p class='description'><?php _e("Each level you create has it's own shortcode.",'membership'); ?></p>

							<table class="form-table">
							<tbody>
								<?php if(!empty($M_options['membershipshortcodes'])) { ?>
								<tr valign="top">
									<th scope="row"><?php _e('Legacy Shortcodes','membership'); ?>
										<?php echo $this->_tips->add_tip( __('Each shortcode can be used to wrap protected content such as [shortcode] Protected content [/shortcode]','membership') ); ?>
									</th>
									<td>
										<?php
										$written = false;
										if(!empty($M_options['membershipshortcodes'])) {
											?>
											<input name='membershipshortcodes' type='hidden' value='<?php
											foreach($M_options['membershipshortcodes'] as $key => $value) {
												if(!empty($value)) {
													$written = true;
													echo esc_html(stripslashes($value)) . "\n";
												}
											}
											?>' />
											<?php
											if($written == true) {
												foreach($M_options['membershipshortcodes'] as $key => $value) {
													if(!empty($value)) {
														echo "[" . esc_html(stripslashes($value)) . "]<br/>";
													}
												}
											}
											// Bring in the level based shortcodes to the list here
											$shortcodes = apply_filters('membership_level_shortcodes', array() );
											if(!empty($shortcodes)) {
												foreach($shortcodes as $key => $value) {
													if(!empty($value)) {
														$written = true;
														echo "[" . esc_html(stripslashes($value)) . "]<br/>";
													}
												}
											}

										}

										if($written == false) {
											echo __('No shortcodes available.','membership');
										}
										?>
									</td>
								</tr>
								<?php } ?>
								<tr valign="top">
									<th scope="row"><?php _e('Protected content message','membership'); ?>
									<?php echo $this->_tips->add_tip( __("This is the message that is displayed when the content protected by the shortcode can't be shown. Leave blank for no message. HTML allowed.",'membership') ); ?>
									</th>
									<td>
										<?php
										$args = array("textarea_name" => "shortcodemessage");
										if(!isset($M_options['shortcodemessage'])) {
											$M_options['shortcodemessage'] = '';
										}
										wp_editor( __(stripslashes($M_options['shortcodemessage']), 'membership'), "shortcodemessage", $args );
										/*
										?>
										<textarea name='shortcodemessage' id='shortcodemessage' rows='10' cols='80'><?php esc_html_e(stripslashes($M_options['shortcodemessage'])); ?></textarea>
										<?php
										*/
										?>
									</td>
								</tr>
							</tbody>
							</table>
						</div>
					</div>

					<div class="postbox">
						<h3 class="hndle" style='cursor:auto;'><span><?php _e('Admin only shortcodes','membership'); ?></span></h3>
						<div class="inside">
							<p class='description'><?php _e('Sometimes plugins create custom shortcodes but only register them in the public part of your site. This means that the Membership plugin admin interface will not be able to show them in the Shortcode rule.','membership'); ?></p>
							<p class='description'><?php _e('If you find that a shortcode you want to protect is missing from the shortcode rule, then you can add it here.','membership'); ?></p>

							<table class="form-table">
							<tbody>
								<tr valign="top">
									<th scope="row"><?php _e('Admin Only Shortcodes','membership'); ?>
										<?php echo $this->_tips->add_tip( __("Place each shortcode text (without the square brackets) on a new line, removing used shortcodes could leave content visible to all users/members.",'membership') ); ?>
									</th>
									<td>
										<textarea name='membershipadminshortcodes' id='membershipadminshortcodes' rows='10' cols='40'><?php
										if(!empty($M_options['membershipadminshortcodes'])) {
											foreach($M_options['membershipadminshortcodes'] as $key => $value) {
												if(!empty($value)) {
													esc_html_e(stripslashes($value)) . "\n";
												}
											}
										}
										?></textarea>
									</td>
								</tr>
							</tbody>
							</table>
						</div>
					</div>

					<div class="postbox">
						<h3 class="hndle" style='cursor:auto;'><span><?php _e('More tag default','membership'); ?></span></h3>
						<div class="inside">
							<p class='description'><?php _e('Content placed after the More tag in a post or page can be protected by setting the visibility below. This setting can be overridden within each individual level.','membership'); ?></p>

							<table class="form-table">
							<tbody>
								<tr valign="top">
									<th scope="row"><?php _e('Show content after the More tag','membership'); ?></th>
									<td>
										<select name='moretagdefault' id='moretagdefault'>
											<option value="yes" <?php if(isset($M_options['moretagdefault']) && $M_options['moretagdefault'] == 'yes') echo "selected='selected'"; ?>><?php _e('Yes - More tag content is visible','membership'); ?></option>
											<option value="no" <?php if(isset($M_options['moretagdefault']) && $M_options['moretagdefault'] == 'no') echo "selected='selected'"; ?>><?php _e('No - More tag content not visible','membership'); ?></option>
										</select>
									</td>
								</tr>

								<tr valign="top">
									<th scope="row"><?php _e('No access message','membership'); ?>
									<?php echo $this->_tips->add_tip( __("This is the message that is displayed when the content protected by the moretag can't be shown. Leave blank for no message. HTML allowed.",'membership') ); ?>
									</th>
									<td>
										<?php
										$args = array("textarea_name" => "moretagmessage");
										if(!isset($M_options['moretagmessage'])) {
											$M_options['moretagmessage'] = '';
										}
										wp_editor( __(stripslashes($M_options['moretagmessage']), 'membership'), "moretagmessage", $args );
										/*
										?>
										<textarea name='moretagmessage' id='moretagmessage' rows='5' cols='40'><?php esc_html_e(stripslashes($M_options['moretagmessage'])); ?></textarea>
										<?php
										*/
										?>
									</td>
								</tr>
							</tbody>
							</table>
						</div>
					</div>

					<?php
						do_action( 'membership_postoptions_page' );
					?>

					<p class="submit">
						<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes','membership'); ?>" />
					</p>

				</form>
				</div>
			<?php
		}

		function show_extras_options() {
			global $action, $page, $M_options;

			$messages = array();
			$messages[1] = __('Your options have been updated.','membership');

			?>
				<div class="icon32" id="icon-options-general"><br></div>
				<h2><?php _e('Extra Options','membership'); ?></h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}
				?>
				<div id="poststuff" class="metabox-holder m-settings">
				<form action='' method='post'>

					<input type='hidden' name='page' value='<?php echo $page; ?>' />
					<input type='hidden' name='action' value='updateoptions' />

					<?php
						wp_nonce_field('update-membership-options');
					?>
					<div class="postbox">
						<h3 class="hndle" style='cursor:auto;'><span><?php _e('Payments currency','membership'); ?></span></h3>
						<div class="inside">
							<p class='description'><?php _e('This is the currency that will be used across all gateways. Note: Some gateways have a limited number of currencies available.','membership'); ?></p>

							<table class="form-table">
							<tbody>
								<tr valign="top">
									<th scope="row"><?php _e('Payment currencys','membership'); ?></th>
									<td>
										<select name="paymentcurrency">
										  <?php
										  	$currency = $M_options['paymentcurrency'];
										    $sel_currency = empty($currency) ? 'USD' : $currency;
										    $currencies = array(
										          'AUD' => __('AUD - Australian Dollar','membership'),
										          'BRL' => __('BRL - Brazilian Real','membership'),
										          'CAD' => __('CAD - Canadian Dollar','membership'),
										          'CHF' => __('CHF - Swiss Franc','membership'),
										          'CZK' => __('CZK - Czech Koruna','membership'),
										          'DKK' => __('DKK - Danish Krone','membership'),
										          'EUR' => __('EUR - Euro','membership'),
										          'GBP' => __('GBP - Pound Sterling','membership'),
										          'HKD' => __('HKD - Hong Kong Dollar','membership'),
										          'HUF' => __('HUF - Hungarian Forint','membership'),
												  'ILS' => __('ILS - Israeli Shekel','membership'),
										          'JPY' => __('JPY - Japanese Yen','membership'),
										          'MYR' => __('MYR - Malaysian Ringgits','membership'),
										          'MXN' => __('MXN - Mexican Peso','membership'),
										          'NOK' => __('NOK - Norwegian Krone','membership'),
										          'NZD' => __('NZD - New Zealand Dollar','membership'),
										          'PHP' => __('PHP - Philippine Pesos','membership'),
										          'PLN' => __('PLN - Polish Zloty','membership'),
										          'SEK' => __('SEK - Swedish Krona','membership'),
										          'SGD' => __('SGD - Singapore Dollar','membership'),
										          'TWD' => __('TWD - Taiwan New Dollars','membership'),
										          'THB' => __('THB - Thai Baht','membership'),
										          'USD' => __('USD - U.S. Dollar','membership'),
												  'ZAR' => __('ZAR - South African Rand')
										      );

												$currencies = apply_filters('membership_available_currencies', $currencies);

										      foreach ($currencies as $key => $value) {
													echo '<option value="' . esc_attr($key) . '"';
													if($key == $sel_currency) echo 'selected="selected"';
													echo '>' . esc_html($value) . '</option>' . "\n";
										      }
										  ?>
										  </select>
									</td>
								</tr>
							</tbody>
							</table>
						</div>
					</div>

					<div class="postbox">
						<h3 class="hndle" style='cursor:auto;'><span><?php _e('Membership renewal','membership'); ?></span></h3>
						<div class="inside">
							<p class='description'><?php _e('If you are using single payment gateways, then you should set the number of days before expiry that the renewal form is displayed on the Account page.','membership'); ?></p>


							<table class="form-table">
							<tbody>
								<tr valign="top">
									<th scope="row"><?php _e('Renewal period limit','membership'); ?></th>
									<td>
										<select name="renewalperiod">
										  <?php
										  	$renewalperiod = $M_options['renewalperiod'];

										      for($n=1; $n <= 365; $n++) {
													echo '<option value="' . esc_attr($n) . '"';
													if($n == $renewalperiod) echo 'selected="selected"';
													echo '>' . esc_html($n) . '</option>' . "\n";
										      }
										  ?>
										  </select>&nbsp;<?php _e('day(s)','membership'); ?>
									</td>
								</tr>
							</tbody>
							</table>
						</div>
					</div>

					<div class="postbox">
						<h3 class="hndle" style='cursor:auto;'><span><?php _e('Membership upgrades','membership'); ?></span></h3>
						<div class="inside">
							<p class='description'><?php _e('You should limit the amount of time allowed between membership upgrades in order to prevent members abusing the upgrade process.','membership'); ?></p>


							<table class="form-table">
							<tbody>
								<tr valign="top">
									<th scope="row"><?php _e('Upgrades period limit','membership'); ?></th>
									<td>
										<select name="upgradeperiod">
										  <?php
										  	$upgradeperiod = $M_options['upgradeperiod'];
											// Set a default of 1 day, but allow the selection of 0 days
											if(empty($upgradeperiod) && $upgradeperiod != 0) {
												$upgradeperiod = 1;
											}

										      for($n=0; $n <= 365; $n++) {
													echo '<option value="' . esc_attr($n) . '"';
													if($n == $upgradeperiod) echo 'selected="selected"';
													echo '>' . esc_html($n) . '</option>' . "\n";
										      }
										  ?>
										  </select>&nbsp;<?php _e('day(s)','membership'); ?>
									</td>
								</tr>
							</tbody>
							</table>
						</div>
					</div>

					<div class="postbox">
						<h3 class="hndle" style='cursor:auto;'><span><?php _e('Membership wizard','membership'); ?></span></h3>
						<div class="inside">
							<p class='description'><?php _e('If you accidentally dismissed the membership wizard and would like to show it again, then check the box below.','membership'); ?></p>


							<table class="form-table">
							<tbody>
								<tr valign="top">
									<th scope="row"><?php _e('Show membership wizard','membership'); ?></th>
									<td>
										<?php
										if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true) {
											if(function_exists('get_blog_option')) {
												if(function_exists('switch_to_blog')) {
													switch_to_blog(MEMBERSHIP_GLOBAL_MAINSITE);
												}
												$wizard_visible = get_blog_option(MEMBERSHIP_GLOBAL_MAINSITE, 'membership_wizard_visible', 'yes');
												if(function_exists('restore_current_blog')) {
													restore_current_blog();
												}
											} else {
												$wizard_visible = get_option('membership_wizard_visible', 'yes');
											}
										} else {
											$wizard_visible = get_option('membership_wizard_visible', 'yes');
										}
										?>
										<input type='checkbox' name='membershipwizard' value='yes' <?php if($wizard_visible == 'yes') echo "checked='checked'"; ?>/>
									</td>
								</tr>
							</tbody>
							</table>
						</div>
					</div>

					<?php
						do_action( 'membership_extrasoptions_page' );
					?>

					<p class="submit">
						<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes','membership'); ?>" />
					</p>

				</form>
				</div>
			<?php
		}

		function show_users_options() {
			global $action, $page, $M_options;

			$messages = array();
			$messages[1] = __('Membership admins have been updated.','membership');

			?>
				<div class="icon32" id="icon-options-general"><br></div>
				<h2><?php _e('Membership Admin Users','membership'); ?></h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}
				?>
				<div id="poststuff" class="metabox-holder m-settings">
				<form action='' method='post'>

					<input type='hidden' name='page' value='<?php echo $page; ?>' />
					<input type='hidden' name='action' value='updateoptions' />

					<?php
						wp_nonce_field('update-membership-options');
					?>
					<div class="postbox">
						<h3 class="hndle" style='cursor:auto;'><span><?php _e('Membership Admin Users','membership'); ?></span></h3>
						<div class="inside">
							<p class='description'><?php _e('You can add or remove the ability for specific admin user accounts to manage the Membership plugin by checking or unchecking the boxes next to the relevant username.','membership'); ?></p>

							<?php
								$columns = array(	"name" 		=> 	__('User Login','membership')
												);

								$columns = apply_filters('membership_adminuserscolumns', $columns);

								$wp_user_search = new WP_User_Query( array( 'role' => 'administrator' ) );
								$admins = $wp_user_search->get_results();

							?>

							<table cellspacing="0" class="widefat fixed">
								<thead>
								<tr>
								<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
								<?php
									foreach($columns as $key => $col) {
										?>
										<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
										<?php
									}
								?>
								</tr>
								</thead>

								<tfoot>
								<tr>
								<th style="" class="manage-column column-cb check-column" scope="col"><input type="checkbox"></th>
								<?php
									reset($columns);
									foreach($columns as $key => $col) {
										?>
										<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
										<?php
									}
								?>
								</tr>
								</tfoot>

								<tbody>
									<?php
									if(!empty($admins)) {
										$user_id = get_current_user_id();
										foreach($admins as $key => $admin) {
											?>
											<tr valign="middle" class="alternate" id="admin-<?php echo $admin->ID; ?>">
												<th class="check-column" scope="row">
													<?php if($user_id != $admin->ID) {
															$user = new WP_User( $admin->ID );
															if($user->has_cap('membershipadmin')) {
															?>
															<input type="checkbox" value="<?php echo esc_attr($admin->ID); ?>" name="admincheck[]" checked='checked'>
															<?php
															} else {
															?>
															<input type="checkbox" value="<?php echo esc_attr($admin->ID); ?>" name="admincheck[]" >
															<?php
															}
														 } ?>
												</th>
												<td class="column-name">
													<strong><?php echo esc_html(stripslashes($admin->user_login)); ?></strong><br/>
													<?php
													if($user_id == $admin->ID) {
														_e('You can not remove your own permissions to manage the membership system whilst logged in.', 'membership');
													}
													?>
												</td>
										    </tr>
											<?php
										}
									} else {
										$columncount = count($columns) + 1;
										?>
										<tr valign="middle" class="alternate" >
											<td colspan="<?php echo $columncount; ?>" scope="row"><?php _e('There are no Admin users - something may have gone wrong.','membership'); ?></td>
									    </tr>
										<?php
									}
									?>

								</tbody>
							</table>

						</div>
					</div>

					<?php
						do_action( 'membership_adminusersoptions_page' );
					?>

					<p class="submit">
						<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes','membership'); ?>" />
					</p>

				</form>
				</div>
			<?php
		}

		function handle_options_panel() {

			global $action, $page, $M_options;

			wp_reset_vars( array('action', 'page') );

			if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true) {
				if(function_exists('get_blog_option')) {
					if(function_exists('switch_to_blog')) {
						switch_to_blog(MEMBERSHIP_GLOBAL_MAINSITE);
					}

					$M_options = get_blog_option(MEMBERSHIP_GLOBAL_MAINSITE, 'membership_options', array());
				} else {
					$M_options = get_option('membership_options', array());
				}
			} else {
				$M_options = get_option('membership_options', array());
			}

			$tab = (isset($_GET['tab'])) ? $_GET['tab'] : '';
			if(empty($tab)) {
				$tab = 'general';
			}

			?>
			<div class='wrap nosubsub'>
				<?php
					$menus = array();
					$menus['general'] = __('General', 'membership');
					$menus['pages'] = __('Membership Pages', 'membership');
					$menus['posts'] = __('Content Protection', 'membership');
					$menus['downloads'] = __('Downloads / Media', 'membership');
					$menus['users'] = __('Membership Admins','membership');
					$menus['extras'] = __('Extras', 'membership');

					$menus = apply_filters('membership_options_menus', $menus);
				?>

				<?php
						if ( current_user_can('manage_options') && !get_option('permalink_structure') ) {
					      echo '<div class="error"><p>'.__('You must enable Pretty Permalinks for Membership to function correctly - <a href="options-permalink.php">Enable now &raquo;</a>', 'membership').'</p></div>';
						}

				?>

				<h3 class="nav-tab-wrapper">
					<?php
						foreach($menus as $key => $menu) {
							?>
							<a class="nav-tab<?php if($tab == $key) echo ' nav-tab-active'; ?>" href="admin.php?page=<?php echo $page; ?>&amp;tab=<?php echo $key; ?>"><?php echo $menu; ?></a>
							<?php
						}

					?>
				</h3>

				<?php

				switch($tab) {

					case 'general':			$this->show_general_options();
											break;

					case 'pages':			$this->show_page_options();
											break;

					case 'posts':			$this->show_posts_options();
											break;

					case 'downloads':		$this->show_downloads_options();
											break;

					case 'extras':			$this->show_extras_options();
											break;

					case 'users':			$this->show_users_options();
											break;

					default:				do_action('membership_option_menu_' . $tab);
											break;


				}

				if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true) {
					if(function_exists('restore_current_blog')) {
						restore_current_blog();
					}
				}

				?>

			</div> <!-- wrap -->
			<?php
		}

		function default_membership_sections($sections) {

			$sections['main'] = array(	"title" => __('Main rules','membership') );

			$sections['content'] = array(	"title" => __('Content rules','membership') );

			return $sections;
		}

		function handle_level_edit_form($level_id = false, $clone = false) {

			global $page, $M_Rules, $M_SectionRules;

			if($level_id && !$clone) {
				$mlevel = new M_Level( $level_id );
				$level = $mlevel->get();
			} else {

				if($clone) {
					$mlevel = new M_Level( $level_id );
					$level = $mlevel->get();

					$level->level_title .= __(' clone','membership');
				} else {
					$level = new stdclass;
					$level->level_title = __('new level','membership');
				}
				$level->id = time() * -1;

			}

			// Get the relevant parts
			if(isset($mlevel)) {
				$positives = $mlevel->get_rules('positive');
				$negatives = $mlevel->get_rules('negative');
			}

			// Re-arrange the rules
			$rules = array(); $p = array(); $n = array();
			if(!empty($positives)) {
				foreach($positives as $positive) {
					$rules[$positive->rule_area] = maybe_unserialize($positive->rule_value);
					$p[$positive->rule_area] = maybe_unserialize($positive->rule_value);
				}
			}
			if(!empty($negatives)) {
				foreach($negatives as $negative) {
					$rules[$negative->rule_area] = maybe_unserialize($negative->rule_value);
					$n[$negative->rule_area] = maybe_unserialize($negative->rule_value);
				}
			}

			// Check which tab we should open the edit form with
			if(!empty($p) && !empty($n)) {
				// We have content in both areas - so start with advanced open
				$advancedtab = 'activetab';
				$negativetab = '';
				$positivetab = '';

				$advancedcontent = 'activecontent';
				$negativecontent = 'activecontent';
				$positivecontent = 'activecontent';
			} else {
				if(!empty($n)) {
					// We have content in the negative area - so start with that
					$advancedtab = '';
					$negativetab = 'activetab';
					$positivetab = '';

					$advancedcontent = 'inactivecontent';
					$negativecontent = 'activecontent';
					$positivecontent = 'inactivecontent';
				} else {
					// Default to the positive area
					$advancedtab = '';
					$negativetab = '';
					$positivetab = 'activetab';

					$advancedcontent = 'inactivecontent';
					$negativecontent = 'inactivecontent';
					$positivecontent = 'activecontent';
				}
			}

			?>
			<div class='wrap nosubsub'>
				<div class="icon32" id="icon-link-manager"><br></div>
				<h2><?php echo __('Edit ','membership') . " - " . esc_html($level->level_title); ?></h2>

				<?php
				if ( isset($usemsg) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[$usemsg] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}
				?>

				<div class='level-liquid-left'>

					<div id='level-left'>
						<form action='?page=<?php echo $page; ?>' name='leveledit' method='post'>
							<input type='hidden' name='level_id' id='level_id' value='<?php echo $level->id; ?>' />

							<input type='hidden' name='ontab' id='ontab' value='positive' />

							<input type='hidden' name='beingdragged' id='beingdragged' value='' />
							<input type='hidden' name='in-positive-rules' id='in-positive-rules' value=',<?php echo implode(',', array_keys($p)); ?>' />
							<input type='hidden' name='in-negative-rules' id='in-negative-rules' value=',<?php echo implode(',', array_keys($n)); ?>' />

							<input type='hidden' name='postive-rules-order' id='postive-rules-order' value='' />
							<input type='hidden' name='negative-rules-order' id='negative-rules-order' value='' />

						<div id='edit-level' class='level-holder-wrap'>
							<div class='sidebar-name no-movecursor'>
								<h3><?php echo esc_html($level->level_title); ?></h3>
							</div>
							<div class='level-holder'>
								<div class='level-details'>
								<label for='level_title'><?php _e('Level title','membership'); ?></label><?php //echo $this->_tips->add_tip( __('This is the title used throughout the system to identify this level.','membership') ); ?><br/>
								<input class='wide' type='text' name='level_title' id='level_title' value='<?php echo esc_attr($level->level_title); ?>' />
								<br/><br/>
								<label for='level_shortcode'><?php _e('Level shortcode','membership'); ?></label><?php echo $this->_tips->add_tip( __('The shortcode for this level is based on the title (above). It can be used to wrap content that you only want to be seen by members on this level e.g. [levelshortcode] protected content [/levelshortcode]','membership') ); ?>
								<?php if($level->id > 0) {
									echo "[" . M_normalize_shortcode($level->level_title) . "]";
								} else {
									_e('Save your level to create the shortcode', 'membership');
								} ?>
								</div>

								<?php do_action('membership_level_form_before_rules', $level->id); ?>

								<ul class='leveltabs'>
									<li class='positivetab <?php echo $positivetab; ?>'><div class='downarrow'></div><a href='#positive'><div><?php _e('Positive Rules', 'membership'); ?></div></a></li>
									<li class='negativetab <?php echo $negativetab; ?>'><div class='downarrow'></div><a href='#negative'><div><?php _e('Negative Rules', 'membership'); ?></div></a></li>
									<li class='advancedtab <?php echo $advancedtab; ?>'><div class='downarrow'></div><a href='#advanced'><div><?php _e('Advanced (both)', 'membership'); ?></div></a></li>
								</ul>

								<div class='advancedtabwarning <?php echo $advancedcontent; ?>'>
									<?php _e('<strong>Warning:</strong> using both positive and negative rules on the same level can cause conflicts and unpredictable behaviour.','membership'); ?>
								</div>

								<div class='positivecontent <?php echo $positivecontent; ?>'>
									<h3 class='positive positivetitle <?php echo $advancedcontent; ?>'><?php _e('Positive rules','membership'); ?></h3>
									<p class='description'><?php _e('These are the areas / elements that a member of this level can access.','membership'); ?></p>

									<div id='positive-rules' class='level-droppable-rules levels-sortable'>
										<?php _e('Drop here','membership'); ?>
									</div>

									<div id='positive-rules-holder'>
										<?php do_action('membership_level_form_before_positive_rules', $level->id); ?>
										<?php
											if(!empty($p)) {
												foreach($p as $key => $value) {

													if(isset($M_Rules[$key])) {
															$rule = new $M_Rules[$key]();

															$rule->admin_main($value);
													}
												}
											}
										?>
										<?php do_action('membership_level_form_after_positive_rules', $level->id); ?>
									</div>
								</div>

								<div class='negativecontent <?php echo $negativecontent; ?>'>
									<h3 class='negative negativetitle <?php echo $advancedcontent; ?>'><?php _e('Negative rules','membership'); ?></h3>
									<p class='description'><?php _e('These are the areas / elements that a member of this level doesn\'t have access to.','membership'); ?></p>

									<div id='negative-rules' class='level-droppable-rules levels-sortable'>
										<?php _e('Drop here','membership'); ?>
									</div>

									<div id='negative-rules-holder'>
										<?php do_action('membership_level_form_before_negative_rules', $level->id); ?>

										<?php
											if(!empty($n)) {
												foreach($n as $key => $value) {
													if(isset($M_Rules[$key])) {
															$rule = new $M_Rules[$key]();

															$rule->admin_main($value);
													}
												}
											}
										?>

										<?php do_action('membership_level_form_after_negative_rules', $level->id); ?>

									</div>
								</div>

								<div class='advancedcontent <?php echo $advancedcontent; ?>'>
									<h3><?php _e('Custom shortcode protected content message','membership'); ?></h3>
									<p class='description'><?php _e('If you want a protected content message to be displayed for this level then you can enter it here.','membership'); ?></p>
									<?php
									$args = array("textarea_name" => "level_protectedcontent", "textarea_rows" => 20);
									if(!empty($mlevel)) {
										$level_protectedcontent = $mlevel->get_meta( 'level_protectedcontent' );
									}
									if(empty($level_protectedcontent)) {
										$level_protectedcontent = '';
									}
									wp_editor( stripslashes($level_protectedcontent), "level_protectedcontent", $args );
									?>
								</div>

								<div class='advancedcontent <?php echo $advancedcontent; ?>'>
								<?php do_action('membership_level_form_after_rules', $level->id); ?>
								</div>

								<div class='buttons'>
									<?php
									if($level->id > 0) {
										wp_original_referer_field(true, 'previous'); wp_nonce_field('update-' . $level->id);
										?>
										<a href='?page=<?php echo $page; ?>' class='cancellink' title='Cancel edit'><?php _e('Cancel', 'membership'); ?></a>
										<input type='submit' value='<?php _e('Update', 'membership'); ?>' class='button-primary' />
										<input type='hidden' name='action' value='updated' />
										<?php
									} else {
										wp_original_referer_field(true, 'previous'); wp_nonce_field('add-' . $level->id);
										?>
										<a href='?page=<?php echo $page; ?>' class='cancellink' title='Cancel add'><?php _e('Cancel', 'membership'); ?></a>
										<input type='submit' value='<?php _e('Add', 'membership'); ?>' class='button-primary' />
										<input type='hidden' name='action' value='added' />
										<?php
									}
									?>
								</div>

							</div>
						</div>
						</form>
					</div>


					<div id='hiden-actions'>
					<?php

						$sections = apply_filters('membership_level_sections', array());

						foreach($sections as $key => $section) {

							if(isset($M_SectionRules[$key])) {
								foreach($M_SectionRules[$key] as $mrule => $mclass) {
									$rule = new $mclass();

									if(!array_key_exists($mrule, $rules)) {
										$rule->admin_main(false);
									}
								}
							}

						}

					?>
					</div> <!-- hidden-actions -->

				</div> <!-- level-liquid-left -->

				<div class='level-liquid-right'>
					<div class="level-holder-wrap">
						<?php

							do_action( 'membership_sidebar_top_level' );
							do_action( 'membership_sidebar_top', 'level' );

							$sections = apply_filters('membership_level_sections', array());

							foreach($sections as $key => $section) {
								?>

								<div class="sidebar-name no-movecursor">
									<h3><?php echo $section['title']; ?></h3>
								</div>
								<div class="section-holder" id="sidebar-<?php echo $key; ?>" style="min-height: 98px;">
									<ul class='levels level-levels-draggable'>
									<?php

										if(isset($M_SectionRules[$key])) {
											foreach($M_SectionRules[$key] as $mrule => $mclass) {
												$rule = new $mclass();

												if(!array_key_exists($mrule, $rules)) {
													$rule->admin_sidebar(false);
												} else {
													$rule->admin_sidebar(true);
												}
											}
										}

									?>
									</ul>
								</div>
								<?php
							}
						?>
					</div> <!-- level-holder-wrap -->

				</div> <!-- level-liquid-left -->

			</div> <!-- wrap -->

			<?php
		}

		function handle_levels_updates() {

			global $action, $page;

			wp_reset_vars( array('action', 'page') );

			if(isset($_GET['doaction']) || isset($_GET['doaction2'])) {
				if(addslashes($_GET['action']) == 'delete' || addslashes($_GET['action2']) == 'delete') {
					$action = 'bulk-delete';
				}

				if(addslashes($_GET['action']) == 'toggle' || addslashes($_GET['action2']) == 'toggle') {
					$action = 'bulk-toggle';
				}
			}

			switch(addslashes($action)) {

				case 'removeheader':	$this->dismiss_user_help( $page );
										wp_safe_redirect( remove_query_arg( 'action' ) );
										break;

				case 'added':	$levels = $levels = $this->get_membership_levels();
								if(count($levels) >= 3) {
									wp_die( __( 'Cheatin&#8217; uh?' ) );
								} else {
									$id = (int) $_POST['level_id'];
									check_admin_referer('add-' . $id);
									if($id) {

										$level = new M_Level($id);

										if($level->add()) {
											// Add in the meta information
											if(!empty($_POST['level_protectedcontent'])) {
												$level->update_meta( 'level_protectedcontent', $_POST['level_protectedcontent'] );
											}
											// redirect
											wp_safe_redirect( add_query_arg( 'msg', 1, 'admin.php?page=' . $page ) );
										} else {
											wp_safe_redirect( add_query_arg( 'msg', 4,  'admin.php?page=' . $page ) );
										}
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 4,  'admin.php?page=' . $page ) );
									}
								}
								break;
				case 'updated':	$id = (int) $_POST['level_id'];
								check_admin_referer('update-' . $id);
								if($id) {

									$level = new M_Level($id);

									if($level->update()) {
										// update the meta information
										if(!empty($_POST['level_protectedcontent'])) {
											$level->update_meta( 'level_protectedcontent', $_POST['level_protectedcontent'] );
										} else {
											$level->delete_meta( 'level_protectedcontent' );
										}
										// redirect
										wp_safe_redirect( add_query_arg( 'msg', 3,  'admin.php?page=' . $page ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 5,  'admin.php?page=' . $page ) );
									}
								} else {
									wp_safe_redirect( add_query_arg( 'msg', 5,  'admin.php?page=' . $page ) );
								}
								break;

				case 'delete':	if(isset($_GET['level_id'])) {
									$level_id = (int) $_GET['level_id'];

									check_admin_referer('delete-level_' . $level_id);

									$level = new M_Level($level_id);

									if($level->delete($level_id)) {
										// delete the meta information
										$level->delete_meta( 'level_protectedcontent' );
										// redirect
										wp_safe_redirect( add_query_arg( 'msg', 2, wp_get_referer() ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 6, wp_get_referer() ) );
									}

								}
								break;

				case 'toggle':	if(isset($_GET['level_id'])) {
									$level_id = (int) $_GET['level_id'];

									check_admin_referer('toggle-level_' . $level_id);

									$level = new M_Level($level_id);

									if( $level->toggleactivation() ) {
										wp_safe_redirect( add_query_arg( 'msg', 7, wp_get_referer() ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 8, wp_get_referer() ) );
									}

								}
								break;

				case 'bulk-delete':
								check_admin_referer('bulk-levels');
								foreach($_GET['levelcheck'] AS $value) {
									if(is_numeric($value)) {
										$level_id = (int) $value;

										$level = new M_Level($level_id);

										$level->delete();
									}
								}

								wp_safe_redirect( add_query_arg( 'msg', 2, wp_get_referer() ) );
								break;

				case 'bulk-toggle':
								check_admin_referer('bulk-levels');
								foreach($_GET['levelcheck'] AS $value) {
									if(is_numeric($value)) {
										$level_id = (int) $value;

										$level = new M_Level($level_id);

										$level->toggleactivation();
									}
								}

								wp_safe_redirect( add_query_arg( 'msg', 7, wp_get_referer() ) );
								break;

			}

		}

		function handle_levels_panel() {

			global $action, $page;

			switch(addslashes($action)) {

				case 'edit':	if(isset($_GET['level_id'])) {
									$level_id = (int) $_GET['level_id'];
									$this->handle_level_edit_form($level_id);
									return; // So we don't see the rest of this page
								}
								break;

				case 'clone':	if(isset($_GET['clone_id'])) {
									$level_id = (int) $_GET['clone_id'];
									$this->handle_level_edit_form($level_id, true);
									return; // So we don't see the rest of this page
								}
								break;
			}

			$filter = array();

			if(isset($_GET['s'])) {
				$s = stripslashes($_GET['s']);
				$filter['s'] = $s;
			} else {
				$s = '';
			}

			if(isset($_GET['level_id'])) {
				$filter['level_id'] = stripslashes($_GET['level_id']);
			}

			if(isset($_GET['order_by'])) {
				$filter['order_by'] = stripslashes($_GET['order_by']);
			}

			$messages = array();
			$messages[1] = __('Membership Level added.', 'membership');
			$messages[2] = __('Membership Level deleted.', 'membership');
			$messages[3] = __('Membership Level updated.', 'membership');
			$messages[4] = __('Membership Level not added.', 'membership');
			$messages[5] = __('Membership Level not updated.', 'membership');
			$messages[6] = __('Membership Level not deleted.', 'membership');

			$messages[7] = __('Membership Level activation toggled.', 'membership');
			$messages[8] = __('Membership Level activation not toggled.', 'membership');

			$messages[9] = __('Membership Levels updated.', 'membership');

			$levels = $this->get_membership_levels($filter);

			?>
			<div class='wrap nosubsub'>
				<div class="icon32" id="icon-link-manager"><br></div>
				<h2><?php _e('Access Levels','membership'); ?><?php if(count($levels) <= 3) { ?><a class="add-new-h2" href="admin.php?page=<?php echo $page; ?>&amp;action=edit&amp;level_id="><?php _e('Add New','membership'); ?></a><<?php } ?>/h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}

				if($this->show_user_help( $page )) {
					?>
					<div class='screenhelpheader'>
						<a href="admin.php?page=<?php echo $page; ?>&amp;action=removeheader" class="welcome-panel-close"><?php _e('Dismiss','membership'); ?></a>
						<?php
						ob_start();
						include_once(membership_dir('membershipincludes/help/header.levels.php'));
						echo ob_get_clean();
						?>
					</div>
					<?php
				}

				?>

				<form method="get" action="?page=<?php echo esc_attr($page); ?>" class="search-form">
				<p class="search-box">
					<input type='hidden' name='page' value='<?php echo esc_attr($page); ?>' />
					<label for="membership-search-input" class="screen-reader-text"><?php _e('Search Levels','membership'); ?>:</label>
					<input type="text" value="<?php echo esc_attr($s); ?>" name="s" id="membership-search-input">
					<input type="submit" class="button" value="<?php _e('Search Levels','membership'); ?>">
				</p>
				</form>

				<br class='clear' />

				<form method="get" action="?page=<?php echo esc_attr($page); ?>" id="posts-filter">

				<input type='hidden' name='page' value='<?php echo esc_attr($page); ?>' />

				<div class="tablenav">

				<div class="alignleft actions">
				<select name="action">
				<option selected="selected" value=""><?php _e('Bulk Actions', 'membership'); ?></option>
				<option value="delete"><?php _e('Delete', 'membership'); ?></option>
				<option value="toggle"><?php _e('Toggle activation', 'membership'); ?></option>
				</select>
				<input type="submit" class="button-secondary action" id="doaction" name="doaction" value="<?php _e('Apply', 'membership'); ?>">

				<select name="level_id">
				<option <?php if(isset($_GET['level_id']) && addslashes($_GET['level_id']) == 'all') echo "selected='selected'"; ?> value="all"><?php _e('View all Levels','membership'); ?></option>
				<option <?php if(isset($_GET['level_id']) && addslashes($_GET['level_id']) == 'active') echo "selected='selected'"; ?> value="active"><?php _e('View active Levels','membership'); ?></option>
				<option <?php if(isset($_GET['level_id']) && addslashes($_GET['level_id']) == 'inactive') echo "selected='selected'"; ?> value="inactive"><?php _e('View inactive Levels','membership'); ?></option>

				</select>

				<select name="order_by">
				<option <?php if(isset($_GET['order_by']) && addslashes($_GET['order_by']) == 'order_id') echo "selected='selected'"; ?> value="order_id"><?php _e('Order by Level ID','membership'); ?></option>
				<option <?php if(isset($_GET['order_by']) && addslashes($_GET['order_by']) == 'order_name') echo "selected='selected'"; ?> value="order_name"><?php _e('Order by Level Name','membership'); ?></option>
				</select>
				<input type="submit" class="button-secondary" value="<?php _e('Filter'); ?>" id="post-query-submit">

				</div>

				<div class="alignright actions">
				</div>

				<br class="clear">
				</div>

				<div class="clear"></div>

				<?php
					wp_original_referer_field(true, 'previous'); wp_nonce_field('bulk-levels');

					$columns = array(	"name" 		=> 	__('Level Name','membership'),
										"active"	=>	__('Active','membership'),
										"users"		=>	__('Users','membership'),
										"shortcode"	=>	__('Shortcode', 'membership') . $this->_tips->add_tip( __('The shortcode for this level is based on the title. It can be used to wrap content that you only want to be seen by members on this level e.g. [levelshortcode] protected content [/levelshortcode], use the [not-levelshortcode] shortcodes to wrap content that should be visible to people not on a particular level.','membership') )
									);

					$columns = apply_filters('membership_levelcolumns', $columns);

				?>

				<table cellspacing="0" class="widefat fixed">
					<thead>
					<tr>
					<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
					<?php
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
					?>
					</tr>
					</thead>

					<tfoot>
					<tr>
					<th style="" class="manage-column column-cb check-column" scope="col"><input type="checkbox"></th>
					<?php
						reset($columns);
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
					?>
					</tr>
					</tfoot>

					<tbody>
						<?php
						if($levels) {
							$levelcount = 0;
							foreach($levels as $key => $level) {
								$levelcount++; if($levelcount >= 4) break;
								?>
								<tr valign="middle" class="alternate" id="level-<?php echo $level->id; ?>">
									<th class="check-column" scope="row"><input type="checkbox" value="<?php echo $level->id; ?>" name="levelcheck[]"></th>
									<td class="column-name">
										<strong><a title="<?php _e('Level ID:', 'membership'); ?> <?php echo esc_attr($level->id); ?>" href="?page=<?php echo $page; ?>&amp;action=edit&amp;level_id=<?php echo $level->id; ?>" class="row-title"><?php echo esc_html($level->level_title); ?></a></strong>
										<?php
											$actions = array();
											//$actions['id'] = "<strong>" . __('ID : ', 'membership') . $level->id . "</strong>";
											$actions['edit'] = "<span class='edit'><a href='?page=" . $page . "&amp;action=edit&amp;level_id=" . $level->id . "'>" . __('Edit', 'membership') . "</a></span>";
											if($level->level_active == 0) {
												$actions['toggle'] = "<span class='edit activate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=toggle&amp;level_id=" . $level->id . "", 'toggle-level_' . $level->id) . "'>" . __('Activate', 'membership') . "</a></span>";
											} else {
												$actions['toggle'] = "<span class='edit deactivate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=toggle&amp;level_id=" . $level->id . "", 'toggle-level_' . $level->id) . "'>" . __('Deactivate', 'membership') . "</a></span>";
											}
											$actions['clone'] = "<span class='edit'><a href='?page=" . $page . "&amp;action=clone&amp;clone_id=" . $level->id . "'>" . __('Clone', 'membership') . "</a></span>";

											$actions['delete'] = "<span class='delete'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=delete&amp;level_id=" . $level->id . "", 'delete-level_' . $level->id) . "'>" . __('Delete', 'membership') . "</a></span>";

										?>
										<br><div class="row-actions"><?php echo implode(" | ", $actions); ?></div>
										</td>
									<td class="column-active">
										<?php
											switch($level->level_active) {
												case 0:	echo "<span  class='membershipinactivestatus'>" . __('Inactive', 'membership') . "</span>";
														break;
												case 1:	echo "<span  class='membershipactivestatus'>" . __('Active', 'membership') . "</span>";
														break;
											}
										?>
									</td>
									<td class="column-users">
										<strong>
											<?php echo $this->count_on_level( $level->id ); ?>
										</strong>
									</td>
									<td class="column-shortcode">
										<?php echo "[" . M_normalize_shortcode($level->level_title) . "]"; ?><br/>
										<?php echo "[not-" . M_normalize_shortcode($level->level_title) . "]"; ?>
									</td>
							    </tr>
								<?php
							}
						} else {
							$columncount = count($columns) + 1;
							?>
							<tr valign="middle" class="alternate" >
								<td colspan="<?php echo $columncount; ?>" scope="row"><?php _e('No Membership levels where found, click above to add one.','membership'); ?></td>
						    </tr>
							<?php
						}
						?>

					</tbody>
				</table>


				<div class="tablenav">

				<div class="alignleft actions">
				<select name="action2">
					<option selected="selected" value=""><?php _e('Bulk Actions', 'membership'); ?></option>
					<option value="delete"><?php _e('Delete', 'membership'); ?></option>
					<option value="toggle"><?php _e('Toggle activation', 'membership'); ?></option>
				</select>
				<input type="submit" class="button-secondary action" id="doaction2" name="doaction2" value="<?php _e('Apply', 'membership'); ?>">
				</div>
				<div class="alignright actions">
				</div>
				<br class="clear">
				</div>



				</form>

			</div> <!-- wrap -->
			<?php
		}

		function handle_sub_edit_form($sub_id = false, $clone = false) {

			global $page;

			$msub = new M_Subscription( $sub_id );
			if($sub_id && !$clone) {
				$sub = $msub->get();
			} else {
				if($clone) {
					$sub = $msub->get();
					$sub->sub_name .= __(' clone','membership');
				} else {
					$sub = new stdclass;
					$sub->sub_name = __('new subscription','membership');
				}
				$sub->id = time() * -1;

			}

			// Get the relevant parts
			if(isset($msub)) {
				$levels = $msub->get_levels();
			}

			?>
			<div class='wrap nosubsub'>
				<div class="icon32" id="icon-link-manager"><br></div>
				<?php
					if($sub->id < 0) {
						?>
						<h2><?php echo __('Add ','membership') . " - " . esc_html($sub->sub_name); ?></h2>
						<?php
					} else {
						?>
						<h2><?php echo __('Edit ','membership') . " - " . esc_html(stripslashes($sub->sub_name)); ?></h2>
						<?php
					}
				?>

				<?php
				if ( isset($usemsg) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[$usemsg] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}
				?>

				<div class='sub-liquid-left'>

					<div id='sub-left'>
						<form action='?page=<?php echo $page; ?>' name='subedit' method='post'>
							<input type='hidden' name='sub_id' id='sub_id' value='<?php echo $sub->id; ?>' />

						<div id='edit-sub' class='sub-holder-wrap'>
							<div class='sidebar-name no-movecursor'>
								<h3><?php echo esc_html(stripslashes($sub->sub_name)); ?></h3>
							</div>
							<div class='sub-holder'>
								<div class='sub-details'>
								<label for='sub_name'><?php _e('Subscription name','membership'); ?></label>
								<input class='wide' type='text' name='sub_name' id='sub_name' value='<?php echo esc_attr(stripslashes($sub->sub_name)); ?>' />
								<br/><br/>
								<label for='sub_name'><?php _e('Subscription description','membership'); ?></label>
								<?php
								$args = array("textarea_name" => "sub_description", "textarea_rows" => 5);

								if(!isset($sub->sub_description)) {
									$sub->sub_description = '';
								}

								wp_editor( stripslashes($sub->sub_description), "sub_description", $args );
								?>
								<br/>
								<?php
								if(!isset($sub->sub_pricetext)) {
									$sub->sub_pricetext = '';
								}
								?>
								<label for='sub_pricetext'><?php _e('Subscription price text','membership'); ?><?php echo $this->_tips->add_tip( __('The text you want to show as the price on the subscription form. E.G. Only $25 per month.','membership') ); ?></label>
								<input class='wide' type='text' name='sub_pricetext' id='sub_pricetext' value='<?php echo esc_attr(stripslashes($sub->sub_pricetext)); ?>' />
								<?php do_action('membership_subscription_form_after_details', $sub->id); ?>

								</div>

								<?php do_action('membership_subscription_form_before_levels', $sub->id); ?>

								<h3><?php _e('Membership levels','membership'); ?></h3>
								<p class='description'><?php _e('These are the levels that are part of this subscription and the order a user will travel through them. Any levels highlighted in red will never be reached due to the settings of previous levels.','membership'); ?></p>
								<div id='membership-levels-start'>
									<div id="main-start" class="sub-operation" style="display: block;">
											<h2 class="sidebar-name"><?php _e('Starting Point','membership'); ?></h2>
											<div class="inner-operation">
												<p class='description'><?php _e('A new signup for this subscription will start here and immediately pass to the next membership level listed below.','membership'); ?></p>
											</div>
									</div>
								</div>

								<ul id='membership-levels-holder'>
									<?php do_action('membership_subscription_form_before_level_list', $sub->id); ?>
									<?php
										$msub->sub_details();
									?>
									<?php do_action('membership_subscription_form_after_level_list', $sub->id); ?>
								</ul>
								<div id='membership-levels' class='droppable-levels levels-sortable'>
									<?php _e('Drop here','membership'); ?>
								</div>

								<?php
									// Hidden fields
								?>
								<input type='hidden' name='beingdragged' id='beingdragged' value='' />
								<input type='hidden' name='level-order' id='level-order' value=',<?php echo implode(',', $msub->levelorder); ?>' />

								<?php do_action('membership_subscription_form_after_levels', $sub->id); ?>

								<div class='buttons'>
									<?php
									if($sub->id > 0) {
										wp_original_referer_field(true, 'previous'); wp_nonce_field('update-' . $sub->id);
										?>
										<a href='?page=<?php echo $page; ?>' class='cancellink' title='Cancel edit'><?php _e('Cancel', 'membership'); ?></a>
										<input type='submit' value='<?php _e('Update', 'membership'); ?>' class='button-primary' />
										<input type='hidden' name='action' value='updated' />
										<?php
									} else {
										wp_original_referer_field(true, 'previous'); wp_nonce_field('add-' . $sub->id);
										?>
										<a href='?page=<?php echo $page; ?>' class='cancellink' title='Cancel add'><?php _e('Cancel', 'membership'); ?></a>
										<input type='submit' value='<?php _e('Add', 'membership'); ?>' class='button-primary' />
										<input type='hidden' name='action' value='added' />
										<?php
									}
									?>
								</div>

							</div>
						</div>
						</form>
					</div>


					<div id='hiden-actions'>

						<div id='template-holder'>
							<?php
								$msub->sub_template();
							?>
						</div>

					</div> <!-- hidden-actions -->

				</div> <!-- sub-liquid-left -->

				<div class='sub-liquid-right'>
					<div class="sub-holder-wrap">

								<?php
									do_action( 'membership_sidebar_top_subscription' );
									do_action( 'membership_sidebar_top', 'subscription' );
								?>

								<div class="sidebar-name no-movecursor">
									<h3><?php _e('Membership levels','membership'); ?></h3>
								</div>
								<div class="level-holder" id="sidebar-levels" style="min-height: 98px;">
									<ul class='subs subs-draggable'>
									<?php
										$levels = $this->get_membership_levels();
										foreach( (array) $levels as $key => $level) {
										?>
											<li class='level-draggable' id='level-<?php echo $level->id; ?>'>

												<div class='action action-draggable'>
													<div class='action-top closed'>
													<a href="#available-actions" class="action-button hide-if-no-js"></a>
													<?php echo esc_html($level->level_title); ?>
													</div>
													<div class='action-body closed'>
														<p>
															<a href='#addtosubscription' class='action-to-subscription' title="<?php _e('Add this level to the bottom of the membership levels list.','membership'); ?>"><?php _e('Add to Subscription','membership'); ?></a>
														</p>
													</div>
												</div>
											</li>
										<?php
											}
									?>
									</ul>
								</div>
					</div> <!-- sub-holder-wrap -->

				</div> <!-- sub-liquid-right -->

			</div> <!-- wrap -->

			<?php
		}

		function handle_subscriptions_updates() {

			global $action, $page;

			wp_reset_vars( array('action', 'page') );

			if(isset($_GET['doaction']) || isset($_GET['doaction2'])) {
				if(addslashes($_GET['action']) == 'delete' || addslashes($_GET['action2']) == 'delete') {
					$action = 'bulk-delete';
				}

				if(addslashes($_GET['action']) == 'toggle' || addslashes($_GET['action2']) == 'toggle') {
					$action = 'bulk-toggle';
				}

				if(addslashes($_GET['action']) == 'togglepublic' || addslashes($_GET['action2']) == 'togglepublic') {
					$action = 'bulk-togglepublic';
				}
			}

			switch(addslashes($action)) {

				case 'removeheader':	$this->dismiss_user_help( $page );
										wp_safe_redirect( remove_query_arg( 'action' ) );
										break;

				case 'added':	$subs = $this->get_subscriptions();
								if(count($subs) >= 3) {
									wp_die( __( 'Cheatin&#8217; uh?' ) );
								} else {
									$id = (int) $_POST['sub_id'];
									check_admin_referer('add-' . $id);

									if($id) {
										$sub = new M_Subscription( $id );

										if($sub->add()) {
											wp_safe_redirect( add_query_arg( 'msg', 1, 'admin.php?page=' . $page ) );
										} else {
											wp_safe_redirect( add_query_arg( 'msg', 4, 'admin.php?page=' . $page ) );
										}
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 4, 'admin.php?page=' . $page ) );
									}
								}
								break;
				case 'updated':	$id = (int) $_POST['sub_id'];
								check_admin_referer('update-' . $id);
								if($id) {
									$sub = new M_Subscription( $id );

									if($sub->update()) {
										wp_safe_redirect( add_query_arg( 'msg', 3, 'admin.php?page=' . $page ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 5, 'admin.php?page=' . $page ) );
									}
								} else {
									wp_safe_redirect( add_query_arg( 'msg', 5, 'admin.php?page=' . $page ) );
								}
								break;

				case 'delete':	if(isset($_GET['sub_id'])) {
									$sub_id = (int) $_GET['sub_id'];

									check_admin_referer('delete-sub_' . $sub_id);

									$sub = new M_Subscription( $sub_id );

									if($sub->delete()) {
										wp_safe_redirect( add_query_arg( 'msg', 2, wp_get_referer() ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 6, wp_get_referer() ) );
									}

								}
								break;

				case 'togglemakepublic':
								if(isset($_GET['sub_id'])) {
									$sub_id = (int) $_GET['sub_id'];

									check_admin_referer('togglemakepublic-sub_' . $sub_id);

									$sub = new M_Subscription( $sub_id );

									$sub->toggleactivation();

									if($sub->togglepublic()) {
										wp_safe_redirect( add_query_arg( 'msg', 7, wp_get_referer() ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 8, wp_get_referer() ) );
									}

								}
								break;

				case 'toggle':	if(isset($_GET['sub_id'])) {
									$sub_id = (int) $_GET['sub_id'];

									check_admin_referer('toggle-sub_' . $sub_id);

									$sub = new M_Subscription( $sub_id );

									if($sub->toggleactivation()) {
										wp_safe_redirect( add_query_arg( 'msg', 7, wp_get_referer() ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 8, wp_get_referer() ) );
									}

								}
								break;

				case 'togglepublic':
								if(isset($_GET['sub_id'])) {
									$sub_id = (int) $_GET['sub_id'];

									check_admin_referer('toggle-pubsub_' . $sub_id);

									$sub = new M_Subscription( $sub_id );

									if($sub->togglepublic()) {
										wp_safe_redirect( add_query_arg( 'msg', 9, wp_get_referer() ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 5, wp_get_referer() ) );
									}

								}
								break;

				case 'bulk-delete':
								check_admin_referer('bulk-subscriptions');
								foreach($_GET['subcheck'] AS $value) {
									if(is_numeric($value)) {
										$sub_id = (int) $value;

										$sub = new M_Subscription( $sub_id );

										$sub->delete();
									}
								}

								wp_safe_redirect( add_query_arg( 'msg', 2, wp_get_referer() ) );
								break;

				case 'bulk-toggle':
								check_admin_referer('bulk-subscriptions');
								foreach($_GET['subcheck'] AS $value) {
									if(is_numeric($value)) {
										$sub_id = (int) $value;

										$sub = new M_Subscription( $sub_id );

										$sub->toggleactivation();
									}
								}

								wp_safe_redirect( add_query_arg( 'msg', 7, wp_get_referer() ) );
								break;

				case 'bulk-togglepublic':
								check_admin_referer('bulk-subscriptions');
								foreach($_GET['subcheck'] AS $value) {
									if(is_numeric($value)) {
										$sub_id = (int) $value;

										$sub = new M_Subscription( $sub_id );

										$sub->togglepublic();
									}
								}

								wp_safe_redirect( add_query_arg( 'msg', 9, wp_get_referer() ) );
								break;

			}

		}

		function handle_subs_panel() {

			// Subscriptions panel
			global $action, $page;

			$filter = array();

			if($action == 'edit') {
				if(isset($_GET['sub_id'])) {
					$sub_id = (int) $_GET['sub_id'];
					$this->handle_sub_edit_form($sub_id);
					return; // So we don't see the rest of this page
				}
			}

			if(isset($_GET['s'])) {
				$s = stripslashes($_GET['s']);
				$filter['s'] = $s;
			} else {
				$s = '';
			}

			if(isset($_GET['sub_status'])) {
				$filter['sub_status'] = stripslashes($_GET['sub_status']);
			}

			if(isset($_GET['order_by'])) {
				$filter['order_by'] = stripslashes($_GET['order_by']);
			}

			$messages = array();
			$messages[1] = __('Subscription added.', 'membership');
			$messages[2] = __('Subscription deleted.', 'membership');
			$messages[3] = __('Subscription updated.', 'membership');
			$messages[4] = __('Subscription not added.', 'membership');
			$messages[5] = __('Subscription not updated.', 'membership');
			$messages[6] = __('Subscription not deleted.', 'membership');

			$messages[7] = __('Subscription activation toggled.', 'membership');
			$messages[8] = __('Subscription activation not toggled.', 'membership');

			$messages[9] = __('Subscriptions updated.', 'membership');


			$subs = $this->get_subscriptions($filter);

			?>
			<div class='wrap nosubsub'>
				<div class="icon32" id="icon-link-manager"><br></div>
				<h2><?php _e('Subscription Plans','membership'); ?><?php if(count($subs) < 3) { ?><a class="add-new-h2" href="admin.php?page=<?php echo $page; ?>&amp;action=edit&amp;sub_id="><?php _e('Add New','membership'); ?></a><?php } ?></h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}

				if($this->show_user_help( $page )) {
					?>
					<div class='screenhelpheader'>
						<a href="admin.php?page=<?php echo $page; ?>&amp;action=removeheader" class="welcome-panel-close"><?php _e('Dismiss','membership'); ?></a>
						<?php
						ob_start();
						include_once(membership_dir('membershipincludes/help/header.subscriptions.php'));
						echo ob_get_clean();
						?>
					</div>
					<?php
				}

				?>

				<form method="get" action="?page=<?php echo esc_attr($page); ?>" class="search-form">
				<p class="search-box">
					<input type='hidden' name='page' value='<?php echo esc_attr($page); ?>' />
					<label for="subscription-search-input" class="screen-reader-text"><?php _e('Search Memberships','membership'); ?>:</label>
					<input type="text" value="<?php echo esc_attr($s); ?>" name="s" id="subscription-search-input">
					<input type="submit" class="button" value="<?php _e('Search Subscriptions','membership'); ?>">
				</p>
				</form>

				<br class='clear' />

				<form method="get" action="?page=<?php echo esc_attr($page); ?>" id="posts-filter">

				<input type='hidden' name='page' value='<?php echo esc_attr($page); ?>' />

				<div class="tablenav">

				<div class="alignleft actions">
				<select name="action">
				<option selected="selected" value=""><?php _e('Bulk Actions', 'membership'); ?></option>
				<option value="delete"><?php _e('Delete', 'membership'); ?></option>
				<option value="toggle"><?php _e('Toggle activation', 'membership'); ?></option>
				<option value="togglepublic"><?php _e('Toggle public status', 'membership'); ?></option>
				</select>
				<input type="submit" class="button-secondary action" id="doaction" name="doaction" value="<?php _e('Apply'); ?>">

				<select name="sub_status">
				<option <?php if(isset($_GET['sub_status']) && addslashes($_GET['sub_id']) == 'all') echo "selected='selected'"; ?> value="all"><?php _e('View all subscriptions','membership'); ?></option>
				<option <?php if(isset($_GET['sub_status']) && addslashes($_GET['sub_id']) == 'active') echo "selected='selected'"; ?> value="active"><?php _e('View active subscriptions','membership'); ?></option>
				<option <?php if(isset($_GET['sub_status']) && addslashes($_GET['sub_id']) == 'inactive') echo "selected='selected'"; ?> value="inactive"><?php _e('View inactive subscriptions','membership'); ?></option>
				<option <?php if(isset($_GET['sub_status']) && addslashes($_GET['sub_id']) == 'public') echo "selected='selected'"; ?> value="public"><?php _e('View public subscriptions','membership'); ?></option>
				<option <?php if(isset($_GET['sub_status']) && addslashes($_GET['sub_id']) == 'private') echo "selected='selected'"; ?> value="private"><?php _e('View private subscriptions','membership'); ?></option>
				</select>

				<select name="order_by">
				<option <?php if(isset($_GET['order_by']) && addslashes($_GET['order_by']) == 'order_id') echo "selected='selected'"; ?> value="order_id"><?php _e('Order by subscription ID','membership'); ?></option>
				<option <?php if(isset($_GET['order_by']) && addslashes($_GET['order_by']) == 'order_name') echo "selected='selected'"; ?> value="order_name"><?php _e('Order by subscription name','membership'); ?></option>
				</select>
				<input type="submit" class="button-secondary" value="<?php _e('Filter', 'membership'); ?>" id="post-query-submit">

				</div>

				<div class="alignright actions">
				</div>

				<br class="clear">
				</div>

				<div class="clear"></div>

				<?php
					wp_original_referer_field(true, 'previous'); wp_nonce_field('bulk-subscriptions');

					$columns = array(	"name" 		=> 	__('Subscription Name','membership'),
										"active"	=>	__('Active','membership'),
										"public"	=>	__('Public','membership'),
										"users"		=>	__('Users','membership')
									);

					$columns = apply_filters('subscription_columns', $columns);

				?>

				<table cellspacing="0" class="widefat fixed">
					<thead>
					<tr>
					<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
					<?php
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
					?>
					</tr>
					</thead>

					<tfoot>
					<tr>
					<th style="" class="manage-column column-cb check-column" scope="col"><input type="checkbox"></th>
					<?php
						reset($columns);
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
					?>
					</tr>
					</tfoot>

					<tbody>
						<?php
						if($subs) {
							$subcount = 0;
							foreach($subs as $key => $sub) {
								$subcount++; if($subcount >= 4) break;
								?>
								<tr valign="middle" class="alternate" id="sub-<?php echo $sub->id; ?>">
									<th class="check-column" scope="row"><input type="checkbox" value="<?php echo $sub->id; ?>" name="subcheck[]"></th>
									<td class="column-name">
										<strong><a title="<?php _e('Subscription ID:', 'membership'); ?> <?php echo esc_attr($sub->id); ?>" href="?page=<?php echo $page; ?>&amp;action=edit&amp;sub_id=<?php echo $sub->id; ?>" class="row-title"><?php echo esc_html(stripslashes($sub->sub_name)); ?></a></strong>
										<?php
											$actions = array();
											//$actions['id'] = "<strong>" . __('ID : ', 'membership') . $sub->id . "</strong>";
											$actions['edit'] = "<span class='edit'><a href='?page=" . $page . "&amp;action=edit&amp;sub_id=" . $sub->id . "'>" . __('Edit', 'membership') . "</a></span>";

											if($sub->sub_active == 0 && $sub->sub_public == 0) {
												$actions['toggle'] = "<span class='edit activate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=togglemakepublic&amp;sub_id=" . $sub->id . "", 'togglemakepublic-sub_' . $sub->id) . "'>" . __('Activate and Make Public', 'membership') . "</a></span>";
											} else {
												if($sub->sub_active == 0) {
													$actions['toggle'] = "<span class='edit activate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=toggle&amp;sub_id=" . $sub->id . "", 'toggle-sub_' . $sub->id) . "'>" . __('Activate', 'membership') . "</a></span>";
												} else {
													$actions['toggle'] = "<span class='edit deactivate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=toggle&amp;sub_id=" . $sub->id . "", 'toggle-sub_' . $sub->id) . "'>" . __('Deactivate', 'membership') . "</a></span>";
												}

												if($sub->sub_public == 0) {
													$actions['public'] = "<span class='edit makeprivate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=togglepublic&amp;sub_id=" . $sub->id . "", 'toggle-pubsub_' . $sub->id) . "'>" . __('Make public', 'membership') . "</a></span>";
												} else {
													$actions['public'] = "<span class='edit makepublic'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=togglepublic&amp;sub_id=" . $sub->id . "", 'toggle-pubsub_' . $sub->id) . "'>" . __('Make private', 'membership') . "</a></span>";
												}
											}

											$actions['delete'] = "<span class='delete'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=delete&amp;sub_id=" . $sub->id . "", 'delete-sub_' . $sub->id) . "'>" . __('Delete', 'membership') . "</a></span>";

										?>
										<br><div class="row-actions"><?php echo implode(" | ", $actions); ?></div>
										</td>
									<td class="column-active">
										<?php
											switch($sub->sub_active) {
												case 0:	echo "<span  class='membershipinactivestatus'>" . __('Inactive', 'membership') . "</span>";
														break;
												case 1:	echo "<span  class='membershipactivestatus'>" . __('Active', 'membership') . "</span>";
														break;
											}
										?>
									</td>
									<td class="column-public">
										<?php
											switch($sub->sub_public) {
												case 0:	echo __('Private', 'membership');
														break;
												case 1:	echo "<strong>" . __('Public', 'membership') . "</strong>";
														break;
											}
										?>
									</td>
									<td class="column-users">
										<strong>
											<?php echo $this->count_on_sub( $sub->id ); ?>
										</strong>
									</td>
							    </tr>
								<?php
							}
						} else {
							$columncount = count($columns) + 1;
							?>
							<tr valign="middle" class="alternate" >
								<td colspan="<?php echo $columncount; ?>" scope="row"><?php _e('No Subscriptions where found, click above to add one.','membership'); ?></td>
						    </tr>
							<?php
						}
						?>

					</tbody>
				</table>


				<div class="tablenav">

				<div class="alignleft actions">
				<select name="action2">
					<option selected="selected" value=""><?php _e('Bulk Actions', 'membership'); ?></option>
					<option value="delete"><?php _e('Delete', 'membership'); ?></option>
					<option value="toggle"><?php _e('Toggle activation', 'membership'); ?></option>
				</select>
				<input type="submit" class="button-secondary action" id="doaction2" name="doaction2" value="<?php _e('Apply', 'membership'); ?>">
				</div>
				<div class="alignright actions">
				</div>
				<br class="clear">
				</div>



				</form>

			</div> <!-- wrap -->
			<?php

		}

		function handle_urlgroups_updates() {

			global $action, $page;

			wp_reset_vars( array('action', 'page') );

			if(isset($_GET['doaction']) || isset($_GET['doaction2'])) {
				if(addslashes($_GET['action']) == 'delete' || addslashes($_GET['action2']) == 'delete') {
					$action = 'bulk-delete';
				}
			}

			switch(addslashes($action)) {

				case 'removeheader':	$this->dismiss_user_help( $page );
										wp_safe_redirect( remove_query_arg( 'action' ) );
										break;

				case 'added':	check_admin_referer('add-group');

								$group = new M_Urlgroup( 0 );

								if($group->add()) {
									wp_safe_redirect( add_query_arg( 'msg', 3, 'admin.php?page=' . $page ) );
								} else {
									wp_safe_redirect( add_query_arg( 'msg', 4, 'admin.php?page=' . $page ) );
								}

								break;
				case 'updated':	$id = (int) $_POST['ID'];
								check_admin_referer('update-group-' . $id);
								if($id) {
									$group = new M_Urlgroup( $id );

									if($group->update()) {
										wp_safe_redirect( add_query_arg( 'msg', 1, 'admin.php?page=' . $page ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 2, 'admin.php?page=' . $page ) );
									}
								} else {
									wp_safe_redirect( add_query_arg( 'msg', 2, 'admin.php?page=' . $page ) );
								}
								break;

				case 'delete':	if(isset($_GET['group'])) {
									$id = (int) $_GET['group'];

									check_admin_referer('delete-group_' . $id);

									$group = new M_Urlgroup( $id );

									if($group->delete()) {
										wp_safe_redirect( add_query_arg( 'msg', 5, wp_get_referer() ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 6, wp_get_referer() ) );
									}

								}
								break;

				case 'bulk-delete':
								check_admin_referer('bulk-groups');
								foreach($_GET['groupcheck'] AS $value) {
									if(is_numeric($value)) {
										$id = (int) $value;

										$group = new M_Urlgroup( $id );

										$group->delete();
									}
								}

								wp_safe_redirect( add_query_arg( 'msg', 7, wp_get_referer() ) );
								break;
			}

		}

		function get_urlgroups() {

			$sql = $this->db->prepare( "SELECT * FROM {$this->urlgroups} WHERE groupname NOT LIKE (%s) ORDER BY id ASC", '\_%' );

			$results = $this->db->get_results( $sql );

			if(!empty($results)) {
				return $results;
			} else {
				return false;
			}

		}

		function show_urlgroup_edit( $group_id ) {

			global $page;

			if( $group_id === false ) {
				$add = new M_Urlgroup( 0 );

				echo "<div class='wrap'>";
				echo "<h2>" . __('Add URL group','membership') . "</h2>";

				echo '<div id="poststuff" class="metabox-holder">';
				?>
				<div class="postbox">
					<h3 class="hndle" style='cursor:auto;'><span><?php _e('Add URL group','membership'); ?></span></h3>
					<div class="inside">
						<?php
						echo '<form method="post" action="?page=' . $page . '">';
						echo '<input type="hidden" name="ID" value="" />';
						echo "<input type='hidden' name='action' value='added' />";
						wp_nonce_field('add-group');
						$add->addform();
						echo '<p class="submit">';
						echo '<input class="button-primary alignright" type="submit" name="go" value="' . __('Add group', 'membership') . '" /></p>';
						echo '</form>';
						echo '<br/>';
						?>
					</div>
				</div>
				<?php
				echo "</div>";
				echo "</div>";
			} else {
				$edit = new M_Urlgroup( (int) $group_id );

				echo "<div class='wrap'>";
				echo "<h2>" . __('Edit URL group','membership') . "</h2>";

				echo '<div id="poststuff" class="metabox-holder">';
				?>
				<div class="postbox">
					<h3 class="hndle" style='cursor:auto;'><span><?php _e('Edit URL group','membership'); ?></span></h3>
					<div class="inside">
						<?php
						echo '<form method="post" action="?page=' . $page . '">';
						echo '<input type="hidden" name="ID" value="' . $group_id . '" />';
						echo "<input type='hidden' name='action' value='updated' />";
						wp_nonce_field('update-group-' . $group_id);
						$edit->editform();
						echo '<p class="submit">';
						echo '<input class="button-primary alignright" type="submit" name="go" value="' . __('Update group', 'membership') . '" /></p>';
						echo '</form>';
						echo '<br/>';
						?>
					</div>
				</div>
				<?php
				echo "</div>";
				echo "</div>";
			}

		}

		function handle_urlgroups_panel() {
			global $action, $page;

			wp_reset_vars( array('action', 'page') );

			switch(addslashes($action)) {

				case 'edit':	if(!empty($_GET['group'])) {
									// Make a communication
									$this->show_urlgroup_edit( $_GET['group'] );
								} else {
									$this->show_urlgroup_edit( false );
								}
								return; // so we don't show the list below
								break;

			}


			$messages = array();
			$messages[1] = __('Group updated.','membership');
			$messages[2] = __('Group not updated.','membership');

			$messages[3] = __('Group added.','membership');
			$messages[4] = __('Group not added.','membership');

			$messages[5] = __('Group deleted.','membership');
			$messages[6] = __('Group not deleted.','membership');

			$messages[7] = __('Groups deleted.','membership');


			?>
			<div class='wrap'>
				<div class="icon32" id="icon-edit-pages"><br></div>
				<h2><?php _e('Edit URL Groups','membership'); ?><a class="add-new-h2" href="admin.php?page=<?php echo $page; ?>&amp;action=edit&amp;group="><?php _e('Add New','membership'); ?></a></h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}

				$groups = $this->get_urlgroups();
				$groups = apply_filters('M_urlgroups_list', $groups);

				if($this->show_user_help( $page )) {
					?>
					<div class='screenhelpheader'>
						<a href="admin.php?page=<?php echo $page; ?>&amp;action=removeheader" class="welcome-panel-close"><?php _e('Dismiss','membership'); ?></a>
						<?php
						ob_start();
						include_once(membership_dir('membershipincludes/help/header.urlgroups.php'));
						echo ob_get_clean();
						?>
					</div>
					<?php
				}

				?>

				<form method="get" action="?page=<?php echo esc_attr($page); ?>" id="posts-filter">

				<input type='hidden' name='page' value='<?php echo esc_attr($page); ?>' />

				<div class="tablenav">

				<div class="alignleft actions">
				<select name="action">
				<option selected="selected" value=""><?php _e('Bulk Actions','membership'); ?></option>
				<option value="delete"><?php _e('Delete','membership'); ?></option>
				</select>
				<input type="submit" class="button-secondary action" id="doaction" name="doaction" value="<?php _e('Apply','membership'); ?>">

				</div>

				<div class="alignright actions">
				</div>

				<br class="clear">
				</div>

				<div class="clear"></div>

				<?php
					wp_original_referer_field(true, 'previous'); wp_nonce_field('bulk-groups');

					$columns = array(	"name" 		=> 	__('Group Name','membership')
									);

					$columns = apply_filters('membership_groupscolumns', $columns);

				?>

				<table cellspacing="0" class="widefat fixed">
					<thead>
					<tr>
					<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
					<?php
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
					?>
					</tr>
					</thead>

					<tfoot>
					<tr>
					<th style="" class="manage-column column-cb check-column" scope="col"><input type="checkbox"></th>
					<?php
						reset($columns);
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
					?>
					</tr>
					</tfoot>

					<tbody>
						<?php
						if(!empty($groups)) {
							foreach($groups as $key => $group) {
								?>
								<tr valign="middle" class="alternate" id="group-<?php echo $group->id; ?>">
									<th class="check-column" scope="row"><input type="checkbox" value="<?php echo esc_attr($group->id); ?>" name="groupcheck[]"></th>
									<td class="column-name">
										<strong><a title="<?php _e('Edit','membership'); ?> <?php echo esc_attr(stripslashes($group->groupname)); ?>" href="?page=<?php echo $page; ?>&amp;action=edit&amp;group=<?php echo $group->id; ?>" class="row-title"><?php echo esc_html(stripslashes($group->groupname)); ?></a></strong>
										<?php
											$actions = array();
											$actions['edit'] = "<span class='edit'><a href='?page=" . $page . "&amp;action=edit&amp;group=" . $group->id . "'>" . __('Edit','membership') . "</a></span>";
											$actions['delete'] = "<span class='delete'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=delete&amp;group=" . $group->id . "", 'delete-group_' . $group->id) . "'>" . __('Delete', 'membership') . "</a></span>";
										?>
										<br><div class="row-actions"><?php echo implode(" | ", $actions); ?></div>
										</td>
							    </tr>
								<?php
							}
						} else {
							$columncount = count($columns) + 1;
							?>
							<tr valign="middle" class="alternate" >
								<td colspan="<?php echo $columncount; ?>" scope="row"><?php _e('No URL groups have been set up.','membership'); ?></td>
						    </tr>
							<?php
						}
						?>

					</tbody>
				</table>

				<div class="tablenav">

				<div class="alignleft actions">
				<select name="action2">
					<option selected="selected" value=""><?php _e('Bulk Actions','membership'); ?></option>
					<option value="delete"><?php _e('Delete','membership'); ?></option>
				</select>
				<input type="submit" class="button-secondary action" id="doaction2" name="doaction2" value="<?php _e('Apply','membership'); ?>">
				</div>
				<div class="alignright actions">
				</div>
				<br class="clear">
				</div>

				</form>

			</div> <!-- wrap -->
			<?php
		}

		function handle_ping_updates() {

			global $action, $page;

			wp_reset_vars( array('action', 'page') );

			if(isset($_GET['doaction']) || isset($_GET['doaction2'])) {
				if(addslashes($_GET['action']) == 'delete' || addslashes($_GET['action2']) == 'delete') {
					$action = 'bulk-delete';
				}
			}

			switch(addslashes($action)) {

				case 'removeheader':	$this->dismiss_user_help( $page );
										wp_safe_redirect( remove_query_arg( 'action' ) );
										break;

				case 'added':	check_admin_referer('add-ping');

								$ping = new M_Ping( 0 );

								if($ping->add()) {
									wp_safe_redirect( add_query_arg( 'msg', 3, 'admin.php?page=' . $page ) );
								} else {
									wp_safe_redirect( add_query_arg( 'msg', 4, 'admin.php?page=' . $page ) );
								}

								break;
				case 'updated':	$id = (int) $_POST['ID'];
								check_admin_referer('update-ping-' . $id);
								if($id) {
									$ping = new M_Ping( $id );

									if($ping->update()) {
										wp_safe_redirect( add_query_arg( 'msg', 1, 'admin.php?page=' . $page ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 2, 'admin.php?page=' . $page ) );
									}
								} else {
									wp_safe_redirect( add_query_arg( 'msg', 2, 'admin.php?page=' . $page ) );
								}
								break;

				case 'delete':	if(isset($_GET['ping'])) {
									$id = (int) $_GET['ping'];

									check_admin_referer('delete-ping_' . $id);

									$ping = new M_Ping( $id );

									if($ping->delete()) {
										wp_safe_redirect( add_query_arg( 'msg', 5, wp_get_referer() ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 6, wp_get_referer() ) );
									}

								}
								break;

				case 'bulk-delete':
								check_admin_referer('bulk-pings');
								foreach($_GET['pingcheck'] AS $value) {
									if(is_numeric($value)) {
										$id = (int) $value;

										$ping = new M_Ping( $id );

										$ping->delete();
									}
								}

								wp_safe_redirect( add_query_arg( 'msg', 7, wp_get_referer() ) );
								break;

				case 'history':
								if(isset($_GET['history']) && isset($_GET['resend'])) {
									$history = (int) $_GET['history'];
									switch($_GET['resend']) {
										case 'new':		$ping = new M_Ping( false );
														$ping->resend_historic_ping( $history, true );
														wp_safe_redirect( add_query_arg( 'msg', 1, wp_get_referer() ) );
														break;
										case 'over':	$ping = new M_Ping( false );
														$ping->resend_historic_ping( $history, false );
														wp_safe_redirect( add_query_arg( 'msg', 1, wp_get_referer() ) );
														break;
									}
								}
								break;
			}



		}

		function show_ping_edit( $ping_id ) {

			global $page;

			if( $ping_id === false ) {
				$add = new M_Ping( 0 );

				echo "<div class='wrap'>";
				echo "<h2>" . __('Add Ping details','membership') . "</h2>";

				echo '<div id="poststuff" class="metabox-holder">';
				?>
				<div class="postbox">
					<h3 class="hndle" style='cursor:auto;'><span><?php _e('Add ping details','membership'); ?></span></h3>
					<div class="inside">
						<?php
						echo '<form method="post" action="?page=' . $page . '">';
						echo '<input type="hidden" name="ID" value="" />';
						echo "<input type='hidden' name='action' value='added' />";
						wp_nonce_field('add-ping');
						$add->addform();
						echo '<p class="submit">';
						echo '<input class="button-primary alignright" type="submit" name="go" value="' . __('Add ping details', 'membership') . '" /></p>';
						echo '</form>';
						echo '<br/>';
						?>
					</div>
				</div>
				<?php
				echo "</div>";
				echo "</div>";
			} else {
				$edit = new M_Ping( (int) $ping_id );

				echo "<div class='wrap'>";
				echo "<h2>" . __('Edit Ping details','membership') . "</h2>";

				echo '<div id="poststuff" class="metabox-holder">';
				?>
				<div class="postbox">
					<h3 class="hndle" style='cursor:auto;'><span><?php _e('Edit ping details','membership'); ?></span></h3>
					<div class="inside">
						<?php
						echo '<form method="post" action="?page=' . $page . '">';
						echo '<input type="hidden" name="ID" value="' . $ping_id . '" />';
						echo "<input type='hidden' name='action' value='updated' />";
						wp_nonce_field('update-ping-' . $ping_id);
						$edit->editform();
						echo '<p class="submit">';
						echo '<input class="button-primary alignright" type="submit" name="go" value="' . __('Update ping details', 'membership') . '" /></p>';
						echo '</form>';
						echo '<br/>';
						?>
					</div>
				</div>
				<?php
				echo "</div>";
				echo "</div>";
			}

		}

		function get_pings() {
			$sql = "SELECT * FROM {$this->pings} ORDER BY id ASC";

			$results = $this->db->get_results( $sql );

			if(!empty($results)) {
				return $results;
			} else {
				return false;
			}
		}

		function handle_ping_history_panel( $ping_id ) {
			global $action, $page;

			wp_reset_vars( array('action', 'page') );

			$messages = array();
			$messages[1] = __('Ping resent.','membership');
			$messages[2] = __('Ping not resent.','membership');

			?>
			<div class='wrap'>
				<div class="icon32" id="icon-link-manager"><br></div>
				<h2><?php _e('Pings History','membership'); ?></h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}

				$ping = new M_Ping( $ping_id );

				$history = $ping->get_history();

				$columns = array(	"name" 		=> 	__('Ping Name','membership'),
									"url"		=>	__('URL','membership'),
									"status"	=>	__('Status', 'membership'),
									"date"		=>	__('Date', 'membership')
								);

				$columns = apply_filters('membership_pingscolumns', $columns);

				?>
				<table cellspacing="0" class="widefat fixed">
					<thead>
					<tr>
					<?php
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
					?>
					</tr>
					</thead>

					<tfoot>
					<tr>
					<?php
						reset($columns);
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
					?>
					</tr>
					</tfoot>

					<tbody>
						<?php
						if(!empty($history)) {
							foreach($history as $key => $h) {
								?>
								<tr valign="middle" class="alternate" id="history-<?php echo $h->id; ?>">
									<td class="column-name">
										<strong><?php echo esc_html(stripslashes($ping->ping_name() )); ?></strong>
										<?php
											$actions = array();
											$actions['resendnew'] = "<span class='edit'><a href='" . wp_nonce_url("?page=" . $page . "&amp;action=history&amp;resend=new&amp;history=" . $h->id, 'membership_resend_ping_' . $h->id ) . "'>" . __('Resend as new ping','membership') . "</a></span>";
											$actions['resendover'] = "<span class='edit'><a href='" . wp_nonce_url("?page=" . $page . "&amp;action=history&amp;resend=over&amp;history=" . $h->id, 'membership_resend_ping_' . $h->id ) . "'>" . __('Resend and overwrite','membership') . "</a></span>";
										?>
										<br><div class="row-actions"><?php echo implode(" | ", $actions); ?></div>
									</td>
									<td class="column-name">
										<?php
										echo $ping->ping_url();
										?>
									</td>
									<td class="column-name">
										<?php
										// Status
										$status = unserialize($h->ping_return);
										if(is_wp_error($status)) {
											// WP error
											echo "<span style='color: red;'>" . implode("<br/>", $status->get_error_messages() ) . "</span>";
										} else {
											if(!empty($status['response'])) {
												if($status['response']['code'] == '200') {
													echo "<span style='color: green;'>" . $status['response']['code'] . " - " . $status['response']['message'] . "</span>";
												} else {
													echo "<span style='color: red;'>" . $status['response']['code'] . " - " . $status['response']['message'] . "</span>";
												}
											}
										}
										//echo $ping->ping_url();
										?>
									</td>
									<td class="column-name">
										<?php
										echo mysql2date( "Y-m-j H:i:s", $h->ping_sent );
										?>
									</td>
							    </tr>
								<?php
							}
						} else {
							$columncount = count($columns);
							?>
							<tr valign="middle" class="alternate" >
								<td colspan="<?php echo $columncount; ?>" scope="row"><?php _e('No History available for this ping.','membership'); ?></td>
						    </tr>
							<?php
						}
						?>

					</tbody>
				</table>

			</div> <!-- wrap -->
			<?php
		}

		function handle_pings_panel() {
			global $action, $page;

			wp_reset_vars( array('action', 'page') );

			switch(addslashes($action)) {

				case 'edit':	if(!empty($_GET['ping'])) {
									// Make a communication
									$this->show_ping_edit( (int) $_GET['ping'] );
								} else {
									$this->show_ping_edit( false );
								}
								return; // so we don't show the list below
								break;

				case 'history':
								if(!empty($_GET['ping'])) {
									$this->handle_ping_history_panel( (int) $_GET['ping'] );
								}
								return;
								break;

			}


			$messages = array();
			$messages[1] = __('Ping details updated.','membership');
			$messages[2] = __('Ping details not updated.','membership');

			$messages[3] = __('Ping details added.','membership');
			$messages[4] = __('Ping details not added.','membership');

			$messages[5] = __('Ping details deleted.','membership');
			$messages[6] = __('Ping details not deleted.','membership');

			$messages[7] = __('Ping details deleted.','membership');

			?>
			<div class='wrap'>
				<div class="icon32" id="icon-link-manager"><br></div>
				<h2><?php _e('Edit Pings','membership'); ?><a class="add-new-h2" href="admin.php?page=<?php echo $page; ?>&amp;action=edit&amp;ping="><?php _e('Add New','membership'); ?></a></h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}

				$pings = $this->get_pings();
				$pings = apply_filters('M_pings_list', $pings);

				if($this->show_user_help( $page )) {
					?>
					<div class='screenhelpheader'>
						<a href="admin.php?page=<?php echo $page; ?>&amp;action=removeheader" class="welcome-panel-close"><?php _e('Dismiss','membership'); ?></a>
						<?php
						ob_start();
						include_once(membership_dir('membershipincludes/help/header.pings.php'));
						echo ob_get_clean();
						?>
					</div>
					<?php
				}

				?>

				<form method="get" action="?page=<?php echo esc_attr($page); ?>" id="posts-filter">

				<input type='hidden' name='page' value='<?php echo esc_attr($page); ?>' />

				<div class="tablenav">

				<div class="alignleft actions">
				<select name="action">
				<option selected="selected" value=""><?php _e('Bulk Actions','membership'); ?></option>
				<option value="delete"><?php _e('Delete','membership'); ?></option>
				</select>
				<input type="submit" class="button-secondary action" id="doaction" name="doaction" value="<?php _e('Apply','membership'); ?>">

				</div>

				<div class="alignright actions">
				</div>

				<br class="clear">
				</div>

				<div class="clear"></div>

				<?php
					wp_original_referer_field(true, 'previous'); wp_nonce_field('bulk-pings');

					$columns = array(	"name" 		=> 	__('Ping Name','membership')
									);

					$columns = apply_filters('membership_pingscolumns', $columns);

				?>

				<table cellspacing="0" class="widefat fixed">
					<thead>
					<tr>
					<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
					<?php
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
					?>
					</tr>
					</thead>

					<tfoot>
					<tr>
					<th style="" class="manage-column column-cb check-column" scope="col"><input type="checkbox"></th>
					<?php
						reset($columns);
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
					?>
					</tr>
					</tfoot>

					<tbody>
						<?php
						if(!empty($pings)) {
							foreach($pings as $key => $ping) {
								?>
								<tr valign="middle" class="alternate" id="ping-<?php echo $ping->id; ?>">
									<th class="check-column" scope="row"><input type="checkbox" value="<?php echo esc_attr($ping->id); ?>" name="pingcheck[]"></th>
									<td class="column-name">
										<strong><a title="<?php _e('Edit','membership'); ?> <?php echo esc_attr(stripslashes($ping->pingname)); ?>" href="?page=<?php echo $page; ?>&amp;action=edit&amp;ping=<?php echo $ping->id; ?>" class="row-title"><?php echo esc_html(stripslashes($ping->pingname)); ?></a></strong>
										<?php
											$actions = array();
											$actions['edit'] = "<span class='edit'><a href='?page=" . $page . "&amp;action=edit&amp;ping=" . $ping->id . "'>" . __('Edit','membership') . "</a></span>";
											$actions['trans'] = "<span class='edit'><a href='?page=" . $page . "&amp;action=history&amp;ping=" . $ping->id . "'>" . __('History','membership') . "</a></span>";
											$actions['delete'] = "<span class='delete'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=delete&amp;ping=" . $ping->id . "", 'delete-ping_' . $ping->id) . "'>" . __('Delete', 'membership') . "</a></span>";
										?>
										<br><div class="row-actions"><?php echo implode(" | ", $actions); ?></div>
										</td>
							    </tr>
								<?php
							}
						} else {
							$columncount = count($columns) + 1;
							?>
							<tr valign="middle" class="alternate" >
								<td colspan="<?php echo $columncount; ?>" scope="row"><?php _e('No Pings have been set up.','membership'); ?></td>
						    </tr>
							<?php
						}
						?>

					</tbody>
				</table>

				<div class="tablenav">

				<div class="alignleft actions">
				<select name="action2">
					<option selected="selected" value=""><?php _e('Bulk Actions','membership'); ?></option>
					<option value="delete"><?php _e('Delete','membership'); ?></option>
				</select>
				<input type="submit" class="button-secondary action" id="doaction2" name="doaction2" value="<?php _e('Apply','membership'); ?>">
				</div>
				<div class="alignright actions">
				</div>
				<br class="clear">
				</div>

				</form>

			</div> <!-- wrap -->
			<?php
		}


		function handle_profile_member_page() {
			?>
			<div class='wrap'>
				<div class="icon32" id="icon-users"><br></div>
				<h2><?php _e('Membership details','membership'); ?></h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}

				if(!current_user_is_member()) {
					// Not a member so show the message and signup forms
					?>
						<div class='nonmembermessage'>
						<h3><?php _e('Not called yet','membership'); ?></h3>
						<?php _e('Not called yet','membership'); ?>
						</div>
						<div class='signups'>
						<h3><?php _e('Select a subscription','membership'); ?></h3>
						<p>
							<?php _e('Please select a subscription from the options below.','membership'); ?>
						</p>
						<?php
							do_action( 'membership_subscription_form_before_subscriptions', $user_id );

							$subs = $this->get_subscriptions();

							do_action( 'membership_subscription_form_before_paid_subscriptions', $user_id );

							foreach((array) $subs as $key => $sub) {

								$subscription = new M_Subscription($sub->id);

								?>
								<div class="subscription">
									<div class="description">
										<h3><?php echo $subscription->sub_name(); ?></h3>
										<p><?php echo $subscription->sub_description(); ?></p>
									</div>

								<?php
									$pricing = $subscription->get_pricingarray();

									if($pricing) {
										?>
										<div class='priceforms'>
											<?php do_action('membership_purchase_button', $subscription, $pricing, $user_id); ?>
										</div>
										<?php
									}
								?>
								</div>
								<?php
							}

							do_action( 'membership_subscription_form_after_paid_subscriptions', $user_id );
							do_action( 'membership_subscription_form_after_subscriptions', $user_id );
							?>
						</div>
					<?php
				} else {
					if(current_user_has_subscription()) {
						// User has a subscription already. Display the details - and an action to enable upgrading / not upgrading to take place.
						?>
							<div class='nonmembermessage'>
							<h3><?php _e('Not called yet','membership'); ?></h3>
							<?php _e('Not called yet','membership'); ?>
							</div>
						<?php
					}
				}

				?>
			</div> <!-- wrap -->
			<?php
		}

		// Media extension options
		/*
		add_filter('attachment_fields_to_edit', array(&$this, 'add_media_protection_settings'), 99, 2);
		add_filter('attachment_fields_to_save', array(&$this, 'save_media_protection_settings'), 99, 2);
		*/
		function add_media_protection_settings($fields, $post) {

			global $M_options;

			$protected = get_post_meta($post->ID, '_membership_protected_content_group', true);
			if(empty($protected)) {
				$protected = 'no';
			}

			$html = "<select name='attachments[" . $post->ID . "][protected-content]'>";

			$html .= "<option value='no'";
			$html .= ">" . __('None', 'membership') . "</option>";

			if(!empty($M_options['membershipdownloadgroups'])) {
				foreach($M_options['membershipdownloadgroups'] as $key => $value) {
					if(!empty($value)) {
						$html .= "<option value='" . esc_attr(trim(stripslashes($value))) . "'";
						if($protected == esc_attr(trim(stripslashes($value)))) {
							$html .= " selected='selected'";
						}
						$html .= ">" . esc_html(trim(stripslashes($value))) . "</option>";
					}
				}
			}
			$html .= "</select>";

			$fields['media-protected-content'] = array(
				'label' 	=> __('Protected content group', 'membership'),
				'input' 	=> 'html',
				'html' 		=> $html,
				'helps'     => __('Is this an item you may want to restrict access to?', 'membership')
			);
			return $fields;
		}

		function save_media_protection_settings($post, $attachment) {
			$key = "protected-content";
			if ( empty( $attachment[$key] ) || addslashes( $attachment[$key] ) == 'no') {
				delete_post_meta($post['ID'], '_membership_protected_content_group'); // delete any residual metadata from a free-form field (as inserted below)
			} else // free-form text was entered, insert postmeta with credit
				update_post_meta($post['ID'], '_membership_protected_content_group', $attachment['protected-content']); // insert 'media-credit' metadata field for image with free-form text
			return $post;
		}

		// Fake shortcode function for administration area - public class has the proper processing function
		function do_fake_shortcode($atts, $content = null, $code = "") {

			global $M_options;

			return $M_options['shortcodemessage'];

		}
		// Database actions

		function update_levelcounts() {

			$sql = $this->db->prepare( "SELECT level_id, count(*) AS number FROM {$this->membership_relationships} WHERE level_id != %d GROUP BY level_id", 0 );

			$this->db->query( $this->db->prepare( "UPDATE {$this->membership_levels} SET level_count = %d", 0) );

			$levels = $this->db->get_results($sql);
			if($levels) {
				foreach($levels as $key => $level) {
					$this->db->update( $this->membership_levels, array('level_count' => $level->number), array('id' => $level->level_id) );
				}
			}

		}

		function update_subcounts() {

			$sql = $this->db->prepare( "SELECT sub_id, count(*) AS number FROM {$this->membership_relationships} WHERE sub_id != %d GROUP BY sub_id", 0 );

			$this->db->query( $this->db->prepare( "UPDATE {$this->subscriptions} SET sub_count = %d", 0) );

			$subs = $this->db->get_results($sql);
			if($subs) {
				foreach($subs as $key => $sub) {
					$this->db->update( $this->subscriptions, array('sub_count' => $sub->number), array('id' => $sub->sub_id) );
				}
			}
		}

		function get_membership_levels($filter = false) {

			if($filter) {
				$where = array();
				$orderby = array();

				if(isset($filter['s'])) {
					$where[] = "level_title LIKE '%" . mysql_real_escape_string($filter['s']) . "%'";
				}

				if(isset($filter['level_id'])) {
					switch($filter['level_id']) {

						case 'active':		$where[] = "level_active = 1";
											break;
						case 'inactive':	$where[] = "level_active = 0";
											break;

					}
				}

				if(isset($filter['order_by'])) {
					switch($filter['order_by']) {

						case 'order_id':	$orderby[] = 'id ASC';
											break;
						case 'order_name':	$orderby[] = 'level_title ASC';
											break;

					}
				}

			}

			$sql = "SELECT * FROM {$this->membership_levels}";

			if(!empty($where)) {
				$sql .= " WHERE " . implode(' AND ', $where);
			}

			if(!empty($orderby)) {
				$sql .= " ORDER BY " . implode(', ', $orderby);
			}

			return $this->db->get_results($sql);


		}

		//subscriptions

		function get_public_subscriptions() {

			$where = array();
			$orderby = array();

			$where[] = "sub_public = 1";
			$where[] = "sub_active = 1";

			$orderby[] = 'id ASC';

			$sql = "SELECT * FROM {$this->subscriptions}";

			if(!empty($where)) {
				$sql .= " WHERE " . implode(' AND ', $where);
			}

			if(!empty($orderby)) {
				$sql .= " ORDER BY " . implode(', ', $orderby);
			}

			return $this->db->get_results($sql);

		}

		function get_subscriptions($filter = false) {

			if($filter) {

				$where = array();
				$orderby = array();

				if(isset($filter['s'])) {
					$where[] = "sub_name LIKE '%" . mysql_real_escape_string($filter['s']) . "%'";
				}

				if(isset($filter['sub_status'])) {
					switch($filter['sub_status']) {

						case 'active':		$where[] = "sub_active = 1";
											break;
						case 'inactive':	$where[] = "sub_active = 0";
											break;
						case 'public':		$where[] = "sub_public = 1";
											break;
						case 'private':		$where[] = "sub_public = 0";
											break;

					}
				}

				if(isset($filter['order_by'])) {
					switch($filter['order_by']) {

						case 'order_id':	$orderby[] = 'id ASC';
											break;
						case 'order_name':	$orderby[] = 'sub_name ASC';
											break;

					}
				}

			}

			$sql = "SELECT * FROM {$this->subscriptions}";

			if(!empty($where)) {
				$sql .= " WHERE " . implode(' AND ', $where);
			}

			if(!empty($orderby)) {
				$sql .= " ORDER BY " . implode(', ', $orderby);
			}

			return $this->db->get_results($sql);


		}

		function get_subscriptions_and_levels($filter = false) {

			if($filter) {

				$where = array();
				$orderby = array();

				if(isset($filter['s'])) {
					$where[] = "sub_name LIKE '%" . mysql_real_escape_string($filter['s']) . "%'";
				}

				if(isset($filter['sub_status'])) {
					switch($filter['sub_status']) {

						case 'active':		$where[] = "sub_active = 1";
											break;
						case 'inactive':	$where[] = "sub_active = 0";
											break;
						case 'public':		$where[] = "sub_public = 1";
											break;
						case 'private':		$where[] = "sub_public = 0";
											break;

					}
				}

			}

			$sql = "SELECT s.id as sub_id, ml.id as level_id, s.*, ml.*, sl.level_order FROM {$this->subscriptions} AS s, {$this->subscriptions_levels} AS sl, {$this->membership_levels} AS ml";

			if(!empty($where)) {
				$sql .= " WHERE " . implode(' AND ', $where);
			}

			$sql .= " AND s.id = sl.sub_id AND sl.level_id = ml.id ORDER BY s.id ASC, sl.level_order ASC ";

			return $this->db->get_results($sql);

		}

		function count_on_level( $level_id ) {

			$sql = $this->db->prepare( "SELECT count(*) as levelcount FROM {$this->membership_relationships} WHERE level_id = %d AND user_id > 0", $level_id );

			return $this->db->get_var( $sql );

		}

		function count_on_sub( $sub_id ) {

			$sql = $this->db->prepare( "SELECT count(*) as levelcount FROM {$this->membership_relationships} WHERE sub_id = %d AND user_id > 0", $sub_id );

			return $this->db->get_var( $sql );

		}

		// Rewrites
		function add_rewrites($wp_rewrite) {

			global $M_options;

			// This function adds in the api rewrite rules
			// Note the addition of the namespace variable so that we know these are vent based
			// calls
			$new_rules = array();

			if(!empty($M_options['masked_url'])) {
				$new_rules[trailingslashit($M_options['masked_url']) . '(.+)'] = 'index.php?protectedfile=' . $wp_rewrite->preg_index(1);
			}

			$new_rules['paymentreturn/(.+)'] = 'index.php?paymentgateway=' . $wp_rewrite->preg_index(1);

			$new_rules = apply_filters('M_rewrite_rules', $new_rules);

		  	$wp_rewrite->rules = array_merge($new_rules, $wp_rewrite->rules);

			return $wp_rewrite;
		}

		function add_queryvars($vars) {
			if(!in_array('feedkey',$vars)) $vars[] = 'feedkey';
			if(!in_array('protectedfile',$vars)) $vars[] = 'protectedfile';
			if(!in_array('paymentgateway',$vars)) $vars[] = 'paymentgateway';

			return $vars;
		}

		// Profile
		function add_profile_feed_key($profileuser) {

			$id = $profileuser->ID;

			$member = new M_Membership($id);

			if($member->is_member()) {
				$key = get_user_meta($id, '_membership_key', true);

				if(empty($key)) {
					$key = md5($id . $profileuser->user_pass . time());
					update_user_meta($id, '_membership_key', $key);
				}

				?>
				<h3><?php _e('Membership key','membership'); ?></h3>

				<table class="form-table">
				<tr>
					<th><label for="description"><?php _e('Membership key','membership'); ?></label></th>
					<td><?php esc_html_e($key); ?>
						<br />
					<span class="description"><?php _e('This key is used to give you access the the members RSS feed, keep it safe and secret.','membership'); ?></span></td>
				</tr>
				</table>
				<?php
			}


		}

		function update_membershipadmin_capability($user_id) {

			$user = new WP_User( $user_id );

			if(!empty($_POST['membershipadmin']) && $_POST['membershipadmin'] == 'yes') {
				$user->add_cap('membershipadmin');
			} else {
				$user->remove_cap('membershipadmin');
			}

		}

		function add_membershipadmin_capability($profileuser) {

			$id = $profileuser->ID;

			?>
			<h3><?php _e('Membership Administration','membership'); ?></h3>

			<table class="form-table">
			<tr>
				<th><label for="description"><?php _e('Membership Administration','membership'); ?></label></th>
				<td>
				<input type='checkbox' name='membershipadmin' value='yes' <?php if($profileuser->has_cap('membershipadmin')) echo "checked='checked'"; ?>/>
				&nbsp;
				<span class="description"><?php _e('This user has access to administer the Membership system.','membership'); ?></span></td>
			</tr>
			</table>
			<?php


		}

		/* Ping interface */
		function update_subscription_ping_information( $sub_id ) {

			$subscription = new M_Subscription( $sub_id );

			$subscription->update_meta( 'joining_ping', $_POST['joiningping'] );
			$subscription->update_meta( 'leaving_ping', $_POST['leavingping'] );

		}

		function show_subscription_ping_information( $sub_id ) {

			// Get all the pings
			$pings = $this->get_pings();

			// Get the currentlt set ping for each level
			$subscription = new M_Subscription( $sub_id );

			$joinping = $subscription->get_meta( 'joining_ping', '' );
			$leaveping = $subscription->get_meta( 'leaving_ping', '' );

			?>
				<h3><?php _e('Subscription Pings','membership'); ?></h3>
				<p class='description'><?php _e('If you want any pings to be sent when a member joins and/or leaves this subscription then set them below.','membership'); ?></p>

				<div class='sub-details'>

				<label for='joiningping'><?php _e('Joining Ping','membership'); ?></label>
				<select name='joiningping'>
					<option value='' <?php selected($joinping,''); ?>><?php _e('None', 'membership'); ?></option>
					<?php
						if(!empty($pings)) {
							foreach($pings as $ping) {
							?>
							<option value='<?php echo $ping->id; ?>' <?php selected($joinping, $ping->id); ?>><?php echo stripslashes($ping->pingname); ?></option>
							<?php
							}
						}
					?>
				</select><br/>

				<label for='leavingping'><?php _e('Leaving Ping','membership'); ?></label>
				<select name='leavingping'>
					<option value='' <?php selected($leaveping,''); ?>><?php _e('None', 'membership'); ?></option>
					<?php
						if(!empty($pings)) {
							foreach($pings as $ping) {
							?>
							<option value='<?php echo $ping->id; ?>' <?php selected($leaveping, $ping->id); ?>><?php echo stripslashes($ping->pingname); ?></option>
							<?php
							}
						}
					?>
				</select>
				</div>
			<?php
		}

		function update_level_ping_information( $level_id ) {

			$level = new M_Level( $level_id );

			$level->update_meta( 'joining_ping', $_POST['joiningping'] );
			$level->update_meta( 'leaving_ping', $_POST['leavingping'] );

		}

		function show_level_ping_information( $level_id ) {
			// Get all the pings
			$pings = $this->get_pings();

			// Get the currentlt set ping for each level
			$level = new M_Level( $level_id );

			$joinping = $level->get_meta( 'joining_ping', '' );
			$leaveping = $level->get_meta( 'leaving_ping', '' );

			?>
				<h3><?php _e('Level Pings','membership'); ?></h3>
				<p class='description'><?php _e('If you want any pings to be sent when a member joins and/or leaves this level then set them below.','membership'); ?></p>

				<div class='level-details'>

				<label for='joiningping'><?php _e('Joining Ping','membership'); ?></label>
				<select name='joiningping'>
					<option value='' <?php selected($joinping,''); ?>><?php _e('None', 'membership'); ?></option>
					<?php
						if(!empty($pings)) {
							foreach($pings as $ping) {
							?>
							<option value='<?php echo $ping->id; ?>' <?php selected($joinping, $ping->id); ?>><?php echo stripslashes($ping->pingname); ?></option>
							<?php
							}
						}
					?>
				</select><br/>

				<label for='leavingping'><?php _e('Leaving Ping','membership'); ?></label>
				<select name='leavingping'>
					<option value='' <?php selected($leaveping,''); ?>><?php _e('None', 'membership'); ?></option>
					<?php
						if(!empty($pings)) {
							foreach($pings as $ping) {
							?>
							<option value='<?php echo $ping->id; ?>' <?php selected($leaveping, $ping->id); ?>><?php echo stripslashes($ping->pingname); ?></option>
							<?php
							}
						}
					?>
				</select>
				</div>
			<?php

		}

		function handle_gateways_panel_updates() {

			global $action, $page, $M_Gateways;

			wp_reset_vars( array('action', 'page') );

			if(isset($_GET['doaction']) || isset($_GET['doaction2'])) {
				if(addslashes($_GET['action']) == 'toggle' || addslashes($_GET['action2']) == 'toggle') {
					$action = 'bulk-toggle';
				}
			}

			$active = get_option('membership_activated_gateways', array());

			switch(addslashes($action)) {

				case 'deactivate':	$key = addslashes($_GET['gateway']);
									if(!empty($key)) {
										check_admin_referer('toggle-gateway-' . $key);

										$found = array_search($key, $active);
										if($found !== false) {
											unset($active[$found]);
											update_option('membership_activated_gateways', array_unique($active));
											wp_safe_redirect( add_query_arg( 'msg', 5, wp_get_referer() ) );
										} else {
											wp_safe_redirect( add_query_arg( 'msg', 6, wp_get_referer() ) );
										}
									}
									break;

				case 'activate':	$key = addslashes($_GET['gateway']);
									if(!empty($key)) {
										check_admin_referer('toggle-gateway-' . $key);

										if(!in_array($key, $active)) {
											$active[] = $key;
											update_option('membership_activated_gateways', array_unique($active));
											wp_safe_redirect( add_query_arg( 'msg', 3, wp_get_referer() ) );
										} else {
											wp_safe_redirect( add_query_arg( 'msg', 4, wp_get_referer() ) );
										}
									}
									break;

				case 'bulk-toggle':
									check_admin_referer('bulk-gateways');
									foreach($_GET['gatewaycheck'] AS $key) {
										$found = array_search($key, $active);
										if($found !== false) {
											unset($active[$found]);
										} else {
											$active[] = $key;
										}
									}
									update_option('membership_activated_gateways', array_unique($active));
									wp_safe_redirect( add_query_arg( 'msg', 7, wp_get_referer() ) );
									break;

				case 'updated':		$gateway = addslashes($_POST['gateway']);
									check_admin_referer('updated-' . $gateway);
									if($M_Gateways[$gateway]->update()) {
										wp_safe_redirect( add_query_arg( 'msg', 1, 'admin.php?page=' . $page ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 2, 'admin.php?page=' . $page ) );
									}

									break;

			}

		}

		function handle_gateways_panel() {

			global $action, $page, $M_Gateways;

			wp_reset_vars( array('action', 'page') );

			switch(addslashes($action)) {

				case 'edit':	if(isset($M_Gateways[addslashes($_GET['gateway'])])) {
									$M_Gateways[addslashes($_GET['gateway'])]->settings();
								}
								return; // so we don't show the list below
								break;

				case 'transactions':
								if(isset($M_Gateways[addslashes($_GET['gateway'])])) {
									$M_Gateways[addslashes($_GET['gateway'])]->transactions();
								}
								return; // so we don't show the list below
								break;

			}


			$messages = array();
			$messages[1] = __('Gateway updated.', 'membership');
			$messages[2] = __('Gateway not updated.', 'membership');

			$messages[3] = __('Gateway activated.', 'membership');
			$messages[4] = __('Gateway not activated.', 'membership');

			$messages[5] = __('Gateway deactivated.', 'membership');
			$messages[6] = __('Gateway not deactivated.', 'membership');

			$messages[7] = __('Gateway activation toggled.', 'membership');

			?>
			<div class='wrap'>
				<div class="icon32" id="icon-plugins"><br></div>
				<h2><?php _e('Edit Gateways','membership'); ?></h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}

				if($this->show_user_help( $page )) {
					?>
					<div class='screenhelpheader'>
						<a href="admin.php?page=<?php echo $page; ?>&amp;action=removeheader" class="welcome-panel-close"><?php _e('Dismiss','membership'); ?></a>
						<?php
						ob_start();
						include_once(membership_dir('membershipincludes/help/header.gateways.php'));
						echo ob_get_clean();
						?>
					</div>
					<?php
				}

				?>

				<form method="get" action="?page=<?php echo esc_attr($page); ?>" id="posts-filter">

				<input type='hidden' name='page' value='<?php echo esc_attr($page); ?>' />

				<div class="tablenav">

				<div class="alignleft actions">
				<select name="action">
				<option selected="selected" value=""><?php _e('Bulk Actions', 'membership'); ?></option>
				<option value="toggle"><?php _e('Toggle activation', 'membership'); ?></option>
				</select>
				<input type="submit" class="button-secondary action" id="doaction" name="doaction" value="<?php _e('Apply', 'membership'); ?>">

				</div>

				<div class="alignright actions"></div>

				<br class="clear">
				</div>

				<div class="clear"></div>

				<?php
					wp_original_referer_field(true, 'previous'); wp_nonce_field('bulk-gateways');

					$columns = array(	"name"		=>	__('Gateway Name', 'membership'),
										"active"	=>	__('Active','membership')
									);

					$columns = apply_filters('membership_gatewaycolumns', $columns);

					$gateways = get_membership_gateways();

					$active = get_option('membership_activated_gateways', array());

				?>

				<table cellspacing="0" class="widefat fixed">
					<thead>
					<tr>
					<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
					<?php
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
					?>
					</tr>
					</thead>

					<tfoot>
					<tr>
					<th style="" class="manage-column column-cb check-column" scope="col"><input type="checkbox"></th>
					<?php
						reset($columns);
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
					?>
					</tr>
					</tfoot>

					<tbody>
						<?php
						if($gateways) {
							foreach($gateways as $key => $gateway) {
								$default_headers = array(
									                'Name' => 'Addon Name',
													'Author' => 'Author',
													'Description'	=>	'Description',
													'AuthorURI' => 'Author URI',
													'gateway_id' => 'Gateway ID'
									        );

								$gateway_data = get_file_data( membership_dir('membershipincludes/gateways/' . $gateway), $default_headers, 'plugin' );

								if(empty($gateway_data['Name'])) {
									continue;
								}

								?>
								<tr valign="middle" class="alternate" id="gateway-<?php echo $gateway_data['gateway_id']; ?>">
									<th class="check-column" scope="row"><input type="checkbox" value="<?php echo esc_attr($gateway_data['gateway_id']); ?>" name="gatewaycheck[]"></th>
									<td class="column-name">
										<strong><?php echo esc_html($gateway_data['Name']) ?></strong>
										<?php if(!empty($gateway_data['Description'])) {
											?><br/><?php echo esc_html($gateway_data['Description']);
											}

											$actions = array();

											if(in_array($gateway_data['gateway_id'], $active)) {
												$actions['edit'] = "<span class='edit'><a href='?page=" . $page . "&amp;action=edit&amp;gateway=" . $gateway_data['gateway_id'] . "'>" . __('Settings', 'membership') . "</a></span>";
												$actions['transactions'] = "<span class='edit'><a href='?page=" . $page . "&amp;action=transactions&amp;gateway=" . $gateway_data['gateway_id'] . "'>" . __('View transactions','membership') . "</a></span>";
												$actions['toggle'] = "<span class='edit deactivate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=deactivate&amp;gateway=" . $gateway_data['gateway_id'] . "", 'toggle-gateway-' . $gateway_data['gateway_id']) . "'>" . __('Deactivate', 'membership') . "</a></span>";

											} else {
												$actions['toggle'] = "<span class='edit activate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=activate&amp;gateway=" . $gateway_data['gateway_id'] . "", 'toggle-gateway-' . $gateway_data['gateway_id']) . "'>" . __('Activate', 'membership') . "</a></span>";
											}
										?>
										<br><div class="row-actions"><?php echo implode(" | ", $actions); ?></div>
										</td>

									<td class="column-active">
										<?php
											if(in_array($gateway_data['gateway_id'], $active)) {
												echo "<span  class='membershipactivestatus'>" . __('Active', 'membership') . "</span>";
											} else {
												echo "<span  class='membershipinactivestatus'>" . __('Inactive', 'membership') . "</span>";
											}
										?>
									</td>
							    </tr>
								<?php
							}
						} else {
							$columncount = count($columns) + 1;
							?>
							<tr valign="middle" class="alternate" >
								<td colspan="<?php echo $columncount; ?>" scope="row"><?php _e('No Gateways where found for this install.','membership'); ?></td>
						    </tr>
							<?php
						}
						?>

					</tbody>
				</table>


				<div class="tablenav">

				<div class="alignleft actions">
				<select name="action2">
					<option selected="selected" value=""><?php _e('Bulk Actions'); ?></option>
					<option value="toggle"><?php _e('Toggle activation'); ?></option>
				</select>
				<input type="submit" class="button-secondary action" id="doaction2" name="doaction2" value="<?php _e('Apply','membership'); ?>">
				</div>
				<div class="alignright actions"></div>
				<br class="clear">
				</div>

				</form>

			</div> <!-- wrap -->
			<?php
		}

		function get_coupons( $filter = false ) {

			global $blog_id;

			$sql = "SELECT * FROM {$this->coupons}";

			if(!is_network_admin()) {
				// We are on a single site admin interface
				$sql .= $this->db->prepare( " WHERE site_id = %d", $blog_id );
			}

			return $this->db->get_results( $sql );

		}


		function activate_addon( $addon ) {

			$active = get_option('membership_activated_addons', array());

			if(!in_array($addon, $active)) {
				$active[] = $addon;
				update_option('membership_activated_addons', array_unique($active));
			}
		}

		function deactivate_addon( $addon ) {

			$active = get_option('membership_activated_addons', array());

			$found = array_search($addon, $active);
			if($found !== false) {
				unset($active[$found]);
				update_option('membership_activated_addons', array_unique($active));
			}
		}

		// The popover registration functions added to the bottom of this class until a new more suitable home can be found
		function popover_signup_form() {

			$content = '';
			$content = apply_filters('membership_popover_signup_form_before_content', $content );
			ob_start();
			if( defined('MEMBERSHIP_POPOVER_SIGNUP_FORM') && file_exists( MEMBERSHIP_POPOVER_SIGNUP_FORM ) ) {
				include_once( MEMBERSHIP_POPOVER_SIGNUP_FORM );
			} elseif(file_exists( apply_filters('membership_override_popover_signup_form', membership_dir('membershipincludes/includes/popover_signup.form.php')) ) ) {
				include_once( apply_filters('membership_override_popover_signup_form', membership_dir('membershipincludes/includes/popover_signup.form.php')) );
			}
			$content .= ob_get_contents();
			ob_end_clean();

			$content = apply_filters('membership_popover_signup_form_after_content', $content );
			echo $content;

			die();
		}

		function popover_register_process() {

			global $M_options;

			//include_once(ABSPATH . WPINC . '/registration.php');

			$error = new WP_Error();

			if(!wp_verify_nonce( $_POST['nonce'], 'membership_register')) {
				$error->add('invalid', __('Invalid form submission.','membership'));
			}

			if(!validate_username( $_POST['user_login'] )) {
				$error->add('usernamenotvalid', __('The username is not valid, sorry.','membership'));
			}

			if(username_exists(sanitize_user($_POST['user_login']))) {
				$error->add('usernameexists', __('That username is already taken, sorry.','membership'));
			}

			if(!is_email($_POST['email'])) {
				$error->add('emailnotvalid', __('The email address is not valid, sorry.','membership'));
			}

			if(email_exists($_POST['email'])) {
				$error->add('emailexists', __('That email address is already taken, sorry.','membership'));
			}

			$error = apply_filters( 'membership_subscription_form_before_registration_process', $error );

			if(is_wp_error($error)) {
				$anyerrors = $error->get_error_messages();
			} else {
				$anyerrors = array();
			}

			if( empty($anyerrors) ) {
				// Pre - error reporting check for final add user
				$user_id = wp_create_user( sanitize_user($_POST['user_login']), $_POST['password'], $_POST['email'] );

				if(is_wp_error($user_id) && method_exists($user_id, 'get_error_message')) {
					$error->add('userid', $user_id->get_error_message());
				} else {
					$member = new M_Membership( $user_id );
					if(defined('MEMBERSHIP_DEACTIVATE_USER_ON_REGISTRATION') && MEMBERSHIP_DEACTIVATE_USER_ON_REGISTRATION == true) {
						$member->deactivate();
					} else {
						$creds = array(
							'user_login' => $_POST['user_login'],
							'user_password' => $_POST['password'],
							'remember' => true
						);
						$is_ssl = (isset($_SERVER['https']) && strtolower($_SERVER['https']) == 'on' ? true : false);
						$user = wp_signon( $creds, $is_ssl );

						if ( is_wp_error($user) && method_exists($user, 'get_error_message') ) {
							$error->add('userlogin', $user->get_error_message());
						} else {
							// Set the current user up
							wp_set_current_user( $user_id );
						}
					}

					if( has_action('membership_susbcription_form_registration_notification') ) {
						do_action('membership_susbcription_form_registration_notification', $user_id, $_POST['password']);
					} else {
						wp_new_user_notification($user_id, $_POST['password']);
					}

					do_action( 'membership_subscription_form_registration_process', $error, $user_id );
				}
			} else {
				do_action( 'membership_subscription_form_registration_process', $error, 0 );
			}

			$anyerrors = $error->get_error_code();
			if(is_wp_error($error) && !empty($anyerrors)) {
				// we have an error - output
				$messages = $error->get_error_messages();
				//sendback error
				echo json_encode( array('errormsg' => $messages[0]) );
			} else {
				// everything seems fine (so far), so we have our queued user so let's
				// move to picking a subscription - so send back the form.
				echo $this->popover_sendpayment_form( $user_id );
			}

			exit;

		}

		function popover_login_process() {

			$error = new WP_Error();

			if(!wp_verify_nonce( $_POST['nonce'], 'membership_login')) {
				$error->add('invalid', __('Invalid form submission.','membership'));
			}

			$userbylogin = get_user_by( 'login', $_POST['user_login'] );

			if(!empty($userbylogin)) {
				$user = wp_authenticate( $userbylogin->user_login, $_POST['password'] );
				if(is_wp_error($user)) {
					$error->add('userlogin', $user->get_error_message());
				} else {
					wp_set_auth_cookie($user->ID);
					// Set the current user up
					wp_set_current_user( $user->ID );
				}
			} else {
				$error->add('userlogin', __('User not found.','membership') );
			}

			$anyerrors = $error->get_error_code();
			if(is_wp_error($error) && !empty($anyerrors)) {
				// we have an error - output
				$messages = $error->get_error_messages();
				//sendback error
				echo json_encode( array('errormsg' => $messages[0]) );
			} else {
				// everything seems fine (so far), so we have our queued user so let's
				// move to picking a subscription - so send back the form.
				echo $this->popover_sendpayment_form($user->ID);
			}

			exit;

		}
		function popover_extraform_process() {
			echo $this->popover_extra_payment_form();
			exit;
		}
		function popover_sendpayment_form( $user_id = false ) {

			$content = '';
			$content = apply_filters('membership_popover_sendpayment_form_before_content', $content );
			ob_start();
			if( defined('MEMBERSHIP_POPOVER_SENDPAYMENT_FORM') && file_exists( MEMBERSHIP_POPOVER_SENDPAYMENT_FORM ) ) {
				include_once( MEMBERSHIP_POPOVER_SENDPAYMENT_FORM );
			} elseif(file_exists( apply_filters('membership_override_popover_sendpayment_form', membership_dir('membershipincludes/includes/popover_payment.form.php')) ) ) {
				include_once( apply_filters('membership_override_popover_sendpayment_form', membership_dir('membershipincludes/includes/popover_payment.form.php')) );
			}
			$content .= ob_get_contents();
			ob_end_clean();

			$content = apply_filters('membership_popover_sendpayment_form_after_content', $content );
			echo $content;

			exit;
		}

		function popover_extra_payment_form( $user_id = false ) {

			$content = '';
			$content = apply_filters('membership_popover_extraform_before_content', $content );
			ob_start();
			if( defined('MEMBERSHIP_POPOVER_SENDPAYMENT_FORM') && file_exists( MEMBERSHIP_POPOVER_SENDPAYMENT_FORM ) ) {
				include_once( MEMBERSHIP_POPOVER_SENDPAYMENT_FORM );
			} elseif(file_exists( apply_filters('membership_override_popover_sendpayment_form', membership_dir('membershipincludes/includes/popover_payment.form.php')) ) ) {
				include_once( apply_filters('membership_override_popover_sendpayment_form', membership_dir('membershipincludes/includes/popover_payment.form.php')) );
			}
			$content .= ob_get_contents();
			ob_end_clean();

			$content = apply_filters('membership_popover_extraform_after_content', $content );
			echo $content;

			exit;

		}

		function create_defaults() {

			// Function to create some defaults if they are not set

			if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true) {
				if(function_exists('get_blog_option')) {
					if(function_exists('switch_to_blog')) {
						switch_to_blog(MEMBERSHIP_GLOBAL_MAINSITE);
					}

					$M_options = get_blog_option(MEMBERSHIP_GLOBAL_MAINSITE, 'membership_options', array());
				} else {
					$M_options = get_option('membership_options', array());
				}
			} else {
				$M_options = get_option('membership_options', array());
			}

			// Make registration and associated pages
			if(empty($M_options['registration_page'])) {

				// Check if the buddypress registration page is created or not
				if(defined('BP_VERSION') && version_compare( preg_replace('/-.*$/', '', BP_VERSION), "1.5", '>=')) {
					// Get the BP pages
					$bppages = get_option('bp-pages', array());

				}

				$pagedetails = array('post_title' => __('Register', 'membership'), 'post_name' => 'register', 'post_status' => 'publish', 'post_type' => 'page', 'post_content' => '');
				$id = wp_insert_post( $pagedetails );
				$M_options['registration_page'] = $id;

				$pagedetails = array('post_title' => __('Account', 'membership'), 'post_name' => 'account', 'post_status' => 'publish', 'post_type' => 'page', 'post_content' => '');
				$id = wp_insert_post( $pagedetails );
				$M_options['account_page'] = $id;

				$content = '<p>' . __('The content you are trying to access is only available to members. Sorry.','membership') . '</p>';
				$pagedetails = array('post_title' => __('Protected Content', 'membership'), 'post_name' => 'protected', 'post_status' => 'publish', 'post_type' => 'page', 'post_content' => $content);
				$id = wp_insert_post( $pagedetails );
				$M_options['nocontent_page'] = $id;

			}

			// Create relevant admin side shortcodes
			if(empty($M_options['membershipadminshortcodes'])) {
				if(!is_array($M_options['membershipadminshortcodes'])) {
					$M_options['membershipadminshortcodes'] = array();
				}

				if(class_exists('RGForms')) {
					// Gravity Forms exists
					$M_options['membershipadminshortcodes'][] = 'gravityform';
				}

				if(defined('WPCF7_VERSION')) {
					// Contact Form 7 exists
					$M_options['membershipadminshortcodes'][] = 'contact-form';
				}

				if(defined('WPAUDIO_URL')) {
					// WPAudio exists
					$M_options['membershipadminshortcodes'][] = 'wpaudio';
				}
			}

			// Create a default download group
			if(empty($M_options['membershipdownloadgroups'])) {
				if(!is_array($M_options['membershipdownloadgroups'])) {
					$M_options['membershipdownloadgroups'] = array();
				}
				$M_options['membershipdownloadgroups'][] = __('default', 'membership');
			}

			// Create a hashed downloads url
			if(empty($M_options['masked_url'])) {
				$M_options['masked_url'] = __('downloads', 'membership');
			}

			// Update the options
			if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true) {
				if(function_exists('update_blog_option')) {
					update_blog_option(MEMBERSHIP_GLOBAL_MAINSITE, 'membership_options', $M_options);
				} else {
					update_option('membership_options', $M_options);
				}
			} else {
				update_option('membership_options', $M_options);
			}

		}

		// Functions to determine whether to show user help on this screen and to disable it if not
		function show_user_help( $page ) {

			$user_id = get_current_user_id();

			$helpscreens = get_user_meta($user_id, 'membership_show_help_headers', true);

			if(!is_array($helpscreens)) {
				$helpscreens = array();
			}

			if(!isset($helpscreens[$page])) {
				return true;
			} else {
				return false;
			}

		}

		function dismiss_user_help( $page ) {

			$user_id = get_current_user_id();

			$helpscreens = get_user_meta($user_id, 'membership_show_help_headers', true);

			if(!is_array($helpscreens)) {
				$helpscreens = array();
			}

			if(!isset($helpscreens[$page])) {
				$helpscreens[$page] = 'no';
			}

			update_user_meta( $user_id, 'membership_show_help_headers', $helpscreens );
		}

		// Level shortcodes function
		function build_level_shortcode_list( $shortcodes = array() ) {

			if(!is_array($shortcodes)) {
				$shortcodes = array();
			}

			$levels = $this->get_membership_levels();

			if(!empty($levels)) {
				foreach($levels as $level) {
					$shortcodes[] = M_normalize_shortcode($level->level_title);
				}
			}

			return $shortcodes;

		}

		function start_membership_session() {
			if (session_id() == "")
      			session_start();
		}


		// Remove relationship when user is deleted
		function remove_relationships_on_delete( $user_id ) {
			global $wpdb;
			
			$sql = $wpdb->prepare( "DELETE FROM ".$wpdb->prefix."m_membership_relationships WHERE user_id = %d", $user_id );
			$wpdb->query( $sql );
		}



	}

}

?>