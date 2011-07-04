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
<title>Aiuto sulla EZBBC Toolbar</title>
</head>
<body>
<div class="pun">
<div class="punwrap">
<div id="brdmain">
<div  id="ezbbc_help">
        <ul id="menu">
                <li><a href="#common_buttons">Pulsanti comuni</a></li>
                <li><a href="#color_button">Pulsante di colorazione</a></li>
                <li><a href="#heading_button">Pulsante di intestazione</a></li>
                <li><a href="#url_button">Pulsante indirizzo</a></li>
                <li><a href="#email_button">Pulsante email</a></li>
                <li><a href="#image_button">Pulsante immagine</a></li>
                <li><a href="#quote_button">Pulsante citazione</a></li>
                <li><a href="#code_button">Pulsante codice</a></li>
                <li><a href="#list_buttons">Pulsante lista</a></li>
                <li><a href="#smilies">Emoticon</a></li>
        </ul>

<h1>EZBBC Toolbar</h1>
        
        <h2 id="common_buttons" style="margin-right: 20%;">Pulsanti di formattazione comune</h2>
                <h3>Utilizzo</h3>
                        <p>
                        I pulsanti di formattazione comune servono per aggiungere caratteristiche di uso frequente ai testi. Selezionare il testo da formattare e quindi premere il pulsante desiderato: un marcatore di apertura e uno di chiusura appariranno all'inizio e alla fine del testo. Se non è stato selezionato alcun testo, verranno inseriti i marcatori e il cursore  verrà spostato tra di essi.<br />
                        Esempio di formattazione utilizzando il pulsante Grassetto: <code>[b]Testo selezionato[/b]</code>                        </p>
                        <h3>Sommario</h3>
                        <table>
                                <tbody>
                                        <tr>
                                        <th>Pulsanti</th>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/bold.png" alt="Bold" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/underline.png" alt="Underline" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/italic.png" alt="Italic" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/strike-through.png" alt="Strike-through" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/delete.png" alt="Delete" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/insert.png" alt="Insert" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/emphasis.png" alt="Emphasis" /></td>
                                        </tr>
                                        <tr>
                                        <th>Uso</th>
                                        <td>Grassetto</td>
                                        <td>Sottolineato</td>
                                        <td>Corsivo</td>
                                        <td>Barrato</td>
                                        <td>Cancellato</td>
                                        <td>Inserito</td>
                                        <td>Enfasi</td>
                                        </tr>
                                        <tr>
                                        <th>Marcatori BBCode</th>
                                        <td><code>[b]…[/b]</code></td>
                                        <td><code>[u]…[/u]</code></td>
                                        <td><code>[i]…[/i]</code></td>
                                        <td><code>[s]…[/s]</code></td>
                                        <td><code>[del]…[/del]</code></td>
                                        <td><code>[ins]…[/ins]</code></td>
                                        <td><code>[em]…[/em]</code></td>
                                        </tr>
                                        <tr>
                                        <th>Marcatori HTML</th>
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

        <h2>Pulsanti per colorare e inserire intestazioni (titoli)</h2>
                <h3 id="color_button"><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/color.png" alt="Colorize" /> Pulsante di colorazione</h3>
                        <p>
                        Il pulsante di colorazione serve per attribuire a un testo un colore. Selezionare il testo da colorare e quindi inserire nel campo il nome del colore desiderato (esempio: &quot;red&quot;, &quot;green&quot;, &quot;blue&quot;, &quot;purple&quot;…) - si veda <a href="http://www.somacon.com/p142.php" onclick="window.open(this.href, 'Color_name', 'height=500, width=310, top=10, left=650, toolbar=no, menubar=no, location=no, resizable=yes, scrollbars=yes, status=no'); return false;">questa pagina</a> per una lista completa dei colori disponibili - oppure il codice esadecimale corrispondente (esempio: #dddddd) - si veda  <a href="http://www.colorpicker.com/" title="Open the color picker" onclick="window.open(this.href, 'Color_picker', 'height=430, width=550, top=10, left=300, toolbar=no, menubar=no, location=no, resizable=yes, scrollbars=yes, status=no'); return false;">questa pagina</a> per una lista. Se non è stato selezionato alcun testo, la frase "Testo da colorare" comparirà già selezionata tra i marcatori<code> [color][/color] per essere modificata</code>.<br/>
                        Esempio di formattazione utilizzando il pulsante color: <code>[color=red]Testo selezionato[/color]</code>.</p>
                        <h3 id="heading_button"><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/heading.png" alt="Heading" /> Pulsante di intestazione (titolo)</h3>
                        <p>
                        Il pulsante di intestazione trasforma il testo selezionato in un titolo (intestazione). Selezionare il testo da trasformare in titolo e cliccare sul pulsante. Se non è stato selezionato alcun testo inserire un testo e confermare.                        </p>
                        <h3>Sommario</h3>
                        <table>
                                <tbody>
                                        <tr>
                                        <th>Pulsanti</th>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/color.png" alt="Colorize" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/heading.png" alt="Heading" /></td>
                                        </tr>
                                        <tr>
                                        <th>Utilizzo</th>
                                        <td>Colorare</td>
                                        <td>Titolo</td>
                                        </tr>
                                        <tr>
                                        <th>Marcatori BBCode</th>
                                        <td><code>[color=color_code]…[/color]</code></td>
                                        <td><code>[h]…[/h]</code></td>
                                        </tr>
                                        <tr>
                                        <th>Marcatori HTML</th>
                                        <td><code>&lt;span style=&quot;color:…&quot; &gt;…&lt;/span&gt;</code></td>
                                        <td><code>&lt;h5&gt;…&lt;/h5&gt;</code></td>
                                        </tr>
                                </tbody>
                        </table>

        <h2>Pulsanti per inserire indirizzi web, email e immagini</h2>
                <h3 id="url_button"><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/link.png" alt="URL" /> Pulsante indirizzo web </h3>
                        <p>
                        Il pulsante di indirizzo web serve ad inserire un collegamento (hyperlink) su un testo. Selezionare un testo sul quale inserire un collegamento e cliccare sul pulsante. Selezionando un testo che non è un indirizzo web (diverso da, per esempio, http://www.sito.est), comparirà un modulo per inserirlo, altrimenti saranno inseriti semplicemente i marcatori necessari. Premendo il pulsante senza aver selezionato alcun testo, comparirà prima un modulo per inserire l'indirizzo del collegamento, e, quindi, un secondo modulo nel quale inserire il testo sul quale applicarlo (il testo è opzionale).<br />
                        Esempio di formattazione utilizzando il pulsante url: <code>[url=http://www.indirizzoweb.est]testo da trasformarte in link[/url]</code>.                        </p>
                        <h3 id="email_button"><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/email.png" alt="E-mail" /> Pulsante email</h3>
                        <p>
                        Il pulsante di email serve ad inserire un collegamento ad una email (esempio: &quot;mailto:nome@dominio.est&quot;). Selezionare un indirizzo email sul quale inserire il collegamento e cliccare sul pulsante. Selezionando un testo che non è un indirizzo email, comparirà un modulo nel quale inserirlo. &Egrave; necessario inserire un indirizzo email valido (comprensivo di @). Premendo il pulsante senza aver selezionato alcun testo, comparirà un modulo per inserire l'indirizzo del collegamento, e, quindi, un secondo modulo nel quale inserire il testo su cui applicarlo (il testo è opzionale).<br />
                        Esempio di formattazione utilizzando il pulsante email: <code>[email=indirizzo@email.est]mia email[/email]</code>.                        </p>
                        <h3 id="image_button"><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/image.png" alt="Image" /> Pulsante immagine</h3>
                        <p>
                        Il pulsante di inserimento immagine serve ad inserire una immagine. Selezionare l'indirizzo web dell'immagine e premere sul pulsante: saranno inseriti i marcatori necessari e un nuovo modulo per aggiungere il realtivo testo alternativo   (l'attributo <code>alt</code> nell'HTML). Selezionando un testo che non è un indirizzo web,   comparirà un modulo in cui inserirlo: il testo selezionato sarà utilizzato come testo alternativo. Premendo il pulsante senza aver selezionato alcun testo, comparirà prima un modulo per inserire l'indirizzo del collegamento, e, quindi, un secondo modulo nel quale inserire il testo alternativo (il testo è opzionale)<br />
                        Esempio di formattazione utilizzando il pulsante di inserimento immagine: <code>[img=testo alternativo]http://www.sito.est/immagine.jpg[/img]</code>.                        </p>
                        <h3>Sommario</h3>
                        <table>
                                <tbody>
                                        <tr>
                                        <th>Pulsanti</th>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/link.png" alt="URL" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/email.png" alt="E-mail" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/image.png" alt="Image" /></td>
                                        </tr>
                                        <tr>
                                        <th>Utilizzo</th>
                                        <td>Indirizzo web </td>
                                        <td>Indirizzo email </td>
                                        <td>Immagine</td>
                                        </tr>
                                        <tr>
                                        <th>Marcatori BBCode</th>
                                        <td><code>[url=http://sitoweb.est]…[/url]</code></td>
                                        <td><code>[email=indirizzo@dominio.est]…[/email]</code></td>
                                        <td><code>[img=testo alternativo]…[/img]</code></td>
                                        </tr>
                                        <tr>
                                        <th>Marcatori HTML</th>
                                        <td><code>&lt;a href="http://…"&gt;…&lt;/a&gt;</code></td>
                                        <td><code>&lt;a href="mailto:…"&gt;…&lt;/a&gt;</code></td>
                                        <td><code>&lt;img src="…" alt="…" /&gt;</code></td>
                                        </tr>
                                </tbody>
                        </table>
        
        <h2>Pulsanti di citazione e inserimento codice</h2>
                <h3 id="quote_button"><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/quote.png" alt="Quote" /> Pulsante di citazione</h3>
                        <p>
                        Premendo il pulsante senza aver selezionato alcun testo, comparirà un modulo per inserire il testo da citare e, quindi, un secondo modulo per inserire il nome dell'autore citato (il nome è opzionale). Se un testo è stato selezionato, comparirà un modulo nel quale inserire  il nome dell'autore citato.<br />
                        Esempio di formattazione utilizzando il pulsante di citazione:<br />
                        <code>[quote=nome autore]<br />
                        testo da citare
                        <br />
                        [/quote]</code>                        </p>
                <h3 id="code_button"><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/code.png" alt="Code" /> Pulsante di inserimento codice</h3>
                        <p>
                        Premendo il pulsante senza aver selezionato alcun testo, comparirà un modulo per inserire il codice e, quindi, un secondo modulo per inserire il linguaggio utilizzato (php, html, javascript... - opzionale). Se un testo è stato selezionato, comparirà un modulo nel quale inserire il linguaggio utilizzato.<br />
                        Esempio di formattazione utilizzando il pulsante di inserimento codice:<br />
                        <code>[code]<br/>
                        [== linguaggio ==]<br />
                        Codice<br />
                        [/code]</code>.                        </p>
                <h3>Sommario</h3>
                        <table>
                                <tbody>
                                        <tr>
                                        <th>Pulsanti</th>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/quote.png" alt="Quote" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/code.png" alt="Code" /></td>
                                        </tr>
                                        <tr>
                                        <th>Utilizzo</th>
                                        <td>Citazione</td>
                                        <td>Codice</td>
                                        </tr>
                                        <tr>
                                        <th>Marcatori BBCode</th>
                                        <td><code>[quote=nome autore]…[/quote]</code></td>
                                        <td><code>[code]…[/code]</code></td>
                                        </tr>
                                        <tr>
                                        <th>Marcatori HTML</th>
                                        <td><code>&lt;cite&gt;…&lt;/cite&gt;&lt;blockquote&gt;…&lt;/blockquote&gt;</code></td>
                                        <td><code>&lt;pre&gt;&lt;code&gt;…&lt;/code&gt;&lt;/pre&gt;</code></td>
                                        </tr>
                                </tbody>
                        </table>
        
        <h2 id="list_buttons">Pulsanti per liste </h2>
                <h3>Utilizzo</h3>
                        <p>
                        Selezionare più righe e cliccare sul pulsante: ciascuna di esse sarà automaticamente trasformata in un elemento della lista (puntata, numerica o alfabetica che sia). Premendo il pulsante senza aver selezionato alcuna riga, comparirà  un modulo per inserire il testo della prima riga, e, quindi, un secondo modulo che spiega ciò che è necessario fare quando si desidera interrompere l'inserimento di nuovi elementi alla lista, ovvero: lasciare il campo vuoto e premere su Ok.                        </p>
                        <h3>Sommario</h3>
                        <table>
                                <tbody>
                                        <tr>
                                        <th>Pulsanti</th>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/list-unordered.png" alt="Unorderd list" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/list-ordered.png" alt="Ordered list" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/list-ordered-alpha.png" alt="Alphabetical ordered list" /></td>
                                        </tr>
                                        <tr>
                                        <th>Utilizzo</th>
                                        <td>Lista puntata </td>
                                        <td>Lista numerica </td>
                                        <td>Lista alfabetica </td>
                                        </tr>
                                        <tr>
                                        <th>Marcatori BBCode</th>
                                        <td style="text-align: left;"><code>[list=*]<br />[*]…[/*]<br />[*]…[/*]<br />[*]…[/*]<br />[/list]</code></td>
                                        <td style="text-align: left;"><code>[list=1]<br />[*]…[/*]<br />[*]…[/*]<br />[*]…[/*]<br />[/list]</code></td>
                                        <td style="text-align: left;"><code>[list=a]<br />[*]…[/*]<br />[*]…[/*]<br />[*]…[/*]<br />[/list]</code></td>
                                        </tr>
                                        <tr>
                                        <th>Marcatori HTML</th>
                                        <td style="text-align: left;"><code>&lt;ul&gt;<br />&lt;li&gt;…&lt;/li&gt;<br />&lt;li&gt;…&lt;/li&gt;<br />&lt;li&gt;…&lt;/li&gt;<br />&lt;/ul&gt;</code></td>
                                        <td style="text-align: left;"><code>&lt;ol&gt;<br />&lt;li&gt;…&lt;/li&gt;<br />&lt;li&gt;…&lt;/li&gt;<br />&lt;li&gt;…&lt;/li&gt;<br />&lt;/ol&gt;</code></td>
                                        <td style="text-align: left;"><code>&lt;ol type="a"&gt;<br />&lt;li&gt;…&lt;/li&gt;<br />&lt;li&gt;…&lt;/li&gt;<br />&lt;li&gt;…&lt;/li&gt;<br />&lt;/ol&gt;</code></td>
                                        </tr>
                                </tbody>
                        </table>
                        
         <h2 id="smilies">Emoticon</h2>
                <table>
                                <tbody>
                                        <tr>
                                        <th>Marcatori BBCode</th>
                                        <td><code>:)</code><br />                                          o<br />
                                          <code>=)</code></td>
                                        <td><code>:|</code><br />                                          o<br />
                                          <code>=)</code></td>
                                        <td><code>:(</code><br />                                          o<br />
                                          <code>=(</code></td>
                                        <td><code>:D</code><br />                                          o<br />
                                          <code>=D</code></td>
                                        <td><code>:o</code><br />                                          o<br />
                                          <code>:O</code></td>
                                        <td><code>;)</code></td>
                                        <td><code>:/</code></td>
                                        <td><code>:P</code><br />                                          o<br />
                                          <code>:p</code></td>
                                        <td><code>:lol:</code></td>
                                        <td><code>:mad:</code></td>
                                        <td><code>:rolleyes:</code></td>
                                        <td><code>:cool:</code></td>
                                        </tr>
                                        <tr>
                                        <th>Emoticon predefinite (FluxBB)</th>
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
                                         <th>Emoticon personalizzate (EZBBC)</th>
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
                                        <th>Marcatori BBCode</th>
                                        <td><code>O:)</code><br />                                          o<br />
                                          <code>:angel:</code></td> 
                                        <td><code>8.(</code><br />                                          o<br />
                                          <code>:cry:</code></td> 
                                        <td><code>]:D</code><br />                                          o<br />
                                          <code>:devil:</code></td> 
                                        <td><code>8)</code><br />                                          o<br />
                                          <code>:glasses:</code></td>
                                        <td><code>{)</code><br />                                          o<br />
                                          <code>:kiss:</code></td>
                                        <td><code>8o</code><br />                                          o<br />
                                          <code>:monkey:</code></td> 
                                        <td><code>:8</code><br />                                          o<br />
                                          <code>:ops:</code></td>
                                        </tr>
                                        <tr>
                                        <th>Emoticon predefinite (FluxBB)</th>
                                        <td>O:)</td> 
                                        <td>8.(</td> 
                                        <td>]:D</td> 
                                        <td>8)</td>
                                        <td>{)</td>
                                        <td>8o</td> 
                                        <td>:8</td>
                                        </tr>
                                        <tr>
                                         <th>Emoticon personalizzate (EZBBC)</th>
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
