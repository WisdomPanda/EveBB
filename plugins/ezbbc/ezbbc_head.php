<?php
// Integration of EZBBC Toolbar script only in the pages containing the req_message textarea and in the profile page
if ((isset($required_fields['req_message']) && basename($_SERVER['PHP_SELF']) != 'misc.php') || (PUN_ACTIVE_PAGE == 'profile' && $pun_config['o_signatures'] == '1')):

// Language file load
$ezbbc_language_folder = (file_exists(PUN_ROOT.'plugins/ezbbc/lang/'.$pun_user['language'].'/ezbbc_plugin.php')) ? $pun_user['language'] : 'English';
require PUN_ROOT.'plugins/ezbbc/lang/'.$ezbbc_language_folder.'/ezbbc_plugin.php';

// Retrieving help file
$ezbbc_help_folder = (file_exists(PUN_ROOT.'plugins/ezbbc/lang/'.$ezbbc_language_folder.'/help.php')) ? $ezbbc_language_folder : 'English';
$help_file_path = 'plugins/ezbbc/lang/'.$ezbbc_help_folder.'/help.php';

// Retrieving style folder and smilies set
$config_content = trim(file_get_contents(PUN_ROOT.'plugins/ezbbc/config.php'));
$config_item = explode(";", $config_content);
$ezbbc_style_folder = $config_item[2];
$ezbbc_smilies_set = $config_item[3];
$smilies_path = ($ezbbc_smilies_set == 'fluxbb_default_smilies') ? 'img/smilies/' : 'plugins/ezbbc/style/smilies/';

// Identifying the name of the current page textatera
$textarea_name = (PUN_ACTIVE_PAGE == 'profile') ? 'signature' : 'req_message';
?>
<!-- EZBBC Toolbar integration -->
<link rel="stylesheet" type="text/css" href="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/ezbbc.css" />
<script type="text/javascript">
/* <![CDATA[ */
// Preloading the EZBBC buttons
var preload = ( function ( ) {
	var images = [ ];
	function preload( ) {
		var i = arguments.length,
		image;
		while ( i-- ) {
			image = new Image;
			images.src = arguments[ i ];
			images.push( image );
		}
	}
	return preload;
}( ) );

preload(
	"plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/bold.png",
	"plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/underline.png",
	"plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/italic.png",
	"plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/strike-through.png",
	"plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/delete.png",
	"plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/insert.png",
	"plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/emphasis.png",
	"plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/color.png",
	"plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/heading.png",
	"plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/link.png",
	"plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/email.png",
	"plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/image.png",
	"plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/quote.png",
	"plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/code.png",
	"plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/list-unordered.png",
	"plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/list-ordered.png",
	"plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/list-ordered-alpha.png",
	"plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/smilie.png",
	"plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/help.png",
	"plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/video.png"
);

