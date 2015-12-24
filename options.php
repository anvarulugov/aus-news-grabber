<?php 
/**
 * AU Slim Options
 *
 * Plugin Options page generator class
 * Uses Wordpress default form html style
 *
 * @link http://codex.wordpress.org/Creating_Options_Pages
 *
 * @package WordPress
 * @subpackage AU Slim Options
 * @since AU Slim Options 0.1
 */

class AUSNGOptions {

	private $plugin_name;
	private $plugin_slug;
	private $options;
	private $settings;
	private $last_grabb;
	private $grabbers;

	function __construct( $grabbers ) {

		$this->init();
		add_action( 'admin_menu', array( $this, 'create_menu_page' ) );
		add_action( 'admin_init', array( $this, 'initialize_plugin_options' ) );

		// Add ajax action to save channels
		add_action('wp_ajax_' . $this->plugin_slug . '_channel_add', array( $this, 'channel_add' ) );
		add_action('wp_ajax_nopriv_' . $this->plugin_slug . '_channel_add', array( $this, 'channel_add' ) );
		// Add ajax action to delete channels
		add_action('wp_ajax_' . $this->plugin_slug . '_channel_del', array( $this, 'channel_del' ) );
		add_action('wp_ajax_nopriv_' . $this->plugin_slug . '_channel_del', array( $this, 'channel_del' ) );
		// Load plugin language files
		add_action( 'plugins_loaded', array( $this, 'language' ) );

		$this->grabbers = $grabbers;
	
	}

	function init() {

		$this->plugin_name = 'AUS News Grabber';
		$this->plugin_slug = 'aus_news_grabber';
		
		if( ! get_option( $this->plugin_slug . '_plugin_options' ) ) {
			add_option( $this->plugin_slug . '_plugin_options' );
		}
		$this->options = get_option( $this->plugin_slug . '_plugin_options' );

		if( ! get_option( $this->plugin_slug . '_plugin_settings' ) ) {
			$default_settings = array(
				'grabb_period' => 'hourly',
				'grabber_author_default' => 1,
				'post_status_default' => 'pending',
				'default_thumb' => '',
				'source_template' => '{source_url}',
			);
			add_option( $this->plugin_slug . '_plugin_settings', $default_settings );
		}
		$this->settings = get_option( $this->plugin_slug . '_plugin_settings' );

		if( ! get_option( $this->plugin_slug . '_plugin_last_grabb' ) ) {
			add_option( $this->plugin_slug . '_plugin_last_grabb', date( 'Y-m-d H:i:s' ) );
		}
		$this->last_grabb = get_option( $this->plugin_slug . '_plugin_last_grabb' );

	}

	/**
	 * Register Menu items
	 */

	public function create_menu_page() {

		/*
		// This page will be under "Settings"
		add_options_page(
			$this->plugin_name . ' plugin options', 
			$this->plugin_name, 
			'manage_options', 
			$this->plugin_slug . '_plugin',
			array( $this, 'plugin_options_display' )
		);
		*/

		add_menu_page(
			sprintf( __( '%s plugin options', 'aus-grabber' ), $this->plugin_name ), // The Title to be displayed on corresponding page for this menu
			'AUS Grabber', // The Text to be displayed for this actual menu item
			'administrator', // Which type of users can see this menu
			$this->plugin_slug . '_plugin', // The unique ID - that is, the slug - for this menu item
			array( $this, 'menu_page_display' ),
			''
		);

		add_submenu_page(
			$this->plugin_slug . '_plugin', // Register this submenu with the menu defined above
			$this->plugin_name . ' Plugin Options', // The text to the display in the browser when this menu item is active
			__( 'Channels', 'aus-grabber' ), // The text for this menu item
			'administrator', // Which type of users can see this menu
			$this->plugin_slug . '_plugin_options', // The unique ID - the slug - for this menu item
			array( $this, 'plugin_options_display' ) // The function used to render the menu for this page to the screen
		);

	}

	/**
	 * Main menu page display
	 */

