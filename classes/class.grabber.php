<?php

abstract class Grabber extends AUSNewsGrabber {

	private $cache_dir;
	private $cache_time;
	private $cdata;

	function __construct() {

	}

	function init() {

		$this->cache_dir = '../cache';
		$cache_time = 3600;
		$this->cdata = 'strip';

	}

	abstract function posts();
	abstract function pattern( $pcontent, $title );

	public function phpquery( $url ) {

		$page = file_get_contents( $url );
		return phpQuery::newDocument($page);

	}

	public function rss_items( $url ) {

		$rss = new lastRSS;
		$rss->cache_dir = $this->cache_dir;
		$rss->cache_time = $this->cache_time;
		$rss->cdata = $this->cdata;
		if ( $rs = $rss->get( $url ) ) {
			// here we can work with RSS fields
			return $rs['items'];
		} else {
			return FALSE;
		}

	}

	public function get_pages( $channel ) {

		$i = 0;
		$posts = array();
		if( ! $channel['last_date'] )
			$channel['last_date'] = '1999-01-01 00:00:00';

		foreach ( $this->rss_items( $channel['rss_url'] ) as $feed) {
			$i++;
			if( $channel['last_date'] < date( 'Y-m-d H:i:s',strtotime( $feed['pubDate'] ) ) )
				$single = $this->single( $feed['title'], $feed['link'], $feed['pubDate'] );
			if ( $single ) {
				$posts[] = $single;
			}
			// if( $i==1 ) break;
		}

		$posts = array_reverse( $posts );

		return $posts;

	}

	public function single( $title, $url, $date ) {

		$data = date( 'Y-m-d H:i:s', strtotime( $date ) );
		$post_exists = $this->post_exists( (string)$title, (string)$data );

		if ( ! $post_exists ) {
			$pcontent = $this->phpquery( $url );
			$news = $this->pattern( $pcontent, $title );
		} else {
			return FALSE;
		}
		

		$post = array();
		$post['post_title'] = ( $news['title'] ? $news['title'] : $title );
		$post['post_content'] = $news['content'];
		$post['post_thumbnail'] = $news['image'];
		$post['post_date'] = date('Y-m-d H:i:s', strtotime($date));
		$post['post_source_url'] = $url;
		if ( isset( $news['tags'] ) && ! empty( $news['tags'] )) {
			$post['tags'] = $news['tags'];
		} else {
			$post['tags'] = '';
		}

		return $post;

	}

	public function image_upload( $image_url ) {

		$filename = basename( $image_url );
		$attachment = $this->attachment_exists( $filename );

		if ( ! $attachment ) {
			$upload_dir = wp_upload_dir();
			$image_data = @file_get_contents( $image_url );
			if( wp_mkdir_p( $upload_dir['path'] ) )
			    $file = $upload_dir['path'] . '/' . $filename;
			else
			    $file = $upload_dir['basedir'] . '/' . $filename;
			@file_put_contents( $file, $image_data );

			$wp_filetype = wp_check_filetype( $filename, null );
			$attachment = array(
			    'post_mime_type'=> $wp_filetype['type'],
			    'post_title'	=> sanitize_file_name( $filename ),
			    'guid'			=> $upload_dir['url'] . '/' . $filename,
			    'post_content'	=> '',
			    'post_status'	=> 'inherit'
			);
			$attach_id = wp_insert_attachment( $attachment, $file );
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
			wp_update_attachment_metadata( $attach_id, $attach_data );
			set_post_thumbnail( $post_id, $attach_id );
			$image_url = wp_get_attachment_url( $attach_id );
			$image = array( 'id'=>$attach_id, 'url'=>$image_url );
			return $image;
		} else {
			return $attachment;
		}

	}

	private function attachment_exists( $file_name ) {

		global $wpdb;
		$post = $wpdb->get_results("SELECT ID, guid FROM $wpdb->posts WHERE post_title='" . sanitize_file_name( $file_name ) . "' AND post_type='attachment' LIMIT 1", 'ARRAY_N');
		if( empty( $post ) ) {
			return FALSE;
		} else {
			$image = array(
				'id' => $post[0][0],
				'url' => $post[0][1],
			);
			return $image;
		}
	}

}