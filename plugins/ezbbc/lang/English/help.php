<?php 
// Including common.php file to have access to fluxBB functions
define('PUN_ROOT', '../../../../');
require PUN_ROOT.'include/common.php';
// Retrieving style folder
$config_content = trim(file_get_contents(PUN_ROOT.'plugins/ezbbc/config.php'));
$config_item = explode(";", $config_content);
$ezbbc_style_folder = $config_item[2];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="<?php echo PUN_ROOT.'style/'.$pun_user['style'].'.css' ?>" />
<link rel="stylesheet" type="text/css" href="<?php echo PUN_ROOT.'plugins/ezbbc/style/'.$ezbbc_style_folder.'/ezbbc.css' ?>" />
<title>EZBBC Toolbar help</title>
</head>
<body>
<div class="pun">
<div class="punwrap">
<div id="brdmain">
<div  id="ezbbc_help">
        <ul id="menu">
                <li><a href="#common_buttons">Common buttons</a></li>
                <li><a href="#color_button">Color button</a></li>
                <li><a href="#heading_button">Heading button</a></li>
                <li><a href="#url_button">URL button</a></li>
                <li><a href="#email_button">E-mail button</a></li>
                <li><a href="#image_button">Image button</a></li>
                <li><a href="#quote_button">Quote button</a></li>
                <li><a href="#code_button">Code button</a></li>
                <li><a href="#list_buttons">List buttons</a></li>
                <li><a href="#smilies">Smilies</a></li>
        </ul>

