<?php 

class Hinewsru extends Grabber {

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

		$hentry = $pcontent->find( '#post' );
		$article = pq( $hentry );

		foreach ( $article as $el ) {
			$post = pq( $el );
			$post->find( '.author' )->remove();
			$title = $post->find( 'h1' )->text();
			$post_tags  = pq( $post )->find( '.tags' )->find( 'a' );
			$paragraphs = $post->find( '.text' );
			$content = '';
			$post_thumnail = '';
			foreach ($paragraphs as $p) {
				$pq = pq( $p );
				$pq->find( '[itemprop="video"]')->replaceWith( $pq->find( '[itemprop="video"]')->find( 'p' ) );
				$images = $pq->find( 'img' );
				$videos = $pq->find( '[data-code]' );
				foreach ($videos as $video) {
					$video = pq( $video );
					$video->replaceWith( '<iframe width="420" height="315" src="https://www.youtube.com/embed/' . $video->attr( 'data-code' ) . '" frameborder="0" allowfullscreen></iframe>' );
				}
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
				$content .= $pq;
			}

			$tags = [];
			foreach ($post_tags as $tag) {
				$tags[] = pq( $tag )->text();
			}

		}

		$result = array(
			'content' => $content,
			'image'	=> $post_thumnail,
			'tags' => $tags,
		);

		return $result;

	}

}