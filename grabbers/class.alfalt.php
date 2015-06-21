<?php 

class Alfalt {

	private $channel;

	public function __construct( $channel = array() ) {

		$this->channel = $channel;

	}

	public function posts() {

		$i = 0;
		$posts = array();
		if(!$this->channel['last_date'])
			$this->channel['last_date'] = '1999-01-01 00:00:00';
		foreach ($this->rss_items() as $feed) {
			$i++;
			if($this->channel['last_date'] < date('Y-m-d H:i:s',strtotime($feed->pubDate)))
				$posts[] = $this->single($feed->title,$feed->link,$feed->pubDate,$this->channel['cat_id']);
			if($i==1) break;
		}
		$posts = array_reverse($posts);

		return $posts;

	}

	public function rss_items() {

		$feeds = @simplexml_load_file($this->channel['rss_url']);
		return $feeds->channel->item;

	}

	public function single($title,$url,$date) {
		$page = @file_get_contents($url);

		//if(!class_exists('phpQuery'));
		$pcontent = phpQuery::newDocument($page);
		$hentry = $pcontent->find('.article');
		$article = pq($hentry);
		foreach ($article as $post) {
			$title = pq($post)->find('.article__title')->text();
			$image = pq($post)->find('.article__image img')->attr('src');
			$content1 = pq($post)->find('.article__zoom_content');
			$content = pq($content1)->find('p');

			$text = '';
			foreach ($content as $p) {
				$text .= pq($p);
			}
		}

		$news = array(
			'post_title' => $title,
			'post_content' => $text,
			'post_thumbnail' => $image,
			'post_date' => date('Y-m-d H:i:s', strtotime($date)),
			'post_source_url' => $url,
		);

		return $news;
	}

}