<h1>EZBBC Toolbar help</h1>
        
        <h2 id="common_buttons" style="margin-right: 20%;">Common inline formating buttons</h2>
                <h3>Use</h3>
                        <p>
                        These buttons only insert a beginning and an ending tag to selected text. If no text is selected, then the tags are inserted and the cursor blinks between the beginning and ending tag.<br />
                        This is what it should look like for the Bold button: <code>[b]Selected text[/b]</code>
                        </p>
                <h3>Summary</h3>
                        <table>
                                <tbody>
                                        <tr>
                                        <th>Buttons</th>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/bold.png" alt="Bold" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/underline.png" alt="Underline" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/italic.png" alt="Italic" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/strike-through.png" alt="Strike-through" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/delete.png" alt="Delete" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/insert.png" alt="Insert" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/emphasis.png" alt="Emphasis" /></td>
                                        </tr>
                                        <tr>
                                        <th>Use</th>
                                        <td>Bold</td>
                                        <td>Underline</td>
                                        <td>Italic</td>
                                        <td>Strike-Through</td>
                                        <td>Delete</td>
                                        <td>Insert</td>
                                        <td>Emphasis</td>
                                        </tr>
                                        <tr>
                                        <th>BBCode Tags</th>
                                        <td><code>[b]…[/b]</code></td>
                                        <td><code>[u]…[/u]</code></td>
                                        <td><code>[i]…[/i]</code></td>
                                        <td><code>[s]…[/s]</code></td>
                                        <td><code>[del]…[/del]</code></td>
                                        <td><code>[ins]…[/ins]</code></td>
                                        <td><code>[em]…[/em]</code></td>
                                        </tr>
                                        <tr>
                                        <th>HTML tags</th>
                                        <td><code>&lt;strong&gt;…&lt;/strong&gt;</code></td>
                                        <td><code>&lt;span…&gt;…&lt;/span&gt;</code></td>
                                        <td><code>&lt;span…&gt;…&lt;/span&gt;</code></td>
                                        <td><code>&lt;span…&gt;…&lt;/span&gt;</code></td>
                                        <td><code>&lt;del&gt;…&lt;/del&gt;</code></td>
                                        <td><code>&lt;ins&gt;…&lt;/ins&gt;</code></td>
                                        <td><code>&lt;em&gt;…&lt;/em&gt;</code></td>
                                        </tr>
                                </tbody>
                        </table>

        <h2>Color and heading buttons</h2>
                <h3 id="color_button"><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/color.png" alt="Colorize" /> Color button</h3>
                        <p>
                        The color button will be used to colorize the selected text. First select the text you want to change the color of then you have to enter in the displaying input field a color name (red, green, blue, purple, …) - if you want to know them all have a look to <a href="http://www.somacon.com/p142.php" onclick="window.open(this.href, 'Color_name', 'height=500, width=310, top=10, left=650, toolbar=no, menubar=no, location=no, resizable=yes, scrollbars=yes, status=no'); return false;">this page</a> - or a hexadecimal color code (ex.: #DDDDDD) - you can find this hex code by using <a href="http://www.colorpicker.com/" title="Open the color picker" onclick="window.open(this.href, 'Color_picker', 'height=430, width=550, top=10, left=300, toolbar=no, menubar=no, location=no, resizable=yes, scrollbars=yes, status=no'); return false;">this Color Picker</a> for example. If no text is selected, then the text "Text that has to be colorized" enclosed in <code>[color]</code> tags will be displayed and highlighted so that you can change it.<br/>
                        This is what it should look like for a red text: <code>[color=red]Selected text[/color]</code>.
                        </p>
                <h3 id="heading_button"><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/heading.png" alt="Heading" /> Heading button</h3>
                        <p>
                        The heading button formats the selected text into a title element. Just select the text that has to become a title and click on that button or click on the button (without selecting anything), enter a title, and validate.
                        </p>
                <h3>Summary</h3>
                        <table>
                                <tbody>
                                        <tr>
                                        <th>Buttons</th>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/color.png" alt="Colorize" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/heading.png" alt="Heading" /></td>
                                        </tr>
                                        <tr>
                                        <th>Use</th>
                                        <td>Colorized</td>
                                        <td>Title</td>
                                        </tr>
                                        <tr>
                                        <th>BBCode Tags</th>
                                        <td><code>[color=color_code]…[/color]</code></td>
                                        <td><code>[h]…[/h]</code></td>
                                        </tr>
                                        <tr>
                                        <th>HTML tags</th>
                                        <td><code>&lt;span…&gt;…&lt;/span&gt;</code></td>
                                        <td><code>&lt;h5&gt;…&lt;/h5&gt;</code></td>
                                        </tr>
                                </tbody>
                        </table>

        <h2>URL, E-mail and Image buttons</h2>
                <h3 id="url_button"><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/link.png" alt="URL" /> URL button</h3>
                        <p>
                        If you've selected text that isn't an URL, before clicking on the URL button, you should see appear an input box that ask you for the URL. The supported types are those who begins with: <code>http://</code>, <code>https://</code>, <code>ftp://</code>, or <code>www.</code>. If you didn't select any text, clicking on the URL button will popup an input box asking first for the address link then a second box will ask for the link label (optional).<br />
                        This is what it should look like: <code>[url=the_URL_you_entered]The label[/url]</code>.
                        </p>
                <h3 id="email_button"><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/email.png" alt="E-mail" /> E-mail button</h3>
                        <p>
                        If you selected text that isn't an E-mail address, before clicking on the E-mail button, you should see appear an input box that ask you for the E-mail address. You have to enter a valid E-mail address (containing a <code>@</code>). If you didn't select any text, clicking on the E-mail button will popup an input box asking first for the E-mail address then the link label (optional).<br />
                        This is what it should look like: <code>[email=the_address@you_entered]The label[/email]</code>.
                        </p>
                <h3 id="image_button"><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/image.png" alt="Image" /> Image button</h3>
                        <p>
                        If you selected text that isn't an URL, before clicking on the Image button, you should see appear an input box that ask you for the URL of the image. The selected text will be handled as the alternative text (<code>alt</code> attribute in HTML language). If you selected an URL, then you will be asked for an alternative text. If nothing has been selected, a prompt will as you first for the URL of the image, then for the alt text (this is optional).<br />
                        This is what it should look like: <code>[img=Your alt text]http://image_url.en[/img]</code>.
                        </p>
                <h3>Summary</h3>
                        <table>
                                <tbody>
                                        <tr>
                                        <th>Buttons</th>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/link.png" alt="URL" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/email.png" alt="E-mail" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/image.png" alt="Image" /></td>
                                        </tr>
                                        <tr>
                                        <th>Use</th>
                                        <td>A Web link</td>
                                        <td>An E-mail link</td>
                                        <td>An image</td>
                                        </tr>
                                        <tr>
                                        <th>BBCode Tags</th>
                                        <td><code>[url=http://website.com]…[/url]</code></td>
                                        <td><code>[email=your_email@somewhere.com]…[/email]</code></td>
                                        <td><code>[img=Alternative text]…[/img]</code></td>
                                        </tr>
                                        <tr>
                                        <th>HTML tags</th>
                                        <td><code>&lt;a href="http://…"&gt;…&lt;/a&gt;</code></td>
                                        <td><code>&lt;a href="mailto:…"&gt;…&lt;/a&gt;</code></td>
                                        <td><code>&lt;img src="…" alt="…" /&gt;</code></td>
                                        </tr>
                                </tbody>
                        </table>
        
        <h2>Quote and Code buttons</h2>
                <h3 id="quote_button"><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/quote.png" alt="Quote" /> Quote button</h3>
                        <p>
                        If nothing was selected, you will be prompted to enter a citation first then the author of this citation (optional). If something was selected, you will be asked for an author name.<br />
                        This is what it should look like:<br />
                        <code>[quote=Author name]<br />
                        Citation<br />
                        [/quote]</code>
                        </p>
                <h3 id="code_button"><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/code.png" alt="Code" /> Code button</h3>
                        <p>
                        If nothing was selected, you will be prompted to enter a code first then the language of this code (php, html, Javascript… - optional). If something was selected, you will be asked for a language.<br />
                        This is what it should look like:<br />
                        <code>[code]<br/>
                        [== language ==]<br />
                        Code<br />
                        [/code]</code>.
                        </p>
                <h3>Summary</h3>
                        <table>
                                <tbody>
                                        <tr>
                                        <th>Buttons</th>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/quote.png" alt="Quote" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/code.png" alt="Code" /></td>
                                        </tr>
                                        <tr>
                                        <th>Use</th>
                                        <td>Quote</td>
                                        <td>Code</td>
                                        </tr>
                                        <tr>
                                        <th>BBCode Tags</th>
                                        <td><code>[quote=Author name]…[/quote]</code></td>
                                        <td><code>[code]…[/code]</code></td>
                                        </tr>
                                        <tr>
                                        <th>HTML tags</th>
                                        <td><code>&lt;cite&gt;…&lt;/cite&gt;&lt;blockquote&gt;…&lt;/blockquote&gt;</code></td>
                                        <td><code>&lt;pre&gt;&lt;code&gt;…&lt;/code&gt;&lt;/pre&gt;</code></td>
                                        </tr>
                                </tbody>
                        </table>
        
        <h2 id="list_buttons">List buttons</h2>
                <h3>Use</h3>
                        <p>
                        If you selected multiple lines and clicked on one of the list button, each line will be considered as an item of the list. For example, if you selected 3 lines, you will get a list with 3 items. If you didn't select anything, a prompt will popup and ask you for the first item of the list. After you entered the first item and validate (click on OK button or hit the Enter key), an alert will be shown explaining what you have to do when you want to interrupt the item input: you just have to validate without entering anything in the input field.
                        </p>
                <h3>Summary</h3>
                        <table>
                                <tbody>
                                        <tr>
                                        <th>Buttons</th>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/list-unordered.png" alt="Unorderd list" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/list-ordered.png" alt="Ordered list" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/list-ordered-alpha.png" alt="Alphabetical ordered list" /></td>
                                        </tr>
                                        <tr>
                                        <th>Use</th>
                                        <td>An unordered list</td>
                                        <td>An ordered list</td>
                                        <td>An alphabetical ordered list</td>
                                        </tr>
                                        <tr>
                                        <th>BBCode Tags</th>
                                        <td style="text-align: left;"><code>[list=*]<br />[*]…[/*]<br />[*]…[/*]<br />[*]…[/*]<br />[/list]</code></td>
                                        <td style="text-align: left;"><code>[list=1]<br />[*]…[/*]<br />[*]…[/*]<br />[*]…[/*]<br />[/list]</code></td>
                                        <td style="text-align: left;"><code>[list=a]<br />[*]…[/*]<br />[*]…[/*]<br />[*]…[/*]<br />[/list]</code></td>
                                        </tr>
                                        <tr>
                                        <th>HTML tags</th>
                                        <td style="text-align: left;"><code>&lt;ul&gt;<br />&lt;li&gt;…&lt;/li&gt;<br />&lt;li&gt;…&lt;/li&gt;<br />&lt;li&gt;…&lt;/li&gt;<br />&lt;/ul&gt;</code></td>
                                        <td style="text-align: left;"><code>&lt;ol&gt;<br />&lt;li&gt;…&lt;/li&gt;<br />&lt;li&gt;…&lt;/li&gt;<br />&lt;li&gt;…&lt;/li&gt;<br />&lt;/ol&gt;</code></td>
                                        <td style="text-align: left;"><code>&lt;ol type="a"&gt;<br />&lt;li&gt;…&lt;/li&gt;<br />&lt;li&gt;…&lt;/li&gt;<br />&lt;li&gt;…&lt;/li&gt;<br />&lt;/ol&gt;</code></td>
                                        </tr>
                                </tbody>
                        </table>
                        
         <h2 id="smilies">Smilies</h2>
                <table>
                                <tbody>
                                        <tr>
                                        <th>BBCode Tags</th>
                                        <td><code>:)</code><br />or<br /><code>=)</code></td>
                                        <td><code>:|</code><br />or<br /><code>=)</code></td>
                                        <td><code>:(</code><br />or<br /><code>=(</code></td>
                                        <td><code>:D</code><br />or<br /><code>=D</code></td>
                                        <td><code>:o</code><br />or<br /><code>:O</code></td>
                                        <td><code>;)</code></td>
                                        <td><code>:/</code></td>
                                        <td><code>:P</code><br />or<br /><code>:p</code></td>
                                        <td><code>:lol:</code></td>
                                        <td><code>:mad:</code></td>
                                        <td><code>:rolleyes:</code></td>
                                        <td><code>:cool:</code></td>
                                        </tr>
                                        <tr>
                                        <th>FluxBB default smilies</th>
                                        <td><img src="../../../../img/smilies/smile.png" alt=":)" /></td>
                                        <td><img src="../../../../img/smilies/neutral.png" alt=":|" /></td>
                                        <td><img src="../../../../img/smilies/sad.png" alt=":(" /></td>
                                        <td><img src="../../../../img/smilies/big_smile.png" alt=":D" /></td>
                                        <td><img src="../../../../img/smilies/yikes.png" alt=":o" /></td>
                                        <td><img src="../../../../img/smilies/wink.png" alt=";)" /></td>
                                        <td><img src="../../../../img/smilies/hmm.png" alt=":/" /></td>
                                        <td><img src="../../../../img/smilies/tongue.png" alt=":P" /></td>
                                        <td><img src="../../../../img/smilies/lol.png" alt=":lol:" /></td>
                                        <td><img src="../../../../img/smilies/mad.png" alt=":mad:" /></td>
                                        <td><img src="../../../../img/smilies/roll.png" alt=":rolleyes:" /></td>
                                        <td><img src="../../../../img/smilies/cool.png" alt=":cool:" /></td>
                                        </tr>
                                        <tr>
                                         <th>EZBBC custom smilies</th>
                                        <td><img src="../../style/smilies/smile.png" alt=":)" /></td>
                                        <td><img src="../../style/smilies/neutral.png" alt=":|" /></td>
                                        <td><img src="../../style/smilies/sad.png" alt=":(" /></td>
                                        <td><img src="../../style/smilies/big_smile.png" alt=":D" /></td>
                                        <td><img src="../../style/smilies/yikes.png" alt=":o" /></td>
                                        <td><img src="../../style/smilies/wink.png" alt=";)" /></td>
                                        <td><img src="../../style/smilies/hmm.png" alt=":/" /></td>
                                        <td><img src="../../style/smilies/tongue.png" alt=":P" /></td>
                                        <td><img src="../../style/smilies/lol.png" alt=":lol:" /></td>
                                        <td><img src="../../style/smilies/mad.png" alt=":mad:" /></td>
                                        <td><img src="../../style/smilies/roll.png" alt=":rolleyes:" /></td>
                                        <td><img src="../../style/smilies/cool.png" alt=":cool:" /></td>
                                        </tr>
                                        
                                </tbody>
                        </table>
                        
                        <table>
                                <tbody>
                                        <tr>
                                        <th>BBCode Tags</th>
                                        <td><code>O:)</code><br />or<br /><code>:angel:</code></td> 
                                        <td><code>8.(</code><br />or<br /><code>:cry:</code></td> 
                                        <td><code>]:D</code><br />or<br /><code>:devil:</code></td> 
                                        <td><code>8)</code><br />or<br /><code>:glasses:</code></td>
                                        <td><code>{)</code><br />or<br /><code>:kiss:</code></td>
                                        <td><code>8o</code><br />or<br /><code>:monkey:</code></td> 
                                        <td><code>:8</code><br />or<br /><code>:ops:</code></td>
                                        </tr>
                                        <tr>
                                        <th>FluxBB default smilies</th>
                                        <td>O:)</td> 
                                        <td>8.(</td> 
                                        <td>]:D</td> 
                                        <td>8)</td>
                                        <td>{)</td>
                                        <td>8o</td> 
                                        <td>:8</td>
                                        </tr>
                                        <tr>
                                         <th>EZBBC custom smilies</th>
                                        <td><img src="../../style/smilies/angel.png" alt="O:)" /></td> 
                                        <td><img src="../../style/smilies/cry.png" alt="8.(" /></td> 
                                        <td><img src="../../style/smilies/devil.png" alt="]:D" /></td>
                                        <td><img src="../../style/smilies/glasses.png" alt="8)" /></td>
                                        <td><img src="../../style/smilies/kiss.png" alt="{)" /></td>
                                        <td><img src="../../style/smilies/monkey.png" alt="8o" /></td> 
                                        <td><img src="../../style/smilies/ops.png" alt=":8" /></td>
                                        </tr>
                                        
                                </tbody>
                        </table>
</div>
</div>
</div>
</div>
</body>
</html>
