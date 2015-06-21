<?php 

class Newsrucom extends Grabber {

	private $channel;

	public function __construct( $channel = array() ) {

		$this->channel = $channel;

	}

	public function posts() {

		return $this->get_pages( $this->channel );

	}

	public function pattern($pcontent, $title) {
		
		$hentry = $pcontent->find('.maintext');
		$news = pq($hentry);
		$content = '';
		$images = $news->find('img');
		$i = 0;
		//if(count($images)<6) continue;
			foreach ($images as $image) {
				$i++;
				$image_link[] = pq($image)->attr('src');
				if($i==2) break;
			}
		$large_image = str_replace('/pict/id/', '/pict/id/large/', $image_link[1]);
		$thumnail = '<img class="aligncenter size-large" src="'.$large_image.'" alt="'.$title.'" title="'.$title.'" />';

		$txts = $news->find("p");
		$i = 0;
		foreach ($txts as $el) {
			$i++;
			if($i==1) continue;
			if($i>(count($txts)-3)) {
				//echo 'Broken';
				break;
			}
			$pq = pq($el);
			$content .= '<p>'.$pq->text().'</p>';
		}
		$content = preg_replace("#<p>(\s|&nbsp;|</?\s?br\s?/?>)*</?p>#", '', $content);
		$news_body = trim($content);

		$result = array(
			'content' => $news_body,
			'image'	=> $large_image,
		);

		return $result;
	}

}