<?php

// manuel@ortega.cl

// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

// Tell admin_loader.php that this is indeed a plugin and that it is loaded
define('PUN_PLUGIN_LOADED', 1);


//
// The rest is up to you!
//

$plugin = 'AP_Feed_Aggregator.php';

$result = $db->query('SELECT f.url, f.max, f.closed, f.forum_id, f.last_post, f.num_posts, fo.forum_name FROM '.$db->prefix.'feeds AS f LEFT JOIN '.$db->prefix.'forums AS fo ON f.forum_id = fo.id ORDER BY fo.forum_name ASC, f.last_post ASC');

// Unistalled
if( $result === FALSE ) {

	if( isset( $_REQUEST['install'] ) ) {
		$db->query('CREATE TABLE '.$db->prefix.'feeds ( url varchar(255) NOT NULL default \'\', max int(11) NOT NULL default 0, closed tinyint(1) NOT NULL default 0, forum_id int(11) NOT NULL default 0, last_post INT(10) NOT NULL default 0, num_posts INT(10) NOT NULL default 0, PRIMARY KEY  (url) )' );
		redirect( 'admin_loader.php?plugin='.$plugin, 'Feed Agregator installed' );
	}

// Display the admin navigation menu
generate_admin_menu( $plugin );

?>

	<div class="blockform">
		<h2><span>Feed Agregator</span></h2>
		<div class="box">
			<form method="post" action="?plugin=<?php print $plugin ?>">
			<div class="inbox">
				<fieldset>
					<legend>Installation</legend>
					<div class="infldset">

					<p>Run the <a href="<?php print $pun_config['o_base_url'] ?>/plugins/feed/sp_compatibility_test.php" target="_blank">Compatibility Test</a></p>

					<p class="submitend"><input type="submit" name="install" value="Install" /></p>
					</div>

				</fieldset>
			</div>
		</div>
	</div>

<?php

} else {

$feeds = array();
while( $row = $db->fetch_assoc( $result ) ) $feeds[ $row['url'] ] = $row;

$url = trim( $_REQUEST['url'] );

if( isset( $_REQUEST['uninstall'] ) ) {
	$db->query('DROP TABLE '.$db->prefix.'feeds') or error('Unable to drop table \'feeds\'', __FILE__, __LINE__, $db->error());
	redirect( 'admin_loader.php?plugin='.$plugin, 'Feed Agregator uninstalled' );

} else if( isset( $_REQUEST['delete'] ) && isset( $feeds[$url] ) ) {
	$db->query('DELETE FROM '.$db->prefix.'feeds WHERE url = \''.$db->escape( $url ).'\'' ) or error('Unable to delete feed', __FILE__, __LINE__, $db->error());
	redirect( 'admin_loader.php?plugin='.$plugin, 'Feed deleted' );

} else if( isset( $_REQUEST['add'] ) && ! isset( $feeds[$url] ) ) {
	$closed = empty( $_REQUEST['closed'] ) ? 0 : 1;
	$db->query('INSERT INTO '.$db->prefix.'feeds ( url, max, closed, forum_id ) VALUES ( \''.$db->escape( $url ).'\', '.intval( $_REQUEST['max'] ).', '.$closed.', '.intval( $_REQUEST['forum_id'] ).' )') or error('Unable to create feed', __FILE__, __LINE__, $db->error());
	redirect( 'admin_loader.php?plugin='.$plugin, 'Feed added' );
}

generate_admin_menu( $plugin );

?>
	<div class="blockform">
		<h2><span>Feed Agregator</span></h2>
		<div class="box">
		<form method="post" action="?plugin=<?php print $plugin ?>">
			<div class="inform">
				<fieldset>
					<legend>Installation</legend>
					<div class="infldset">

						<p>Test the <a href="<?php print $pun_config['o_base_url'] ?>/plugins/feed/fa_cron.php?<?php print rand() ?>" target="_blank">cron script</a> and install crontab (crontab -e)
<div class="codebox"><div class="incqbox"><div class="scrollbox" style="height: "><pre>
*/30 * * * * /usr/bin/wget -O - -q <?php print $pun_config['o_base_url'] ?>/plugins/feed/fa_cron.php
</pre></div></div></div>
						</p>

						<p class="submitend"><input type="submit" name="uninstall" value="Uninstall" /></p>
					</div>

				</fieldset>
			</div>

			<div class="inform">
				<fieldset>
					<legend>Configuration</legend>
					<div class="infldset">

						<table cellspacing="0">
						<thead>
							<tr>
								<th scope="row">URL</th>
								<th>Forum</th>
								<th>Max. Post per Cron</th>
								<th>Closed</th>
								<th scope="row">Last Post</th>
								<th scope="row">Num Posts</th>
								<th></th>
							</tr>
						</thead>

						<tbody>
							<tr>
								<td><input type="text" maxlength="255" name="url" value="http://" /></td>
								<td>
									<select name="forum_id">
<?php

$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id WHERE f.redirect_url IS NULL ORDER BY c.disp_position, c.id, f.disp_position', true) or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());
$cur_category = 0;
while( $cur_forum = $db->fetch_assoc( $result ) ) {
	if( $cur_forum['cid'] != $cur_category ) { // A new category since last iteration?
		if ($cur_category) print '</optgroup>';
		print '<optgroup label="'.pun_htmlspecialchars($cur_forum['cat_name']).'">';
		$cur_category = $cur_forum['cid'];
	}

	print '<option value="'.$cur_forum['fid'].'">'.pun_htmlspecialchars( $cur_forum['forum_name'] ).'</option>';
}

?>
									</optgroup>
									</select>

								</td>
								<td><input type="text" maxlength="1" size="1" name="max" value="0" /> 0 = Unlimited</td>
								<td><input type="checkbox" value="1" name="closed" /></td>
								<td colspan="3"><input type="submit" name="add" value="Add" /></td>
							</tr>
<?php
foreach( $feeds as $feed ) {
?>
							<tr>
								<td><?php print $feed['url'] ?></td>
								<td><?php print $feed['forum_name'] ? pun_htmlspecialchars( $feed['forum_name'] ) : '<strong>DELETED</strong>' ?></td>
								<td><?php print $feed['max'] ? $feed['max'] : 'Unlimited' ?></td>
								<td><?php print $feed['closed'] ? 'Yes' : 'No' ?></td>
								<td><?php print $feed['last_post'] == 0 ? 'Never' : format_time( $feed['last_post'] ) ?></td>
								<td><?php print $feed['num_posts'] ?></td>
								<td><a href="?plugin=<?php print $plugin ?>&url=<?php print urlencode( $feed['url'] ) ?>&delete=1">Delete</a></td>
							</tr>
<?php
}
?>
						</tbody>
						</table>

						</div>
				</fieldset>
			</div>
		</form>
		</div>
	</div>

<?php

}
?>