// Function to insert the tags in the textarea
function insertTag(startTag, endTag, tagType) {
        var field  = document.getElementsByName('<?php echo $textarea_name ?>')[0];
        var scroll = field.scrollTop;
        field.focus();
        
        
        /* === Part 1: get the selection === */
        if (window.ActiveXObject) { //For IE
                var textRange = document.selection.createRange();
                var currentSelection = textRange.text;
        } else { //For other browsers
                var startSelection   = field.value.substring(0, field.selectionStart);
                var currentSelection = field.value.substring(field.selectionStart, field.selectionEnd);
                var endSelection     = field.value.substring(field.selectionEnd);
        }
        
        /* === Part 2: what Tag type ? === */
        if (tagType) {
                switch (tagType) {
                   	case "color":
                       	       if (currentSelection) { //Something is selected
        				var color = prompt("<?php echo $lang_ezbbc['Ask color'] ?> (<?php echo $lang_ezbbc['Ask color explanation'] ?>)", "");
        			        if (color != '' && color != null) {
        			        	startTag = '[color=' + color + ']';
        			        } else {
        			                startTag = endTag = '';
        			        }
        			        
        			} else { //Nothing is selected
        			        var color = prompt("<?php echo $lang_ezbbc['Ask color'] ?> (<?php echo $lang_ezbbc['Ask color explanation'] ?>)", "");
        			        if (color != '' && color != null) {
        			                var text = prompt("<?php echo $lang_ezbbc['Ask colorized text'] ?>", "");
        			                if (text != '' && color !=null) {
        			                        startTag = '[color=' + color + ']';
        			                        currentSelection = text;
        			                } else {
        			                        startTag = '[color=' + color + ']';
        			                        currentSelection = "<?php echo$lang_ezbbc['Ask colorized text'] ?>";
        			                }
        			        } else {
        			                startTag = endTag = '';
        			        }
        			}
        		break;
        		case "heading":
                       	       if (!currentSelection) { //Nothing is selected
        			       var title = prompt("<?php echo $lang_ezbbc['Ask title'] ?>", "");
        			       if (title != '' && title != null) {
        			               currentSelection = title;
        			       } else if (title != null) {
        			               currentSelection = "<?php echo $lang_ezbbc['Ask title'] ?>";
        			       } else {
        			               startTag = endTag = '';
        			       }
        			}
        		break;
        		case "link":
       				if (currentSelection) { //Something is selected
       				        if (currentSelection.indexOf('http://') == 0 || currentSelection.indexOf('https://') == 0 || currentSelection.indexOf('ftp://') == 0 || currentSelection.indexOf('www.') == 0) {
       				                //The selection seems to be a link
       				                startTag = '[url=' + currentSelection + ']';
       				        } else {
       				                //The selection is not a link, so it is the label. We ask for the URL
       				                var URL = prompt("<?php echo$lang_ezbbc['Ask url'] ?>", "");
       				                if (URL != '' && URL != null && (URL.indexOf('http://') == 0 || URL.indexOf('https://') == 0 || URL.indexOf('ftp://') == 0 || URL.indexOf('www.') == 0)) {
       				                        startTag = '[url=' + URL + ']';
       				                } else {
       				                        startTag = endTag = '';
       				                }
       				        }
       				} else { //No selection, we ask for the URL and the label
       				         var URL = prompt("<?php echo $lang_ezbbc['Ask url'] ?>", "");
       				         if (URL != '' && URL != null && (URL.indexOf('http://') == 0 || URL.indexOf('https://') == 0 || URL.indexOf('ftp://') == 0 || URL.indexOf('www.') == 0)) {
       				                 var label = prompt("<?php echo $lang_ezbbc['Ask label'] ?>", "");
       				                 if (label != '') {
       				                         startTag = '[url=' + URL + ']';
       				                         currentSelection = label;
       				                 } else {
       				                         startTag = '[url=' + URL + ']';
       				                         currentSelection = URL;
       				                 }
       				         } else {
       				                 startTag = endTag = '';
       				         }
       				}
       			break;
        		case "email":
       				if (currentSelection) { // Something is selected
       				        if (currentSelection.indexOf('@') == -1) {
       				                //The selection is not an E-mail address, so it is the label. We ask for the E-mail
       				                var email = prompt("<?php echo $lang_ezbbc['Ask email'] ?>", "");
       				                if (email != '' && email != null && email.indexOf('@') != -1) {
       				                        startTag = '[email=' + email + ']';
       				                } else {
       				                        startTag = endTag = '';
       				                }
       				        } else {//The selection seems to be an E-mail address
       				                startTag = '[email=' + currentSelection + ']';
       				        }
       				                
       				} else { //No selection, we ask for the URL and the label
       				        var email = prompt("<?php echo $lang_ezbbc['Ask email'] ?>", "");
       				        if (email !='' && email != null && email.indexOf('@') != -1) {
       				                var label = prompt("<?php echo $lang_ezbbc['Ask label'] ?>", "");
       				                if (label != '') {
       				                        startTag = '[email=' + email + ']';
       				                        currentSelection = label;
       				                } else {
       				                        currentSelection = email;
       				                }
       				        } else {
       				                startTag = endTag = '';
       				        }
       				}
       			break;
       			case "img":
       				if (currentSelection) { //Something is selected
       				        if (currentSelection.indexOf('http://') == 0) {
       				                //The selection seems to be a link, we ask for the alt text
       				                var alt = prompt("<?php echo $lang_ezbbc['Ask alt'] ?>", "");
       				                if (alt != '' && alt != null) {
       				                        startTag = '[img=' + alt + ']';
       				                }
       				        } else {
       				                //The selection is not a link, so it is the alt text. We ask for the URL
       				                var URL = prompt("<?php echo $lang_ezbbc['Ask url img'] ?>", "");
       				                if (URL != '' && URL !=null && URL.indexOf('http://') == 0) {
       				                        startTag = '[img=' + currentSelection + ']';
       				                        currentSelection = URL;
       				                } else {
       				                        startTag = endTag = '';
       				                }
       				        }
       				} else { //No selection, we ask for the URL and the alt text
       				        var URL = prompt("<?php echo $lang_ezbbc['Ask url img'] ?>", "");
       				         if (URL != '' && URL != null && currentSelection.indexOf('http://') != 0) {
       				                 var alt = prompt("<?php echo $lang_ezbbc['Ask alt'] ?>", "");
       				                 if (alt !='' && alt != null) {
       				                         startTag = '[img=' + alt + ']';
       				                         currentSelection = URL;
       				                 } else {
       				                         currentSelection = URL;
       				                 }
       				         } else {
       				                  startTag = endTag = '';
       				         }
       				}
       			break;
       			case "quote":
                       	       if (currentSelection) { //Something is selected
                       	               var author = prompt("<?php echo $lang_ezbbc['Ask author'] ?>", "");
        			        if (author != '' && author != null) {
        			        	startTag = '[quote=' + author + ']\n';
        			        	endTag = '\n[/quote]';
        			        }
        			} else { //Nothing is selected
        			        var citation = prompt("<?php echo $lang_ezbbc['Ask quotation'] ?>", "");
        			        if (citation != '' && citation != null) {
        			                var author = prompt("<?php echo $lang_ezbbc['Ask author'] ?>", "");
        			                if (author != '' && author != null) {
        			                        startTag = '[quote=' + author + ']\n';
        			                        endTag = '\n[/quote]';
        			                        currentSelection = citation;
        			                } else {
        			                        startTag = '[quote]\n';
        			                        endTag = '\n[/quote]';
        			        	  	currentSelection = citation;
        			        	}
        			        } else {
        			                startTag = endTag = '';
        			        }
        			}
        		break;
        		case "code":
                       	       if (currentSelection) { //Something is selected
                       	               var language = prompt("<?php echo $lang_ezbbc['Ask language'] ?>", "");
        			        if (language != '' && language != null) {
        			        	startTag = '[code]\n[== ' + language + ' ==]\n';
        			        	endTag = '\n[/code]';
        			        }
        			} else { //Nothing is selected
        			        var code = prompt("<?php echo $lang_ezbbc['Ask code'] ?>", "");
        			        if (code != '' && code != null) {
        			                var language = prompt("<?php echo $lang_ezbbc['Ask language'] ?>", "");
        			                if (language != '' && language != null) {
        			                        startTag = '[code]\n[== ' + language + ' ==]\n';
        			                        endTag = '\n[/code]';
        			                        currentSelection = code;
        			                } else {
        			                        startTag = '[/code]\n';
        			        	  	endTag = '\n[/code]';
        			                        currentSelection = code;
        			        	}
        			        } else {
        			                startTag = endTag = '';
        			        }
        			}
        		break;
       			case "unorderedlist":
       				if (currentSelection) { //Something is selected
       				        var item = currentSelection.split('\n');
       				        for(i=0;i<item.length;i++) {
       				                item[i] = '[*]' + item[i] + '[/*]';
					}
					currentSelection = '\n' + item.join("\n") + '\n';
       				} else { //No selection, we ask for the different list items
       				        var item = new Array();
       				        var i = 0;
       				        do {
       				               var itemCount = i+1;
       				               if (itemCount == 2){
       				                       alert("<?php echo $lang_ezbbc['Ask item explanation'] ?>");
       				               }
       				               item[i] = prompt("<?php echo $lang_ezbbc['Ask item'] ?>" + itemCount, "");
       				               i+=1;
       				        }
       				        while (item[i-1] != '' && item[i-1] != null);
       				        var count = item.length-1; //To avoid taking in account the last empty item
       				         if (count != 0 && item[i-1] != null) {
       				                for (i=0;i<count;i++) {
       				                        item[i] = '[*]' + item[i] + '[/*]';
       				                }
       				                currentSelection = '\n' + item.join("\n");
       				        } else {
       				                startTag = endTag = '';
       				        }
       				}
       			break;
       			case "orderedlist":
       				if (currentSelection) { //Something is selected
       				        var item = currentSelection.split('\n');
       				        for(i=0;i<item.length;i++) {
       				                item[i] = '[*]' + item[i] + '[/*]';
					}
					currentSelection = '\n' + item.join("\n") + '\n';
       				} else { //No selection, we ask for the different list items
       				        var item = new Array();
       				        var i = 0;
       				        do {
       				               var itemCount = i+1;
       				               if (itemCount == 2){
       				                       alert("<?php echo $lang_ezbbc['Ask item explanation'] ?>");
       				               }
       				                 item[i] = prompt("<?php echo $lang_ezbbc['Ask item'] ?>" + itemCount, "");
       				               i+=1;
       				        }
       				        while (item[i-1] != '' && item[i-1] != null);
       				        var count = item.length-1; //To avoid taking in account the last empty item
       				        if (count != 0 && item[i-1] != null) {
       				                for (i=0;i<count;i++) {
       				                        item[i] = '[*]' + item[i] + '[/*]';
       				                }
       				                currentSelection = '\n' + item.join("\n");
       				        } else {
       				                startTag = endTag = '';
       				        }
       				}
       			break;
       			case "alphaorderedlist":
       				if (currentSelection) { //Something is selected
       				        var item = currentSelection.split('\n');
       				        for(i=0;i<item.length;i++) {
       				                item[i] = '[*]' + item[i] + '[/*]';
					}
					currentSelection = '\n' + item.join("\n") + '\n';
       				} else { //No selection, we ask for the different list items
       				        var item = new Array();
       				        var i = 0;
       				        do {
       				               var itemCount = i+1;
       				               if (itemCount == 2){
       				                       alert("<?php echo $lang_ezbbc['Ask item explanation'] ?>");
       				               }
       				               item[i] = prompt("<?php echo $lang_ezbbc['Ask item'] ?>" + itemCount, "");
       				               i+=1;
       				        }
       				        while (item[i-1] != '' && item[i-1] != null);
       				        var count = item.length-1; //To avoid taking in account the last empty item
       				        if (count != 0 && item[i-1] != null) {
       				                for (i=0;i<count;i++) {
       				                        item[i] = '[*]' + item[i] + '[/*]';
       				                }
       				                currentSelection = '\n' + item.join("\n");
       				        } else {
       				                startTag = endTag = '';
       				        }
       				}
       			break;
       		       case "smiley":
       		                       if (window.ActiveXObject) { //For IE
       		                                //Calculating the start point of the selection
	                                        var storedRange = textRange.duplicate();
	                                        storedRange.moveToElementText(field);
	                                        storedRange.setEndPoint('EndToEnd', textRange);
	                                        field.selectionStart = storedRange.text.length - currentSelection.length;
                                               if (field.selectionStart == 0) { //We are at the beginning of the textarea
       		                                        startTag = ' ' + startTag + ' ';
       		                                        currentSelection = '';
       		                               } else { //We are not at the beginning of the textarea, extending the text selection to handle the previous and next character
       		                                       textRange.moveStart('character', -1);
       		                                       textRange.moveEnd('character');
       		                                       textRange.select();
       		                                       currentSelection = textRange.text;
       		                                       if (currentSelection.length == 1) { //Case caret at the end of a line
       		                                               startTag = currentSelection + ' ' + startTag + '\n';
       		                                               currentSelection = '';
       		                                       }
       		                               }
       		                       
       		                       } else { //For other browsers
       		                               if (startSelection.length == 0) { //Looking if we are at the beginning of the textarea
       		                                        startTag = ' ' + startTag + ' ';
       		                                        currentSelection = '';
       		                               } else { //Textarea not empty, extending the text selection to handle the previous and next character
       		                                       field.setSelectionRange(startSelection.length -1, startSelection.length + currentSelection.length +1);
       		                                       startSelection = field.value.substring(0, field.selectionStart);
       		                                       currentSelection = field.value.substring(field.selectionStart, field.selectionEnd);
       		                                       endSelection = field.value.substring(field.selectionEnd);
       		                                       if (currentSelection.length == 1) { //Case at the end of a line
       		                                               startTag = currentSelection + ' ' + startTag + ' ';
       		                                               currentSelection = '';
       		                                       }
       		                               }
       		                       }
       		                       
       		                       //Common situations for all browsers
       		                       if (currentSelection.length >= 2) {
       		                               //To ease checking, variable definition
       		                               var charBefore = currentSelection.substr(0,1);
       		                               var charAfter = currentSelection.substr(currentSelection.length-1,1);
       		                               //Parsing and treating the new selection
       		                               if (charBefore != ' ' && charAfter != ' ') {
       		                                       //Adding a space before and after the smiley
       		                                       startTag = charBefore + ' ' + startTag + ' ';
       		                                       endTag = charAfter;
       		                               } else if (charBefore != ' ') {
       		                                       //Adding a space before the smiley
       		                                       startTag = charBefore + ' ' + startTag + ' ';
       		                               } else if (charAfter != ' ') {
       		                                       //Adding a space after the smiley
       		                                       currentSelection = startTag;
       		                                       startTag = ' ' + startTag + ' ';
       		                                       endTag = charAfter;
       		                               } else {
       		                                       startTag = ' ' + starTag + ' ';
       		                               }
       		                               currentSelection = '';
       		                       }
       		        break;

       		       case "video":
               	       if (currentSelection) { //Something is selected
           			        if (currentSelection.length > 4 && currentSelection.substr(0,4).toLowerCase() != "http" && currentSelection.substr(0,4).toLowerCase() != "www") {
           			        	startTag = '[video]';
           			        	endTag = '[/video]';
           			        } else {
           			                startTag = endTag = '';
           			        } //End if - else.
           			        
           				} else { //Nothing is selected
               				var video = prompt("<?php echo $lang_ezbbc['video'] ?> (<?php echo $lang_ezbbc['video_info'] ?>)", "");
           			        if (video != '' && video != null && video.length > 4) {
               			        if (video.substr(0,4).toLowerCase() != "http" && video.substr(0,4).toLowerCase() != "www") {
               			        	startTag = '[video]';
               			        	endTag = '[/video]';
               			        	currentSelection = video;
               			        } else {
               			                startTag = endTag = '';
               			        } //End if - else.
           			        } else {
           			                startTag = endTag = '';
           			        } //End if - else.
           				} //End if - else.

					break;
       		}
        }
        
        /* === Part 3: adding what was produced === */
        if (window.ActiveXObject) { //For IE
                textRange.text = startTag + currentSelection + endTag;
                textRange.moveStart('character', -endTag.length - currentSelection.length);
                textRange.moveEnd('character', -endTag.length);
                textRange.select();
        } else { //For other browsers
                field.value = startSelection + startTag + currentSelection + endTag + endSelection;
                field.focus();
                field.setSelectionRange(startSelection.length + startTag.length, startSelection.length + startTag.length + currentSelection.length);
        }

        field.scrollTop = scroll;
}
//Function to make the smilies visible or not
function sVisibility() {
	var smilies_bar = document.getElementById("ezbbc_s");
	if (smilies_bar.style.display == "none") {
		smilies_bar.style.display = "inline";
	} else {
		smilies_bar.style.display = "none";
	}
}
		