	public function menu_page_display() {
		?>

		<?php $this->scripts(); ?>
		<div class="wrap">
			<h2><?php echo sprintf( __( '%s settings'), $this->plugin_name ); ?></h2>

			<form method="post" action="options.php">
			
			<input type="hidden" name="nonce" id="nonce" value="<?php echo wp_create_nonce( $this->plugin_slug . '_channel_add' ); ?>">

			<input type="hidden" name="referer" id="referer" value="<?php echo $_SERVER['REQUEST_URI']; ?>">

			<?php settings_fields( $this->plugin_slug . '_plugin_settings_group' ); ?>
			<?php do_settings_sections( $this->plugin_slug . '_plugin_settings' ); ?>
			<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * #1 submenu page display
	 */

	public function plugin_options_display() {
		?>
		<?php $this->scripts(); ?>
		<div class="wrap">
			<h2><?php echo sprintf( __( '%s channels'), $this->plugin_name ); ?></h2>
			<form id="<?php echo $this->plugin_slug; ?>-add-form" method="post" action="">
			
			<input type="hidden" name="nonce" id="nonce" value="<?php echo wp_create_nonce( $this->plugin_slug . '_channel_add' ); ?>">

			<input type="hidden" name="referer" id="referer" value="<?php echo $_SERVER['REQUEST_URI']; ?>">

			<?php //settings_fields( $this->plugin_slug . '_plugin_options_group' ); ?>
			<?php do_settings_sections( $this->plugin_slug . '_plugin_options' ); ?>
			<a id="<?php echo $this->plugin_slug; ?>-add-channel" class="button button-primary" href="#"><?php _e( 'Add Channel', 'aus-grabber' ); ?></a>
			</form>
			<br /><br />
			<table class="wp-list-table widefat fixed tags aus-channels-table">
				<thead>
					<tr>
						<th class="aus-channels-table-col1">#</th>
						<th  class="aus-channels-table-col2">
							<?php _e( 'Grabber', 'aus-grabber' ); ?>
						</th>
						<th class="aus-channels-table-col3">
							<?php _e( 'Category', 'aus-grabber' ); ?>
						</th>
						<th class="aus-channels-table-col3">
							<?php _e( 'Author', 'aus-grabber' ); ?>
						</th>
						<th class="aus-channels-table-col4">
							<?php _e( 'RSS URL', 'aus-grabber' ); ?>
						</th>
						<th class="aus-channels-table-col5">
							<?php _e( 'Delete', 'aus-grabber' ); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $this->options ) ) : ?>
					<?php $channel_del_nonce = wp_create_nonce( $this->plugin_slug . '_channel_del' ); ?>
					<div id="channel_del_nonce" style="display:none;"><?php echo $channel_del_nonce; ?></div>
					<?php $i=1; foreach ( $this->options as $option ) : ?>
						<tr>
							<td class="aus-channels-table-col1"><?php echo $i++; ?></td>
							<td class="aus-channels-table-col2">
								<?php echo $option['grabber']; ?>
							</td>
							<td class="aus-channels-table-col3">
								<?php echo get_cat_name( $option['grabber_cat'] ); ?>
							</td>
							<td class="aus-channels-table-col3">
								<?php echo get_user_by( 'id', $option['grabber_author'] )->display_name; ?>
							</td>
							<td class="aus-channels-table-col4">
								<?php echo $option['rss_url']; ?>
							</td>
							<td class="aus-channels-table-col5">
								<a class="<?php echo $this->plugin_slug . '-del-channel'; ?>" data-nonce="<?php echo $channel_del_nonce; ?>" data-rand_id="<?php echo $option['rand_id']; ?>" id="" href="#<?php echo $option['rand_id']; ?>"><?php _e( 'Delete', 'aus-grabber' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php 
	}

	/**
	 * Initialize plugin options
	 */

