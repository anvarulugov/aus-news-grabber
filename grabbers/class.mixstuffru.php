<?php 

class Mixstuffru extends Grabber {

	private $channel;

	public function __construct( $channel = array() ) {
		parent::__construct();
		$this->channel = $channel;

	}

	public function posts() {

		$pages = $this->get_pages( $this->channel );
		if ( ! empty( $pages ) ) {
			return $pages;
		} else {
			return FALSE;
		}

	}

	public function pattern( $pcontent, $title ) {

		$hentry = pq( $pcontent )->find( '.entry-content' );

		$post_thumnail = '';
		$content = '';
		$tags = array();
		foreach ( $hentry as $el ) {
			$pq = pq( $el );
			$pq->find('*')
				->not('p')
				->not('img')
				->not('a')
				->not('ul')
				->not('li')->remove();
			$images = $pq->find('img');
			$i = 0;
			foreach ($images as $img) {
				$upload = $this->image_upload( pq( $img )->attr( 'src' ) );
				if ( isset( $upload['url'] ) ) {
					pq( $img )->attr( 'src', $upload['url'] );
				}
				if( $i == 0 ) {
					$post_thumnail = $upload['id'];
				}
				$i++;
			}
			$a_images = $pq->find('a img');
			foreach ( $a_images as $a_image ) {
				$a = pq( $a_image )->parent();
				pq ( $a )->replaceWith( '<img src="' . pq( $a_image )->attr( 'src' ) . '" class="' . pq( $a_image )->attr( 'class' ) . '" />' );
			}
			$content .= strip_tags( $pq, '<ul><ol><li><a><p><br><strong><i><img><figure><figcaption><blockquote><span><h1><h2><h3><h4><h5>' );
			$content = preg_replace( '/[\t+\n+]/', '', $content );
		}

		$result = array(
			'content' => $content,
			'image'	=> $post_thumnail,
			'tags' => $tags,
		);

		return $result;

	}

}