//Function to create the Toolbar
function doToolbar() {
        var toolbar = '';
// Toolbar for common textareas
<?php if ($textarea_name == 'req_message'): ?>
  <?php if ($pun_config['o_smilies'] == '1'): ?>
        // Smileys
        toolbar += '<span id="ezbbc_s" style="display: none;">';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>smile.png" title=":)" alt=":)" onclick="insertTag(\':)\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>neutral.png" title=":|" alt=":|" onclick="insertTag(\':|\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>sad.png" title=":(" alt=":(" onclick="insertTag(\':(\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>big_smile.png" title=":D" alt=":D" onclick="insertTag(\':D\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>yikes.png" title=":o" alt=":o" onclick="insertTag(\':o\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>wink.png" title=";)" alt=";)" onclick="insertTag(\';)\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>hmm.png" title=":/" alt=":/" onclick="insertTag(\':/\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>tongue.png" title=":p" alt=":p" onclick="insertTag(\':p\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>lol.png" title=":lol:" alt=":lol:" onclick="insertTag(\':lol:\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>mad.png" title=":mad:" alt=":mad:" onclick="insertTag(\':mad:\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>roll.png" title=":rolleyes:" alt=":rolleyes:" onclick="insertTag(\':rolleyes:\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>cool.png" title=":cool:" alt=":cool:" onclick="insertTag(\':cool:\',\'\',\'smiley\')" />';
    <?php if ($ezbbc_smilies_set == 'ezbbc_smilies'): ?>
    // Additional smilies if ezbbc smilies enabled
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>angel.png" title="O:)" alt="O:)" onclick="insertTag(\'O:)\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>cry.png" title="8.(" alt="8.(" onclick="insertTag(\'8.(\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>devil.png" title="]:D" alt="]:D" onclick="insertTag(\']:D\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>glasses.png" title="8)" alt="8)" onclick="insertTag(\'8)\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>kiss.png" title="{)" alt="{)" onclick="insertTag(\'{)\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>monkey.png" title="8o" alt="8o" onclick="insertTag(\'8o\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>ops.png" title=":8" alt=":8" onclick="insertTag(\':8\',\'\',\'smiley\')" />';
    <?php endif; ?>
	toolbar += '<\/span>';
	toolbar += '<br />';
  <?php endif; ?>
  
  <?php if ($pun_config['p_message_bbcode'] == '1'): ?>
  // if BBcode enabled
	// Text style
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/bold.png" title="<?php echo $lang_ezbbc['Bold'] ?>" alt="<?php echo $lang_ezbbc['Bold'] ?>" onclick="insertTag(\'[b]\',\'[/b]\',\'\')" />';
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/underline.png" title="<?php echo $lang_ezbbc['Underline'] ?>" alt="<?php echo $lang_ezbbc['Underline'] ?>" onclick="insertTag(\'[u]\',\'[/u]\',\'\')" />';
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/italic.png" title="<?php echo $lang_ezbbc['Italic'] ?>" alt="<?php echo $lang_ezbbc['Italic'] ?>" onclick="insertTag(\'[i]\',\'[/i]\',\'\')" />';
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/strike-through.png" title="<?php echo $lang_ezbbc['Strike-through'] ?>" alt="<?php echo $lang_ezbbc['Strike-through'] ?>" onclick="insertTag(\'[s]\',\'[/s]\',\'\')" />';
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/delete.png" title="<?php echo $lang_ezbbc['Delete'] ?>" alt="<?php echo $lang_ezbbc['Delete'] ?>" onclick="insertTag(\'[del]\',\'[/del]\',\'\')" />';
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/insert.png" title="<?php echo $lang_ezbbc['Insert'] ?>" alt="<?php echo $lang_ezbbc['Insert'] ?>" onclick="insertTag(\'[ins]\',\'[/ins]\',\'\')" />';
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/emphasis.png" title="<?php echo $lang_ezbbc['Emphasis'] ?>" alt="<?php echo $lang_ezbbc['Emphasis'] ?>" onclick="insertTag(\'[em]\',\'[/em]\',\'\')" />';
	toolbar += '&#160;';

	// Color and heading
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/color.png" title="<?php echo $lang_ezbbc['Colorize'] ?>" alt="<?php echo $lang_ezbbc['Colorize'] ?>" onclick="insertTag(\'[color]\',\'[/color]\',\'color\')" />';
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/heading.png" title="<?php echo $lang_ezbbc['Heading'] ?>" alt="<?php echo $lang_ezbbc['Heading'] ?>" onclick="insertTag(\'[h]\',\'[/h]\',\'heading\')" />';
	toolbar += '&#160;';

	// Links and images
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/link.png" title="<?php echo $lang_ezbbc['URL'] ?>" alt="<?php echo $lang_ezbbc['URL'] ?>" onclick="insertTag(\'[url]\',\'[/url]\',\'link\')" />';
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/email.png" title="<?php echo $lang_ezbbc['E-mail'] ?>" alt="<?php echo $lang_ezbbc['E-mail'] ?>" onclick="insertTag(\'[email]\',\'[/email]\',\'email\')" />';
    
    <?php if ($pun_config['p_message_img_tag'] == '1'): ?>
    // if image tag enabled
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/image.png" title="<?php echo $lang_ezbbc['Image'] ?>" alt="<?php echo $lang_ezbbc['Image'] ?>" onclick="insertTag(\'[img]\',\'[/img]\',\'img\')" />';
    <?php endif; ?>
	toolbar += '&#160;';

	// Quote and code
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/quote.png" title="<?php echo $lang_ezbbc['Quote'] ?>" alt="<?php echo $lang_ezbbc['Quote'] ?>" onclick="insertTag(\'[quote]\\n\',\'\\n[/quote]\',\'quote\')" />';
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/code.png" title="<?php echo $lang_ezbbc['Code'] ?>" alt="<?php echo $lang_ezbbc['Code'] ?>" onclick="insertTag(\'[code]\\n\',\'\\n[/code]\',\'code\')" />';
	toolbar += '&#160;';

	// Lists
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/list-unordered.png" title="<?php echo $lang_ezbbc['Unordered List'] ?>" alt="<?php echo $lang_ezbbc['Unordered List'] ?>" onclick="insertTag(\'[list=*]\',\'[/list]\',\'unorderedlist\')" />';
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/list-ordered.png" title="<?php echo $lang_ezbbc['Ordered List'] ?>" alt="<?php echo $lang_ezbbc['Ordered List'] ?>" onclick="insertTag(\'[list=1]\',\'[/list]\',\'orderedlist\')" />';
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/list-ordered-alpha.png" title="<?php echo $lang_ezbbc['Alphabetical Ordered List'] ?>" alt="<?php echo $lang_ezbbc['Alphabetical Ordered List'] ?>" onclick="insertTag(\'[list=a]\',\'[/list]\',\'alphaorderedlist\')" />';
  <?php endif; ?>
  // End if BBCode enabled
  
   <?php if ($pun_config['o_smilies'] == '1'): ?>
        // Smilies button
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/smilie.png" title="<?php echo $lang_ezbbc['Smilies toggle'] ?>" alt="<?php echo $lang_ezbbc['Smilies toggle'] ?>" onclick="sVisibility()" />';
  <?php endif; ?>
  <?php if ($pun_config['o_smilies'] == '1' || $pun_config['p_message_bbcode'] == '1'): ?>

	//Video link
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/video.png" title="<?php echo $lang_ezbbc['video_button'] ?>" alt="<?php echo $lang_ezbbc['video_button'] ?>" onclick="insertTag(\'[video]\\n\',\'\\n[/video]\',\'video\')" />';
	
  
	// Help link
	toolbar += '<a class="toolbar_help" href="<?php echo $help_file_path ?>" title="<?php echo $lang_ezbbc['Toolbar help'] ?>" onclick="window.open(this.href, \'Toolbar_help\', \'height=400, width=750, top=50, left=50, toolbar=yes, menubar=yes, location=no, resizable=yes, scrollbars=yes, status=no\'); return false;"><img src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/help.png" alt="<?php echo $lang_ezbbc['Toolbar help'] ?>" /><\/a>';
	
	toolbar += '<br />';
  <?php endif; ?>
// End Toolbar for common teaxtareas
<?php endif; ?>

<?php if ($textarea_name == 'signature'): ?>
// Toolbar for signature textarea
  <?php if ($pun_config['o_smilies_sig'] == '1'): ?>
        // Smileys
        toolbar += '<span id="ezbbc_s" style="display: none;">';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>smile.png" title=":)" alt=":)" onclick="insertTag(\':)\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>neutral.png" title=":|" alt=":|" onclick="insertTag(\':|\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>sad.png" title=":(" alt=":(" onclick="insertTag(\':(\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>big_smile.png" title=":D" alt=":D" onclick="insertTag(\':D\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>yikes.png" title=":o" alt=":o" onclick="insertTag(\':o\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>wink.png" title=";)" alt=";)" onclick="insertTag(\';)\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>hmm.png" title=":/" alt=":/" onclick="insertTag(\':/\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>tongue.png" title=":p" alt=":p" onclick="insertTag(\':p\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>lol.png" title=":lol:" alt=":lol:" onclick="insertTag(\':lol:\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>mad.png" title=":mad:" alt=":mad:" onclick="insertTag(\':mad:\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>roll.png" title=":rolleyes:" alt=":rolleyes:" onclick="insertTag(\':rolleyes:\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>cool.png" title=":cool:" alt=":cool:" onclick="insertTag(\':cool:\',\'\',\'smiley\')" />';
    <?php if ($ezbbc_smilies_set == 'ezbbc_smilies'): ?>
        // Additional smilies if ezbbc smilies enabled
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>angel.png" title="O:)" alt="O:)" onclick="insertTag(\'O:)\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>cry.png" title="8.(" alt="8.(" onclick="insertTag(\'8.(\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>devil.png" title="]:D" alt="]:D" onclick="insertTag(\']:D\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>glasses.png" title="8)" alt="8)" onclick="insertTag(\'8)\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>kiss.png" title="{)" alt="{)" onclick="insertTag(\'{)\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>monkey.png" title="8o" alt="8o" onclick="insertTag(\'8o\',\'\',\'smiley\')" />';
        toolbar += '<img class="smiley" src="<?php echo $smilies_path ?>ops.png" title=":8" alt=":8" onclick="insertTag(\':8\',\'\',\'smiley\')" />';
    <?php endif; ?>
	toolbar += '<\/span>';
	toolbar += '<br />';
  <?php endif; ?>
  
  <?php if ($pun_config['p_sig_bbcode'] == '1'): ?>
  // if BBcode enabled
	// Text style
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/bold.png" title="<?php echo $lang_ezbbc['Bold'] ?>" alt="<?php echo $lang_ezbbc['Bold'] ?>" onclick="insertTag(\'[b]\',\'[/b]\',\'\')" />';
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/underline.png" title="<?php echo $lang_ezbbc['Underline'] ?>" alt="<?php echo $lang_ezbbc['Underline'] ?>" onclick="insertTag(\'[u]\',\'[/u]\',\'\')" />';
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/italic.png" title="<?php echo $lang_ezbbc['Italic'] ?>" alt="<?php echo $lang_ezbbc['Italic'] ?>" onclick="insertTag(\'[i]\',\'[/i]\',\'\')" />';
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/strike-through.png" title="<?php echo $lang_ezbbc['Strike-through'] ?>" alt="<?php echo $lang_ezbbc['Strike-through'] ?>" onclick="insertTag(\'[s]\',\'[/s]\',\'\')" />';
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/delete.png" title="<?php echo $lang_ezbbc['Delete'] ?>" alt="<?php echo $lang_ezbbc['Delete'] ?>" onclick="insertTag(\'[del]\',\'[/del]\',\'\')" />';
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/insert.png" title="<?php echo $lang_ezbbc['Insert'] ?>" alt="<?php echo $lang_ezbbc['Insert'] ?>" onclick="insertTag(\'[ins]\',\'[/ins]\',\'\')" />';
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/emphasis.png" title="<?php echo $lang_ezbbc['Emphasis'] ?>" alt="<?php echo $lang_ezbbc['Emphasis'] ?>" onclick="insertTag(\'[em]\',\'[/em]\',\'\')" />';
	toolbar += '&#160;';

	// Color
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/color.png" title="<?php echo $lang_ezbbc['Colorize'] ?>" alt="<?php echo $lang_ezbbc['Colorize'] ?>" onclick="insertTag(\'[color]\',\'[/color]\',\'color\')" />';
	toolbar += '&#160;';

	// Links and images
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/link.png" title="<?php echo $lang_ezbbc['URL'] ?>" alt="<?php echo $lang_ezbbc['URL'] ?>" onclick="insertTag(\'[url]\',\'[/url]\',\'link\')" />';
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/email.png" title="<?php echo $lang_ezbbc['E-mail'] ?>" alt="<?php echo $lang_ezbbc['E-mail'] ?>" onclick="insertTag(\'[email]\',\'[/email]\',\'email\')" />';
    // if image tag enabled
    <?php if ($pun_config['p_sig_img_tag'] == '1'): ?>
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/image.png" title="<?php echo $lang_ezbbc['Image'] ?>" alt="<?php echo $lang_ezbbc['Image'] ?>" onclick="insertTag(\'[img]\',\'[/img]\',\'img\')" />';
    <?php endif; ?>
	toolbar += '&#160;';
  // End if BBCode enabled
  <?php endif; ?>
  
  <?php if ($pun_config['o_smilies_sig'] == '1'): ?>
        //  smilies button
	toolbar += '<img class="button" src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/smilie.png" title="<?php echo $lang_ezbbc['Smilies toggle'] ?>" alt="<?php echo $lang_ezbbc['Smilies toggle'] ?>" onclick="sVisibility()" />';
  <?php endif; ?>
  
  <?php if ($pun_config['o_smilies_sig'] == '1' || $pun_config['p_sig_bbcode'] == '1'): ?>
	// Help link
	toolbar += '<a class="toolbar_help" href="<?php echo $help_file_path ?>" title="<?php echo $lang_ezbbc['Toolbar help'] ?>" onclick="window.open(this.href, \'Toolbar_help\', \'height=400, width=750, top=50, left=50, toolbar=yes, menubar=yes, location=no, resizable=yes, scrollbars=yes, status=no\'); return false;"><img src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/help.png" alt="<?php echo $lang_ezbbc['Toolbar help'] ?>" /><\/a>';
  	
	toolbar += '<br />';
  <?php endif; ?>
// End for Signature textarea
<?php endif; ?>

// Returning the right Toolbar
	return toolbar;
}

//Adding the Toolbar on the right place
function addToolbar(){
    var textarea = document.getElementsByName('<?php echo $textarea_name ?>')[0];
    if (textarea == null) {
		return;
    } //End if.
	var span = document.createElement('span');
	span.setAttribute("id","ezbbctoolbar");
	span.setAttribute("idName","ezbbctoolbar"); /* For IE */
	span.innerHTML = doToolbar();
	var html = textarea.parentNode;
	html.insertBefore(span,textarea);
}
window.addEventListener?window.addEventListener('load',addToolbar,false):window.attachEvent('onload',addToolbar);
/* ]]> */
</script>
<!-- EZBBC Toolbar integration end -->
<?php endif; ?>