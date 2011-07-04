<?php

/**
 * Copyright (C) 2008-2010 FluxBB
 * based on code by Rickard Andersson copyright (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';
require PUN_ROOT.'include/poll.php';
require PUN_ROOT.'include/attach/attach_incl.php'; //Attachment Mod row, loads variables, functions and lang file


if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view']);


$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	message($lang_common['Bad request']);

// Fetch some info about the post, the topic and the forum
//Old: $result = $db->query('SELECT f.id AS fid, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics, t.id AS tid, t.subject, t.posted, t.first_post_id, t.sticky, t.closed, p.poster, p.poster_id, p.message, p.hide_smilies FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON t.id=p.topic_id INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND p.id='.$id) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
$result = $db->query('SELECT f.id AS fid, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics, t.id AS tid, t.subject, t.posted, t.first_post_id, t.sticky, t.closed, t.poll_type, t.poll_time, t.poll_term, t.poll_kol, p.poster, p.poster_id, p.message, p.hide_smilies FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON t.id=p.topic_id INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND p.id='.$id) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result))
	message($lang_common['Bad request']);

$cur_post = $db->fetch_assoc($result);

// Sort out who the moderators are and if we are currently a moderator (or an admin)
$mods_array = ($cur_post['moderators'] != '') ? unserialize($cur_post['moderators']) : array();
$is_admmod = ($pun_user['g_id'] == PUN_ADMIN || ($pun_user['g_moderator'] == '1' && array_key_exists($pun_user['username'], $mods_array))) ? true : false;

$can_edit_subject = $id == $cur_post['first_post_id'];

if ($pun_config['o_censoring'] == '1')
{
	$cur_post['subject'] = censor_words($cur_post['subject']);
	$cur_post['message'] = censor_words($cur_post['message']);
}

// Do we have permission to edit this post?
if (($pun_user['g_edit_posts'] == '0' ||
	$cur_post['poster_id'] != $pun_user['id'] ||
	$cur_post['closed'] == '1') &&
	!$is_admmod)
	message($lang_common['No permission']);

// Load the post.php/edit.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/post.php';

// Start with a clean slate
$errors = array();


if (isset($_POST['form_sent']))
{
	if ($is_admmod)
		confirm_referrer('edit.php');

	// If it's a topic it must contain a subject
	if ($can_edit_subject)
	{
		$subject = pun_trim($_POST['req_subject']);

		if ($pun_config['o_censoring'] == '1')
			$censored_subject = pun_trim(censor_words($subject));
 	 
		if ($subject == '')
			$errors[] = $lang_post['No subject'];
		else if ($pun_config['o_censoring'] == '1' && $censored_subject == '')
			$errors[] = $lang_post['No subject after censoring'];
		else if (pun_strlen($subject) > 70)
			$errors[] = $lang_post['Too long subject'];
		else if ($pun_config['p_subject_all_caps'] == '0' && is_all_uppercase($subject) && !$pun_user['is_admmod'])
			$errors[] = $lang_post['All caps subject'];
		
		poll_form_validate($cur_post['tid'], $errors);
	}

	// Clean up message from POST
	$message = pun_linebreaks(pun_trim($_POST['req_message']));

	// Here we use strlen() not pun_strlen() as we want to limit the post to PUN_MAX_POSTSIZE bytes, not characters
	if (strlen($message) > PUN_MAX_POSTSIZE)
		$errors[] = sprintf($lang_post['Too long message'], forum_number_format(PUN_MAX_POSTSIZE));
	else if ($pun_config['p_message_all_caps'] == '0' && is_all_uppercase($message) && !$pun_user['is_admmod'])
		$errors[] = $lang_post['All caps message'];

	// Validate BBCode syntax
	if ($pun_config['p_message_bbcode'] == '1')
	{
		require PUN_ROOT.'include/parser.php';
		$message = preparse_bbcode($message, $errors);
	}

    if (empty($errors))
    {
        if ($message == '')
            $errors[] = $lang_post['No message'];
        else if ($pun_config['o_censoring'] == '1')
        {
            // Censor message to see if that causes problems
            $censored_message = pun_trim(censor_words($message));
            if ($censored_message == '')
                $errors[] = $lang_post['No message after censoring'];
        }
    }

	$hide_smilies = isset($_POST['hide_smilies']) ? '1' : '0';

	$stick_topic = isset($_POST['stick_topic']) ? '1' : '0';
 	if (!$is_admmod)
 		$stick_topic = $cur_post['sticky'];
 	
	// Did everything go according to plan?
	if (empty($errors) && !isset($_POST['preview']))
	{
		$edited_sql = (!isset($_POST['silent']) || !$is_admmod) ? ', edited='.time().', edited_by=\''.$db->escape($pun_user['username']).'\'' : '';

		require PUN_ROOT.'include/search_idx.php';

		if ($can_edit_subject)
		{
			// Update the topic and any redirect topics
			$db->query('UPDATE '.$db->prefix.'topics SET subject=\''.$db->escape($subject).'\', sticky='.$stick_topic.' WHERE id='.$cur_post['tid'].' OR moved_to='.$cur_post['tid']) or error('Unable to update topic', __FILE__, __LINE__, $db->error());

			// We changed the subject, so we need to take that into account when we update the search words
			update_search_index('edit', $id, $message, $subject);
		}
		else
			update_search_index('edit', $id, $message);

		// Update the post
		$db->query('UPDATE '.$db->prefix.'posts SET message=\''.$db->escape($message).'\', hide_smilies='.$hide_smilies.$edited_sql.' WHERE id='.$id) or error('Unable to update post', __FILE__, __LINE__, $db->error());

		//Attachment Mod 2.0 Block Start
		//First check if there are any files to delete, the postvariables should be named 'attach_delete_'.$i , if it's set you're going to delete the value of this (the 0 =< $i < attachments, just to get some order in there...)
		if(isset($_POST['attach_num_attachments'])){
			// if there is any number of attachments, check if there has been any deletions ... if so, delete the files if allowed...
			$attach_num_attachments = intval($_POST['attach_num_attachments']);
			for($i=0;$i<$attach_num_attachments;$i++){
				if(array_key_exists('attach_delete_'.$i,$_POST)){
					$attach_id=intval($_POST['attach_delete_'.$i]);
					//fetch info about it ... owner and such ... (so we know if it's going to be ATTACH_OWNER_DELETE or ATTACH_DELETE that will affect the rulecheck...
					$result_attach = $db->query('SELECT af.owner,ar.rules FROM '.$db->prefix.'attach_2_files AS af, '.$db->prefix.'attach_2_rules AS ar, '.$db->prefix.'posts AS p, '.$db->prefix.'topics AS t WHERE af.id='.$attach_id.' AND ar.group_id='.$pun_user['g_id'].' AND ar.forum_id=t.forum_id AND t.id=p.topic_id AND p.id=af.post_id LIMIT 1') or error('Unable to fetch attachment details and forum rules', __FILE__, __LINE__, $db->error());
					if($db->num_rows($result_attach)>0||$pun_user['g_id']==PUN_ADMIN){
						list($attach_cur_owner,$attach_rules)=$db->fetch_row($result_attach);
						
						$attach_allowed = false;
						
						if($pun_user['g_id']==PUN_ADMIN)//admin overrides
							$attach_allowed = true;
						elseif($attach_cur_owner==$pun_user['id'])//it's the owner of the file that want to delete it
							$attach_allowed=attach_rules($attach_rules,ATTACH_OWNER_DELETE);
						else //it's not the owner that wants to delete the attachment...
							$attach_allowed=attach_rules($attach_rules,ATTACH_DELETE);

						if($attach_allowed){
							if(!attach_delete_attachment($attach_id)){
								// uncomment if you want to show error if it fails to delete
								//error('Unable to delete attachment.');
							}
						}else{
							// the user may not delete it ... uncomment the error if you want to use it ...
							//error('You\'re not allowed to delete the attachment');
						}
					}else{
						// the user probably hasn't any rules in this forum any longer...
					}
				}
			}
		}
		
		if (isset($_FILES['attached_file']['error']) && $_FILES['attached_file']['error'] != 0 && $_FILES['attached_file']['error'] != 4)
			error(file_upload_error_message($_FILES['attached_file']['error']), __FILE__, __LINE__);

		//Then recieve any potential new files
		if((isset($_FILES['attached_file'])&&$_FILES['attached_file']['size']!=0&&is_uploaded_file($_FILES['attached_file']['tmp_name']))){
			//ok, we have a new file, much similar to post, except we need to check if the user uploads too many files...
			$attach_allowed=false;
			if($pun_user['g_id']==PUN_ADMIN){
				$attach_allowed=true;
			}else{
				//fetch forum rules and the number of attachments for this post.
				$result_attach = $db->query('SELECT COUNT(af.id) FROM '.$db->prefix.'attach_2_files AS af WHERE af.post_id='.$id.' GROUP BY af.post_id LIMIT 1') or error('Unable to fetch current number of attachments in post',__FILE__,__LINE__,$db->error());
				if($db->num_rows($result_attach)==1){
					list($attach_num_attachments)=$db->fetch_row($result_attach);
				}else{
					$attach_num_attachments=0;
				}

				$result_attach = $db->query('SELECT ar.rules,ar.size,ar.file_ext,ar.per_post FROM '.$db->prefix.'attach_2_rules AS ar, '.$db->prefix.'posts AS p, '.$db->prefix.'topics AS t WHERE group_id='.$pun_user['g_id'].' AND p.id='.$id.' AND t.id=p.topic_id AND ar.forum_id=t.forum_id LIMIT 1')or error('Unable to fetch attachment rules',__FILE__,__LINE__,$db->error());
				if($db->num_rows($result_attach)==1){
					list($attach_rules,$attach_size,$attach_file_ext,$attach_per_post)=$db->fetch_row($result_attach);
					//first check if the user is allowed to upload
					$attach_allowed=attach_allow_upload($attach_rules,$attach_size,$attach_file_ext,$_FILES['attached_file']['size'],$_FILES['attached_file']['name']); //checks so that extensions, size etc is ok
					if($attach_allowed && $attach_num_attachments < $attach_per_post) // if we haven't attached too many...
						$attach_allowed=true;
					else
						$attach_allowed=false;
				}else{
					// probably no rules, don't allow upload
					$attach_allowed=false;
				}
			}
			// ok, by now we should know if it's allowed to upload or not ...
			if($attach_allowed){ //if so upload it ...
				if(!attach_create_attachment($_FILES['attached_file']['name'],$_FILES['attached_file']['type'],$_FILES['attached_file']['size'],$_FILES['attached_file']['tmp_name'],$id,count_chars($message))){
					error('Error creating attachment, inform the owner of this bulletin board of this problem. (Most likely something to do with rights on the filesystem)',__FILE__,__LINE__);
				}
			}
		}
		//Attachment Mod 2.0 Block End

		if ($can_edit_subject)
			poll_save($cur_post['tid']);
		
		redirect('viewtopic.php?pid='.$id.'#p'.$id, $lang_post['Edit redirect']);
	}
}
//Attachment Mod 2.0 Block Start
//ok, first check the rules, so we know if the user may may upload more or delete potentially existing attachments
$attach_allow_delete=false;
$attach_allow_owner_delete=false;
$attach_allow_upload=false;
$attach_allowed=false;
$attach_allow_size=0;
$attach_per_post=0;
if($pun_user['g_id']==PUN_ADMIN){
	$attach_allow_delete=true;
	$attach_allow_owner_delete=true;
	$attach_allow_upload=true;
	$attach_allow_size=$pun_config['attach_max_size'];
	$attach_per_post=-1;
}else{
	$result_attach=$db->query('SELECT ar.rules,ar.size,ar.per_post,COUNT(f.id) FROM '.$db->prefix.'attach_2_rules AS ar, '.$db->prefix.'attach_2_files AS f, '.$db->prefix.'posts AS p, '.$db->prefix.'topics AS t WHERE group_id='.$pun_user['g_id'].' AND p.id='.$id.' AND t.id=p.topic_id AND ar.forum_id=t.forum_id GROUP BY f.post_id LIMIT 1')or error('Unable to fetch attachment rules and current number of attachments in post (#2)',__FILE__,__LINE__,$db->error());
	if($db->num_rows($result_attach)==1){
		list($attach_rules,$attach_allow_size,$attach_per_post,$attach_num_attachments)=$db->fetch_row($result_attach);
		//may the user delete others attachments?
		$attach_allow_delete = attach_rules($attach_rules,ATTACH_DELETE);
		//may the user delete his/her own attachments?
		$attach_allow_owner_delete = attach_rules($attach_rules,ATTACH_OWNER_DELETE);
		//may the user upload new files?
		$attach_allow_upload = attach_rules($attach_rules,ATTACH_UPLOAD);
	}else{
		//no rules set, so nothing allowed
	}
}
$attach_output = '';
$attach_output_two = '';
//check if this post has attachments, if so make the appropiate output
if($attach_allow_delete||$attach_allow_owner_delete||$attach_allow_upload){
	$attach_allowed=true;
	$result_attach=$db->query('SELECT af.id, af.owner, af.filename, af.extension, af.size, af.downloads FROM '.$db->prefix.'attach_2_files AS af WHERE post_id='.$id) or error('Unable to fetch current attachments',__FILE__,__LINE__,$db->error());
	if($db->num_rows($result_attach)>0){
		//time for some output ... create the existing files ...
		$i=0;
		while(list($attach_id,$attach_owner,$attach_filename,$attach_extension,$attach_size,$attach_downloads)=$db->fetch_row($result_attach)){
			if(($attach_owner==$pun_user['id']&&$attach_allow_owner_delete)||$attach_allow_delete)
				$attach_output .= '<br />'."\n".'<input type="checkbox" name="attach_delete_'.$i.'" value="'.$attach_id.'" />'.$lang_attach['Delete?'].' '.attach_icon($attach_extension).' <a href="./attachment.php?item='.$attach_id.'">'.pun_htmlspecialchars($attach_filename).'</a>, '.$lang_attach['Size:'].' '.format_bytes($attach_size).', '.$lang_attach['Downloads:'].' '.number_format($attach_downloads);
			else
				$attach_output_two .= '<br />'."\n".attach_icon($attach_extension).' <a href="./attachment.php?item='.$attach_id.'">'.pun_htmlspecialchars($attach_filename).'</a>, '.$lang_attach['Size:'].' '.format_bytes($attach_size).', '.$lang_attach['Downloads:'].' '.number_format($attach_downloads);
			$i++;
		}
		if(strlen($attach_output)>0)
			$attach_output = '<input type="hidden" name="attach_num_attachments" value="'.$db->num_rows($result_attach).'" />'.$lang_attach['Existing'] . $attach_output;
		if(strlen($attach_output_two)>0)
			$attach_output .= "<br />\n".$lang_attach['Existing2'] . $attach_output_two;
		$attach_output .= "<br />\n";
	}else{
		// we have not existing files
	}
}
//fix the 'new upload' field...
if($attach_allow_upload){
	if(strlen($attach_output)>0)$attach_output .= "<br />\n";
	if($attach_per_post==-1)$attach_per_post = '<em>unlimited</em>';
	$attach_output .= str_replace('%%ATTACHMENTS%%',$attach_per_post,$lang_attach['Upload'])."<br />\n".'<input type="hidden" name="MAX_FILE_SIZE" value="'.$attach_allow_size.'" /><input type="file" name="attached_file" size="80" />';
	
	
	
}
//Attachment Mod 2.0 Block End



$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_post['Edit post']);
$required_fields = array('req_subject' => $lang_common['Subject'], 'req_message' => $lang_common['Message']);
$focus_element = array('edit', 'req_message');
define('PUN_ACTIVE_PAGE', 'index');
require PUN_ROOT.'header.php';

$cur_index = 1;

?>
<div class="linkst">
	<div class="inbox">
		<ul class="crumbs">
			<li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="viewforum.php?id=<?php echo $cur_post['fid'] ?>"><?php echo pun_htmlspecialchars($cur_post['forum_name']) ?></a></li>
			<li><span>»&#160;</span><a href="viewtopic.php?id=<?php echo $cur_post['tid'] ?>"><?php echo pun_htmlspecialchars($cur_post['subject']) ?></a></li>
			<li><span>»&#160;</span><strong><?php echo $lang_post['Edit post'] ?></strong></li>
		</ul>
	</div>
</div>

<?php

// If there are errors, we display them
if (!empty($errors))
{

?>
<div id="posterror" class="block">
	<h2><span><?php echo $lang_post['Post errors'] ?></span></h2>
	<div class="box">
		<div class="inbox error-info">
			<p><?php echo $lang_post['Post errors info'] ?></p>
			<ul class="error-list">
<?php

	foreach ($errors as $cur_error)
		echo "\t\t\t\t".'<li><strong>'.$cur_error.'</strong></li>'."\n";
?>
			</ul>
		</div>
	</div>
</div>

<?php

}
else if (isset($_POST['preview']))
{
	require_once PUN_ROOT.'include/parser.php';
	$preview_message = parse_message($message, $hide_smilies);

?>
<div id="postpreview" class="blockpost">
	<h2><span><?php echo $lang_post['Post preview'] ?></span></h2>
	<div class="box">
		<div class="inbox">
			<div class="postbody">
				<div class="postright">
					<div class="postmsg">
						<?php echo $preview_message."\n" ?>
						<?php if ($can_edit_subject) poll_display_post($cur_post['tid'], $pun_user['id']); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php

}

?>
<div id="editform" class="blockform">
	<h2><span><?php echo $lang_post['Edit post'] ?></span></h2>
	<div class="box">
		<form id="edit" method="post" <?php echo 'enctype="multipart/form-data"'; ##Attachment Mod 2.0 ?> action="edit.php?id=<?php echo $id ?>&amp;action=edit" onsubmit="return process_form(this)">
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_post['Edit post legend'] ?></legend>
					<input type="hidden" name="form_sent" value="1" />
					<div class="infldset txtarea">
<?php if ($can_edit_subject): ?>						<label class="required"><strong><?php echo $lang_common['Subject'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br />
						<input class="longinput" type="text" name="req_subject" size="80" maxlength="70" tabindex="<?php echo $cur_index++ ?>" value="<?php echo pun_htmlspecialchars(isset($_POST['req_subject']) ? $_POST['req_subject'] : $cur_post['subject']) ?>" /><br /></label>
<?php endif; ?>						<label class="required"><strong><?php echo $lang_common['Message'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br />
						<textarea id="req_message" name="req_message" rows="20" cols="95" tabindex="<?php echo $cur_index++ ?>"><?php echo pun_htmlspecialchars(isset($_POST['req_message']) ? $message : $cur_post['message']) ?></textarea><br /></label>
						<ul class="bblinks">
							<li><span><a href="help.php#bbcode" onclick="window.open(this.href); return false;"><?php echo $lang_common['BBCode'] ?></a> <?php echo ($pun_config['p_message_bbcode'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></span></li>
							<li><span><a href="help.php#img" onclick="window.open(this.href); return false;"><?php echo $lang_common['img tag'] ?></a> <?php echo ($pun_config['p_message_bbcode'] == '1' && $pun_config['p_message_img_tag'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></span></li>
							<li><span><a href="help.php#smilies" onclick="window.open(this.href); return false;"><?php echo $lang_common['Smilies'] ?></a> <?php echo ($pun_config['o_smilies'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></span></li>
						</ul>
					</div>
				</fieldset>
<?php
$checkboxes = array();
//Attachment Mod Block Start
if($attach_allowed){
?>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_attach['Attachment'] ?></legend>
					<div class="infldset">
						<?php echo $attach_output; ?><br />
						<?php echo $lang_attach['Note2']; ?>
					</div>
				</fieldset>
<?php
}
//Attachment Mod Block End

if ($can_edit_subject && $is_admmod)
{
    if (isset($_POST['stick_topic']) || $cur_post['sticky'] == '1')
        $checkboxes[] = '<label><input type="checkbox" name="stick_topic" value="1" checked="checked" tabindex="'.($cur_index++).'" />'.$lang_common['Stick topic'].'<br /></label>';
    else
        $checkboxes[] = '<label><input type="checkbox" name="stick_topic" value="1" tabindex="'.($cur_index++).'" />'.$lang_common['Stick topic'].'<br /></label>';
}

if ($pun_config['o_smilies'] == '1')
{
	if (isset($_POST['hide_smilies']) || $cur_post['hide_smilies'] == '1')
		$checkboxes[] = '<label><input type="checkbox" name="hide_smilies" value="1" checked="checked" tabindex="'.($cur_index++).'" />'.$lang_post['Hide smilies'].'<br /></label>';
	else
		$checkboxes[] = '<label><input type="checkbox" name="hide_smilies" value="1" tabindex="'.($cur_index++).'" />'.$lang_post['Hide smilies'].'<br /></label>';
}

if ($is_admmod)
{
	if ((isset($_POST['form_sent']) && isset($_POST['silent'])) || !isset($_POST['form_sent']))
		$checkboxes[] = '<label><input type="checkbox" name="silent" value="1" tabindex="'.($cur_index++).'" checked="checked" />'.$lang_post['Silent edit'].'<br /></label>';
	else
		$checkboxes[] = '<label><input type="checkbox" name="silent" value="1" tabindex="'.($cur_index++).'" />'.$lang_post['Silent edit'].'<br /></label>';
}

if (!empty($checkboxes))
{

?>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_common['Options'] ?></legend>
					<div class="infldset">
						<div class="rbox">
							<?php echo implode("\n\t\t\t\t\t\t\t", $checkboxes)."\n" ?>
						</div>
					</div>
				</fieldset>
<?php

	}

?>
			</div>
			<?php if ($can_edit_subject) poll_form_edit($cur_post['tid']); ?>
			<p class="buttons"><input type="submit" name="submit" value="<?php echo $lang_common['Submit'] ?>" tabindex="<?php echo $cur_index++ ?>" accesskey="s" /> <input type="submit" name="preview" value="<?php echo $lang_post['Preview'] ?>" tabindex="<?php echo $cur_index++ ?>" accesskey="p" /> <a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p>
		</form>
	</div>
</div>
<?php

require PUN_ROOT.'footer.php';
