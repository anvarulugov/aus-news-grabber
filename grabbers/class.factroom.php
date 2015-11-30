<?php 

class Factroom extends Grabber {

	private $channel;

	public function __construct( $channel = array() ) {

		$this->channel = $channel;

	}

	public function posts() {

		$pages = $this->get_pages( $this->channel );
		if ( ! empty( $pages )) {
			return $pages;
		} else {
			return FALSE;
		}

	}

	public function pattern( $pcontent, $title ) {

		$hentry = pq($page)->find('.post');

		$post_thumnail = '';
		$content = '';
		$tags = array();
		foreach ( $hentry as $el ) {
			$pq = pq( $el );
			$pq->find('div')->remove();
			$pq->find('h1')->remove();
			$images = $pq->find('img');
			$i = 0;
			foreach ($images as $img) {
				$upload = $this->image_upload( pq( $img )->attr( 'src' ) );
				if ( isset( $upload['url'] ) ) {
					pq( $img )->attr( 'src', $upload['url'] );
				}
				if( $i == 0 ) {
					$post_thumnail = pq( $img )->attr( 'src' );
				}
				$i++;
			}
			$a_images = $pq->find('a img');
			foreach ( $a_images as $a_image ) {
				$a = pq( $a_image )->parent();
				pq ( $a )->replaceWith( '<img src="' . pq( $a_image )->attr( 'src' ) . '" class="' . pq( $a_image )->attr( 'class' ) . '" />' );
			}
			$content .= strip_tags( $pq, '<a><p><br><strong><i><img><figure><figcaption>' );
			$content = preg_replace( '/[\t+\n+]/', '', $content );
			$content = preg_replace( '/<p><strong>Читайте также.*<\/?p>/', '', $content );
		}

		$result = array(
			'content' => $content,
			'image'	=> $post_thumnail,
			'tags' => $tags,
		);

		return $result;

	}

}