<?php 

class Lsminlt extends Grabber {

	private $channel;

	public function __construct( $channel = array() ) {

		$this->channel = $channel;

	}

	public function posts() {

		return $this->get_pages( $this->channel );

	}

	public function pattern( $pcontent, $title ) {

		$hentry = $pcontent->find( '#news-item' );
		$article = pq( $hentry );

		foreach ( $article as $post ) {
			$title = pq( $post )->find( 'h1' )->text();
			$image = pq( $post )->find( '.image img' )->attr( 'src' );
			$intro = pq( $post )->find( '.intro');
			$content = pq( $post )->find( '.text.grid_4' );
			$photos = $content->find( '.big-image-link:first' );
			$imgs = $content->find( '.image-big a:first' );
			$content->find( ':not(p, blockquote)' )->remove();

			$images = array();
			foreach ( $photos as $img ) {
				$img1 = pq( $img );
				$photo = $img1->attr( 'href' );
			}

			foreach ( $imgs as $img ) {
				$img1 = pq( $img );
				$photo2 = $img1->attr( 'href' );
			}

			if ( empty( $image ) and ! empty( $photo ) ) {
				$image = $photo;
			} elseif ( empty( $image ) and empty( $photo ) and !empty( $photo2 ) ) {
				$image = $photo2;
			}

			//$text = '<img class="aligncenter size-large" src="'.$image.'" alt="'.$title.'" title="'.$title.'" />';
			$text .= '<div class="lsminlt-news-body">';
			$text .= $intro;
			foreach ($content->find('*') as $el) {
				$text .= pq($el);
			}
			$text .= '</div>';
		}

		$result = array(
			'content' => $text,
			'image'	=> $image,
		);

		return $result;
	}

}