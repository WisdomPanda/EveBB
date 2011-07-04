<?php

// manuel@ortega.cl

define('PUN_ROOT', '../../');

require PUN_ROOT.'include/common.php';
require PUN_ROOT.'include/search_idx.php';
include( 'simplepie.inc.php' );


$ip = '127.0.0.1';

$feed = new SimplePie();
$feed->set_cache_location( PUN_ROOT.'cache/' );

$result = $db->query('SELECT url, max, closed, forum_id FROM '.$db->prefix.'feeds') or error('Unable to search feeds', __FILE__, __LINE__, $db->error());

print 'date: '.date( 'Y-m-d H:i:s' )."\n";

while( $row = $db->fetch_assoc( $result ) ) {
	$url = $row['url'];
	$max = $row['max'];
	$closed = intval( $row['closed'] );
	$fid = $row['forum_id'];

	// skip deletes forums
	$result2 = $db->query('SELECT 1 FROM '.$db->prefix.'forums WHERE id = '.$fid) or error('Unable to search forum', __FILE__, __LINE__, $db->error());
	if( $db->num_rows( $result2 ) === 0 ) continue;

	$feed->set_feed_url( $url );
	$feed->set_output_encoding( $lang_common['lang_encoding'] );
	$feed->init();
	$feed->handle_content_type();

	if( ! $feed->data ) continue;

	$title = $feed->get_title();

	$cont = 0;
	$max_time = 0;
	for( $i = 0; ( $max <= 0 || $i < $max ) && $item = $feed->get_item($i); $i++ ) {
		$time = $item->get_date('Y-m-d H:i:s');
		$time = $time ? strtotime( $time ) : time();

		$author = $item->get_author(0);
		if( $author ) {
			$username = $db->escape( $author->get_name() );
			$email = $author->get_email();
			if( empty( $username ) ) $username = $email;
		} else {
			$username = $title;
		}

		$username = iconv( $feed->get_encoding(), $lang_common['lang_encoding'], $username );
		$username = $db->escape( $username );
		$email = $db->escape( $email );

		$subject = '['.$title.'] '.( $item->get_title() ? $item->get_title() : basename( $item->get_permalink() ) );
		$subject = $db->escape( fa_cleanup( $subject, $feed->get_encoding(), $lang_common['lang_encoding'] ) );

		$message = $item->get_description()."\n\n[url]".$item->get_permalink()."[/url]";
		$message = $db->escape( fa_cleanup( $message, $feed->get_encoding(), $lang_common['lang_encoding'] ) );

		// skip duplicates
		$result2 = $db->query('SELECT 1 FROM '.$db->prefix.'topics WHERE poster = \''.$username.'\' AND subject = \''.$subject.'\' AND forum_id = '.$fid) or error('Unable to search topic', __FILE__, __LINE__, $db->error());
		if( $db->num_rows( $result2 ) > 0 ) continue;

		// Create the topic
		$db->query('INSERT INTO '.$db->prefix.'topics (poster, subject, posted, last_post, last_poster, closed, forum_id) VALUES(\''.$username.'\', \''.$subject.'\', '.$time.', '.$time.', \''.$username.'\', '.$closed.', '.$fid.')') or error('Unable to create topic', __FILE__, __LINE__, $db->error());
		$new_tid = $db->insert_id();

		// Insert the new post
		$db->query('INSERT INTO '.$db->prefix.'posts (poster, poster_ip, poster_email, message, hide_smilies, posted, topic_id) VALUES(\''.$username.'\', \''.$ip.'\', \''.$email.'\', \''.$message.'\', \'0\', '.$time.', '.$new_tid.')') or error('Unable to create post', __FILE__, __LINE__, $db->error());
		$new_pid = $db->insert_id();

		// Update the topic with last_post_id
		$db->query('UPDATE '.$db->prefix.'topics SET last_post_id='.$new_pid.' WHERE id='.$new_tid) or error('Unable to update topic', __FILE__, __LINE__, $db->error());

		update_search_index('post', $new_pid, $message, $subject);

		$max_time = max( $max_time, $time );
		$cont++;
	}

	if( $cont > 0 ) {
		$db->query('UPDATE '.$db->prefix.'feeds SET last_post='.$max_time.', num_posts = num_posts + '.$cont.' WHERE url=\''.$url.'\'') or error('Unable to update feeds', __FILE__, __LINE__, $db->error());

		update_forum($fid);
	}

	print "feed: $url -> $cont / $max\n";
}

list($usec, $sec) = explode(' ', microtime());
$time_diff = sprintf('%.3f', ((float)$usec + (float)$sec) - $pun_start);

die( 'time: '.$time_diff."\n\n" );


function fa_cleanup( $str, $enc_from, $enc_to ) {
	global $pun_config;
	
	//$str = iconv( $enc_from, $enc_to, $str );
	$str = str_replace(
		array("<em>", "</em>", "<strong>", "</strong>", "<p>", "</p>\n\n", "</p>\n", "<br>\n", "<br>", "<br />\n", "<br />" ),
		array("[i]", "[/i]", "[b]", "[/b]", "<br/>\n", "<br/>\n\n", "<br/>\n\n", "<br/>\n", "<br/>\n", "<br/>\n", "<br/>\n" ),
		$str
	);
	$str = preg_replace( '#<img.*?src="([^"]+)"[^>]*>#i', "[img]$1[/img]", $str );
	$str = preg_replace( '#<a.*?href="([^"]+)"[^>]*>([^<]+?)</a>#ie', "'[url=\\1]'.strtr('\\2',\"\n\r\",'  ').'[/url]'", $str );
	$str = html_entity_decode( $str, ENT_QUOTES, $enc_to );
	$str = strip_tags( $str );
	return $str;
}