	public function initialize_plugin_options() {

		// First, we register a section. This is necessary since all 
		// future options must belong a section.
		add_settings_section(
			$this->plugin_slug . '_plugin_settings_section', // ID that used to identify this section and whith wich to register options
			'Plugin Settings', // Title to be displayed on the administration page
			array( $this, 'plugin_general_options_ballback' ), // Call back used to render the description of the section
			$this->plugin_slug . '_plugin_settings' // Page on which to add this section of options
		);

		// Next, we'll introduce the fields11
		add_settings_field(
			'grabb_period',
			__( 'Check every', 'aus-grabber' ),
			array( $this, 'input'),
			$this->plugin_slug . '_plugin_settings',
			$this->plugin_slug . '_plugin_settings_section',
			array(
				'id' => 'grabb_period',
				'type' => 'select',
				'description' => __( 'Please, select period of channel checking', 'aus-grabber' ),
				'options' => array( 
					'hourly'=>'Once Hourly',
					'twicedaily' => 'Twice Daily', 
					'daily' => 'Once Daily', 
					),
				'group' => $this->plugin_slug . '_plugin_settings',
			)
		);

		add_settings_field(
			'grabber_author_default',
			__( 'Default author', 'aus-grabber' ),
			array( $this, 'input'),
			$this->plugin_slug . '_plugin_settings',
			$this->plugin_slug . '_plugin_settings_section',
			array(
				'id' => 'grabber_author_default',
				'type' => 'authors',
				'description' => __( 'Please, select grabbed news default author', 'aus-grabber' ),
				'group' => $this->plugin_slug . '_plugin_settings',
			)
		);

		add_settings_field(
			'post_status_default',
			__( 'Default post status', 'aus-grabber' ),
			array( $this, 'input'),
			$this->plugin_slug . '_plugin_settings',
			$this->plugin_slug . '_plugin_settings_section',
			array(
				'id' => 'post_status_default',
				'type' => 'select',
				'options' => array(
					'pending' => __( 'Pending' ),
					'publish' => __( 'Published' ),
					'draft' => __( 'Draft' ),
				),
				'description' => __( 'Please, select grabbed news default post status', 'aus-grabber' ),
				'group' => $this->plugin_slug . '_plugin_settings',
			)
		);

		//
		add_settings_field(
			'default_thumb', // ID to identify the field throughout the plugin 
			__( 'Default thubmnail', 'aus-grabber' ), // The label to the left of the option interface
			array( $this, 'input'), // The name of the function responsible for rendering the option interface
			$this->plugin_slug . '_plugin_settings', // The page on which this option will be displayed
			$this->plugin_slug . '_plugin_settings_section', // The name of the section to which this field belongs
			array(
				'id' => 'default_thumb',
				'type' => 'text',
				'description' => __( 'Please, enter default thubmnail URL', 'aus-grabber' ),
				'group' => $this->plugin_slug . '_plugin_settings',
				'atts' => array( 'style' => 'width:auto;')
			) // The array of arguments to pass to the callback function.
		);
		add_settings_field(
			'show_source', // ID to identify the field throughout the plugin 
			__( 'Show news source', 'aus-grabber' ), // The label to the left of the option interface
			array( $this, 'input'), // The name of the function responsible for rendering the option interface
			$this->plugin_slug . '_plugin_settings', // The page on which this option will be displayed
			$this->plugin_slug . '_plugin_settings_section', // The name of the section to which this field belongs
			array(
				'id' => 'show_source',
				'type' => 'checkbox',
				'title' => 'Show source',
				'description' => __( 'Show news source', 'aus-grabber' ),
				'group' => $this->plugin_slug . '_plugin_settings',
			) // The array of arguments to pass to the callback function.
		);
		add_settings_field(
			'source_template',
			__( 'Source template', 'aus-grabber' ),
			array( $this, 'input'),
			$this->plugin_slug . '_plugin_settings',
			$this->plugin_slug . '_plugin_settings_section',
			array(
				'id' => 'source_template',
				'type' => 'textarea',
				'title' => 'Source template',
				'default' => '{source_url}',
				'description' => __( 'Source template. Use {source_url} tag to display the link to original content.', 'aus-grabber' ),
				'editor' => array('textarea_name'=>$this->plugin_slug . '_plugin_settings[source_template]','teeny'=>true,'textarea_rows'=>4),
			)
		);

		// Channels options
		add_settings_section(
			$this->plugin_slug . '_plugin_channels_settings_section', // ID that used to identify this section and whith wich to register options
			'Plugin Options', // Title to be displayed on the administration page
			array( $this, 'plugin_general_options_ballback'), // Call back used to render the description of the section
			$this->plugin_slug . '_plugin_options' // Page on which to add this section of options
		);

		// Next, we'll introduce the fields11
		add_settings_field(
			'grabber',
			__( 'Grabber class', 'aus-grabber' ),
			array( $this, 'input'),
			$this->plugin_slug . '_plugin_options',
			$this->plugin_slug . '_plugin_channels_settings_section',
			array(
				'id' => 'grabber',
				'type' => 'select',
				'description' => __( 'Please, select grabber class', 'aus-grabber' ),
				'options' => $this->grabbers,
			)
		);

		add_settings_field(
			'grabber_cat',
			__( 'Grabbed news category', 'aus-grabber' ),
			array( $this, 'input'),
			$this->plugin_slug . '_plugin_options',
			$this->plugin_slug . '_plugin_channels_settings_section',
			array(
				'id' => 'grabber_cat',
				'type' => 'categories',
				'description' => __( 'Please, select grabbed news category', 'aus-grabber' ),
			)
		);

		add_settings_field(
			'grabber_author',
			__( 'Grabbed news author', 'aus-grabber' ),
			array( $this, 'input'),
			$this->plugin_slug . '_plugin_options',
			$this->plugin_slug . '_plugin_channels_settings_section',
			array(
				'id' => 'grabber_author',
				'type' => 'authors',
				'default' => $this->settings['grabber_author_default'],
				'description' => __( 'Please, select grabbed news author', 'aus-grabber' ),
			)
		);
		
		add_settings_field(
			'rss_url', // ID to identify the field throughout the plugin 
			__( 'RSS URL', 'aus-grabber' ), // The label to the left of the option interface
			array( $this, 'input'), // The name of the function responsible for rendering the option interface
			$this->plugin_slug . '_plugin_options', // The page on which this option will be displayed
			$this->plugin_slug . '_plugin_channels_settings_section', // The name of the section to which this field belongs
			array(
				'id' => 'rss_url',
				'type' => 'text',
				'description' => __( 'Please, enter RSS URL', 'aus-grabber' ),
				'atts' => array( 'style' => 'width:auto;')
			) // The array of arguments to pass to the callback function.
		);
	
		register_setting(
			$this->plugin_slug . '_plugin_settings_group',
			$this->plugin_slug . '_plugin_settings',
			array( $this, 'senitize_settings' )
		);

		register_setting(
			$this->plugin_slug . '_plugin_options_group',
			$this->plugin_slug . '_plugin_options',
			array( $this, 'senitize_options' )
		);

	}

