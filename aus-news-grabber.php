<?php 
/*
Plugin Name: AUS News Grabber
Plugin URI: http://wp.ulugov.uz/
Description: Grabbers news from selected rss channel. Includes widget view, single post and category view
Version: 1.0.0
Author: Anvar Ulugov
Author URI: http://ulugov.uz/
*/

defined( 'ABSPATH' ) or die( "No script kiddies please!" );

/*
 * Define plugin absolute path and url
 */
define( 'AUSNG_DIR', rtrim( plugin_dir_path( __FILE__ ), '/' ) );
define( 'AUSNG_URL', rtrim( plugin_dir_url( __FILE__ ), '/' ) );

include( AUSNG_DIR . '/options.php' );
include( AUSNG_DIR . '/vendors/phpQuery.php' );
include( AUSNG_DIR . '/vendors/lastRSS.php' );
include( AUSNG_DIR . '/classes/class.grabber.php' );

class AUSNewsGrabber {

	private $options;
	private $settings;
	private $last_grabb;
	private $grabbers;
	private $grabbers_names;

	function __construct() {

		$this->init();
		// Load Grabber classes automatically
		$this->autoload_classes();

		// Activate Plugin Options Class
		$AUSNGOptions = new AUSNGOptions( $this->grabbers_names );

		// Add action to schedule grabb_news function
		add_action( 'aus_grabb_schedule', array( $this, 'grabb_news') );
		$this->grabb_news_scheduler();

		// Check if changed the recurrence of scheduled event. If yes unschedule the current and schedule new one.
		$recurrence = wp_get_schedule( 'aus_grabb_schedule' );

		if ( $recurrence != $this->settings['grabb_period'] ) {
			wp_clear_scheduled_hook( 'aus_grabb_schedule' );
			wp_schedule_event( time(), $this->settings['grabb_period'], 'aus_grabb_schedule' );
		}


		if( $this->settings['show_source'] )
			add_filter( 'the_content', array( $this, 'show_source' ) );
	}

	/*
	 * Class init function
	 */
	public function init() {

		$this->options = get_option( 'aus_news_grabber_plugin_options' );
		$this->settings = get_option( 'aus_news_grabber_plugin_settings' );
		$this->last_grabb = get_option( 'aus_news_grabber_plugin_last_grabb' );

	}

	public function autoload_classes() {

		foreach ( glob( AUSNG_DIR . '/grabbers/class.*.php' ) as $grabber) {
			if ( file_exists( $grabber ) ) {
				include( $grabber );
				$classfile = basename( $grabber, '.php' );
				$classname = str_replace( array( 'class.', '.php' ), array( '', '' ), $classfile);
				$this->set_grabber( $classname, ucfirst( $classname ) );
			}
		}

	}

	public function set_grabber( $name, $class ) {

		$this->grabbers_names[ $name ] = $class;

	}

	public function get_last_date( $cat_id ) {

		$latest_post = new WP_Query( array( 
			'posts_per_page'=>1, 
			'category__in'=>array($cat_id)
		) );

		if( $latest_post->have_posts() ) {
			return $latest_post->posts[0]->post_date;
		} else {
			return FALSE;
		}

	}

	public function insert_posts( $channel ) {

		$wp_error = true;

		$grabber_args = array(
			'last_date' => $this->get_last_date( $channel['grabber_cat'] ),
			'cat_id' => $channel['grabber_cat'],
			'rss_url' => $channel['rss_url'],
		);
		$grabber_class = ucfirst( $channel['grabber'] );

		$posts = new $grabber_class( $grabber_args );

		if ( $posts->posts() ) {
			foreach ( $posts->posts() as $news ) {
				$post = array(
					'post_title' => $news['post_title'],
					'post_date' => $news['post_date'],
					'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $news['post_date'] ),
					'post_author' => $channel['grabber_author'],
					'post_type' => 'post',
					'post_status' => 'pending',
					'post_category' => array( $channel['grabber_cat'] ),
				);
				$source = $news['post_source_url'];
				$post_exists = $this->post_exists( (string)$news['post_title'], (string)$news['post_date'] );
				if( ! $post_exists ) {
					$post_id = wp_insert_post( $post, $wp_error );

					if ( ! empty( $news['tags'] ) && is_array( $news['tags'] ) ) {
						wp_set_post_tags( $post_id, $news['tags'] );
					}

					if ( ! empty( $news['image'] ) ) {
						$thumbnail = update_post_meta( $post_id, '_thumbnail_id', $news['image'] );
					} else {
						$thumbnail = array(
							'src' => $this->settings['default_thumb'],
							'id' => '',
						);
					}
					if( $thumbnail ) {
						wp_update_post( array(
							'ID'=>$post_id,
							'post_content'=>$news['post_content'],
							'post_status' => $this->settings['post_status_default'],
						) );
					}
					add_post_meta( $post_id, 'source', $source );
				} else {
					$post_id = $post_exists['ID'];
					if ( ! empty( $news['image'] ) ) {
						$thumbnail = update_post_meta( $post_id, '_thumbnail_id', $news['image'] );
					} else {
						$thumbnail = array(
							'src' => $this->settings['default_thumb'],
							'id' => '',
						);
					}
					if( $thumbnail ) {
						wp_update_post( array( 
							'ID'=>$post_id, 
							'post_content'=>$news['post_content'] 
						) );
						update_post_meta( $post_id, 'source', $source );
					}
				}
			}
			return TRUE;
		} else {
			return FALSE;
		}

	}

	public function show_source( $content ) {

		global $post;
		$source_url = get_post_meta( $post->ID, 'source', true );
		
		$template = $this->settings['source_template'];
		$template = str_replace( '{source_url}', $source_url, $template );

		if( ! is_feed() && ! is_home() && is_singular() && is_main_query() ) {
			$content .= $template;
		}
		
		return $content;

	}

	public function post_exists( $post_title, $post_date ) {

		global $wpdb;
		$post = $wpdb->get_results( "SELECT post_title FROM $wpdb->posts WHERE post_title='" . $post_title . "' AND post_date='" . date( 'Y-m-d H:i:s', strtotime( $post_date ) ) . "' AND post_type='post' LIMIT 1", 'ARRAY_N' );
		if( empty( $post ) || count( $post_date ) <= 0 )
			return FALSE;
		else
			return $post;
	}

	public function grabb_news() {

		if( is_array( $this->options ) && ! empty( $this->options ) ) {

			foreach ( $this->options as $channel ) {
				$this->insert_posts( $channel );
			}

			update_option( 'aus_news_grabber_plugin_last_grabb', date( 'Y-m-d H:i:s' ) );

		}
		
	}

	public function grabb_news_scheduler() {

		if ( ! wp_next_scheduled( 'aus_grabb_schedule' ) ) {

			if ( ! isset( $this->settings['grabb_period'] ) or $this->settings['grabb_period'] == '' ) {
				$this->settings['grabb_period'] = 'hourly';
			}

			wp_schedule_event( time(), $this->settings['grabb_period'], 'aus_grabb_schedule' );

		}

	}

}

$AUSNewsGrabber = new AUSNewsGrabber();