	public function senitize_settings( $input ) {

		//if ( $this->settings['grabb_period'] <> $input['grabb_period'] )
		//	wp_clear_scheduled_hook('aus_news_grabber_reccurence');
		return $input;

	}

	public function senitize_options( $input ) {

		$output = array();
		$i = 0;
		foreach ( $input as $option_key => $option_val ) {
			$i++;
			foreach ( $option_val as $key => $value ) {
				switch ( $key ) {
					case 'grabber':
						$output[ $i ][ $key ] = sanitize_text_field( $value );
						break;
					case 'grabber_cat':
						$output[ $i ][ $key ] = absint( $value );
						break;
					case 'grabber_author':
						$output[ $i ][ $key ] = absint( $value );
						break;
					case 'rss_url':
						$output[ $i ][ $key ] = esc_url( $value );
						break;
					case 'rand_id':
						$output[ $i ][ $key ] = absint( $value );
						break;
				}
			}
		}

		return $output;

	}

	public function _esc_attr( $key, $type ) {
		if ( isset( $this->options[ $key ] ) ) {
			return $this->options[ $key ];
		} elseif ( isset( $this->settings[ $key ] ) ) {
			return $this->settings[ $key ];
		} else {
			return false;
		}
	}

	public function channel_add() {

		if ( isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], $this->plugin_slug . '_channel_add') ) {

			$data = $_POST['aus_news_grabber_plugin_options'];
			$data['rand_id'] = rand(0,9999);

			if ( isset( $data['rss_url'] ) && ! empty( $data['rss_url'] ) ) {
				if ( $this->options ) {
					$options = array_merge( $this->options, array( $data ) );
				} else {
					$options = array( $data );
				}

				update_option( $this->plugin_slug . '_plugin_options', $options );

				$channel = array(
					'grabber' => $data['grabber'],
					'grabber_cat' => get_cat_name( $data['grabber_cat'] ),
					'grabber_author' => get_user_by( 'id', $data['grabber_author'] )->display_name,
					'rss_url' => $data['rss_url'],
					'rand_id' => $data['rand_id'],
				);

				echo $message = json_encode( array( 'error'=>0, 'message'=>__( 'New channel added', 'aus-grabber' ), 'channel'=>$channel, ) );
			} else {
				echo $message = json_encode( array( 'error'=>1, 'message'=>__( 'Please, enter RSS URL', $this->plugin_slug ) ) );
			}

		} else {
			echo $message = json_encode( array( 'error'=>1, 'message'=>__( 'No naughty business please', $this->plugin_slug ) ) );
			exit;
		}

		exit;

	}

	public function channel_del() {

		if ( isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], $this->plugin_slug . '_channel_del') ) {


			if ( isset( $_POST['rand_id'] ) && ! empty( $_POST['rand_id'] ) ) {
				$rand_id = $_POST['rand_id'];
				
				foreach ( $this->options as $option_key => $option_val ) {
					if ( in_array( $rand_id, $option_val ) ) {
						unset( $this->options[ $option_key ] );
						update_option( $this->plugin_slug . '_plugin_options', $this->options );
					}
				}
				
			} else {
				echo $message = json_encode( array( 'error'=>1, 'message'=>__( 'Channel not set', $this->plugin_slug ) ) );
			}

			echo $message = json_encode( array( 'error'=>0, 'message'=> $_POST ) );

		} else {
			echo $message = json_encode( array( 'error'=>1, 'message'=>__( 'No naughty business please', $this->plugin_slug ) ) );
			exit;
		}

		exit;

	}

	/**
	 * Initialize plugin options callbacks
	 */
	public function plugin_general_options_ballback() {

		$html = '<h4>General Options</h4>';
		//echo $html;

	}

	public function input( $args ) {

		$defaults = array(
			'id' => '',
			'type' => '',
			'title' => '',
			'description' => '',
			'default' => '',
			'options' => array(),
			'group' => $this->plugin_slug . '_plugin_options',
			'editor' => array(
				'teeny'=>true,
				'textarea_rows'=>4,
				'textarea_name'=>$this->plugin_slug . '_plugin_options'. '[' .$id . ']',
			),
			'atts' => array(),
		);
		extract( $defaults, EXTR_OVERWRITE );
		extract( $args, EXTR_OVERWRITE );

		if ( isset( $atts ) && ! empty( $atts ) ) {
			foreach ( $atts as $attribute => $attr_value ) {
				$attributes .= $attribute . '="' . $attr_value . '"';
			}
		}

		$value = $this->_esc_attr( $id, $type );
		if ( $value === false )
			$value = $default;

		switch ( $type ) {

			case 'radio':
				$input = '<fieldset>';
				foreach ( $options as $key => $option ) {
					$input .= '<label title="' . $option . '">';
					$input .= '<input type="radio" name="' . $group . '[' .$id . ']" value="' . $key . '" ' . ( $value == $key ? 'checked="checked"' : '' ) . ' />';
					$input .= '<span>' . $option . '</span>';
					$input .= '</label><br />';
				}
				$input .= '</fieldset>';
				break;
			case 'textarea':
				ob_start();
				wp_editor( $value, $id, $editor );
				$input = ob_get_contents();
				ob_end_clean();
						break;
			case 'select':
				$input  = '<select name="' . $group . '[' .$id . ']" id="' .$id . '" ' . $attributes . '>';
				foreach ( $options as $key => $option ) {
					$input .= '<option ' . selected( $key, $value, false ) . ' value="'. $key .'">' . $option . '</option>';
				}
				$input .= '</select>';
				break;

			case 'categories':
			case 'cats':
				$input = '<select name="' . $group . '[' .$id . ']" id="' .$id . '" ' . $attributes . '>';
				foreach ( get_categories( array( 'hide_empty' => false ) ) as $cat ) {
					$input .= '<option ' . selected( $key, $value, false ) . ' value="'. $cat->cat_ID .'">' . $cat->cat_name . '</option>';
				}
				$input .= '</select>';
				break;

			case 'authors':
				$input = '<select name="' . $group . '[' .$id . ']" id="' .$id . '" ' . $attributes . '>';
				foreach ( get_users() as $user ) {
					$input .= '<option ' . selected( $user->ID, $value, false ) . ' value="'. $user->ID .'">' . $user->display_name . '</option>';
				}
				$input .= '</select>';
				break;

			case 'checkbox':
				$input = '<fieldset>';
				$input .= '<label title="' . $id . '">';
				$input .= '<input name="' . $group . '[' .$id . ']" id="' .$id . '" type="' .$type . '" value="1"' . $attributes  . ( $value ? 'checked="checked"' : '' ) . ' />';
				$input .= $title;
				$input .= '</label>';
				$input .= '</fieldset>';
				break;

			case 'hidden':
				$input = '<input name="' . $group . '[' .$id . ']" id="' .$id . '" type="' .$type . '" value="' . $value . '"' . $attributes . ' />';
				break;

			case 'email':
			case 'text':
			default:
				$input = '<input name="' . $group . '[' .$id . ']" id="' .$id . '" type="' .$type . '" value="' . $value . '"' . $attributes . ' />';
				break;

		}

		$html  = '';
		$html .= $input;
		if ( ! empty( $description ) )
			$html .= '<p class="description">' . $description . '</p>';
		echo $html;
	}

	/**
	 * Enqueue plugin scripts
	 */
	public function scripts() {

		echo '
		<script>
		function home_url() {
			return "' . home_url() . '";
		}
		</script>
		';
		wp_enqueue_style( 'aus-news-grabber', AUSNG_URL . '/css/styles.css', array(), '0.0.1' );
		wp_enqueue_script( 'aus-news-grabber', AUSNG_URL . '/js/scripts.js', array( 'jquery' ), '0.0.1', true );

	}

	/**
	 * Set plugin text domain
	 */
	public function language() {
		load_plugin_textdomain( $this->plugin_slug, false,  AUSNG_DIR . '/languages/' );
	}



}