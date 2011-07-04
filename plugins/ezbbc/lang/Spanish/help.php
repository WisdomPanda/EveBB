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
<html xmlns="http://www.w3.org/1999/xhtml" lang="es">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="<?php echo PUN_ROOT.'style/'.$pun_user['style'].'.css' ?>" />
<link rel="stylesheet" type="text/css" href="<?php echo PUN_ROOT.'plugins/ezbbc/style/'.$ezbbc_style_folder.'/ezbbc.css' ?>" />
<title>EZBBC Toolbar ayuda</title>
</head>
<body>
<div class="pun">
<div class="punwrap">
<div id="brdmain">
<div  id="ezbbc_help">
        <ul id="menu">
                <li><a href="#common_buttons">Común botones</a></li>
                <li><a href="#color_button">Botón Color</a></li>
                <li><a href="#heading_button">Botones de cabecera</a></li>
                <li><a href="#url_button">Botón de URL</a></li>
                <li><a href="#email_button">Botón de E-mail</a></li>
                <li><a href="#image_button">Botón de Imagen</a></li>
                <li><a href="#quote_button">Botón Citar</a></li>
                <li><a href="#code_button">Botón de Código</a></li>
                <li><a href="#list_buttons">Botones de Lista</a></li>
                <li><a href="#smilies">Emoticones</a></li>
        </ul>

<h1>EZBBC Toolbar ayuda</h1>

        <h2 id="common_buttons">Línea común botones de formateo</h2>
                <h3>Usar</h3>
                        <p>
                        Estos botones sólo insertan una etiqueta de inicio y una final al texto seleccionado. Si no se selecciona texto, las etiquetas se insertan y parpadea el cursor entre las etiquetas inicial y final.<br />
                        Esto es lo que debe ser similar para el botón Negrita: <code>[b]Texto seleccionado[/b]</code>
                        </p>
                <h3>Sumario</h3>
                        <table>
                                <tbody>
                                        <tr>
                                        <th>Botones</th>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/bold.png" alt="Negrita" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/underline.png" alt="Subrayar" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/italic.png" alt="Itálica" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/strike-through.png" alt="Tachado" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/delete.png" alt="Borar" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/insert.png" alt="Insertar" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/emphasis.png" alt="Enfasis" /></td>
                                        </tr>
                                        <tr>
                                        <th>Usar</th>
                                        <td>Negrita</td>
                                        <td>Subrayar</td>
                                        <td>Ítalica</td>
                                        <td>Tachado</td>
                                        <td>Borrar</td>
                                        <td>Insertar</td>
                                        <td>Enfasis</td>
                                        </tr>
                                        <tr>
                                        <th>Etiquetas BBCode</th>
                                        <td><code>[b]…[/b]</code></td>
                                        <td><code>[u]…[/u]</code></td>
                                        <td><code>[i]…[/i]</code></td>
                                        <td><code>[s]…[/s]</code></td>
                                        <td><code>[del]…[/del]</code></td>
                                        <td><code>[ins]…[/ins]</code></td>
                                        <td><code>[em]…[/em]</code></td>
                                        </tr>
                                        <tr>
                                        <th>Etiquetas HTML</th>
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

        <h2>Color y los botones de cabecera</h2>
                <h3 id="color_button"><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/color.png" alt="Colorear" /> Botón Color</h3>
                        <p>
                        El botón de color se utiliza para colorear el texto seleccionado. Primero, seleccione el texto al que desea cambiar el color, entonces usted tiene que introducir en el campo de entrada el nombre de un color (rojo, verde, azul, violeta, …) - si quiere conocerlos todos, échele un vistazo a <a href="http://www.somacon.com/p142.php" onclick="window.open(this.href, 'Color_name', 'height=500, width=310, top=10, left=650, toolbar=no, menubar=no, location=no, resizable=yes, scrollbars=yes, status=no'); return false;">esta página</a> - o un código de color hexadecimal (ej.: #DDDDDD) - usted puede encontrar el código hexadecimal mediante el uso de <a href="http://www.colorpicker.com/" title="Open the color picker" onclick="window.open(this.href, 'Color_picker', 'height=430, width=550, top=10, left=300, toolbar=no, menubar=no, location=no, resizable=yes, scrollbars=yes, status=no'); return false;">este Selector de color</a> por ejemplo. Si no se selecciona texto, el texto "El texto que tiene que tener el mismo color" encerrado en las etiquetas <code>[color]</code> se mostrará y se destacará de manera que se puede cambiar.<br/>
                        Esto es lo que debe ser similar a un texto en rojo: <code>[color=red]Texto seleccionado[/color]</code>.
                        </p>
                <h3 id="heading_button"><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/heading.png" alt="Cabecera" /> Botones de cabecera</h3>
                        <p>
                        El título botones de cabecera en el texto seleccionado en un elemento de título. Sólo tienes que seleccionar el texto que debe convertirse en un título y pulsar en este botón o pulsar en el botón (sin seleccionar nada), introducir un título, y validar.
                        </p>
                <h3>Sumario</h3>
                        <table>
                                <tbody>
                                        <tr>
                                        <th>Botones</th>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/color.png" alt="Colorear" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/heading.png" alt="Cabecera" /></td>
                                        </tr>
                                        <tr>
                                        <th>Usar</th>
                                        <td>Colorear</td>
                                        <td>Título</td>
                                        </tr>
                                        <tr>
                                        <th>Etiquetas BBCode</th>
                                        <td><code>[color=color_code]…[/color]</code></td>
                                        <td><code>[h]…[/h]</code></td>
                                        </tr>
                                        <tr>
                                        <th>Etiquetas HTML</th>
                                        <td><code>&lt;span…&gt;…&lt;/span&gt;</code></td>
                                        <td><code>&lt;h5&gt;…&lt;/h5&gt;</code></td>
                                        </tr>
                                </tbody>
                        </table>

        <h2>Dirección Web, E-mail y Botones de Imagen</h2>
                <h3 id="url_button"><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/link.png" alt="URL" /> Botón de URL</h3>
                        <p>
                        Si ha seleccionado texto que no es una URL, antes de hacer clic en el botón de dirección, debería ver aparecer un cuadro de entrada donde le piden la dirección URL. Los tipos admitidos son los que inician con: <code>http://</code>, <code>https://</code>, <code>ftp://</code>, o <code>www.</code>. Si usted no ha seleccionado ningún texto, pulse en el botón URL aparecerá un cuadro de entrada preguntando primero por el enlace, a continuación, un segundo cuadro le pedirá la etiqueta del enlace (opcional).<br />
                        Esto es lo que debe ser similar: <code>[url=la_URL_que_introdujiste]La Etiqueta[/url]</code>.
                        </p>
                <h3 id="email_button"><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/email.png" alt="E-mail" /> Botón de E-mail</h3>
                        <p>
                        Si ha seleccionado texto que no es una dirección de correo electrónico, antes de pulsar en el botón de E-mail, deberá ver aparecer un cuadro de entrada que le pide la dirección de correo electrónico. Usted tiene que introducir una dirección válida de correo electrónico (contiendo una <code>@</code>). Si usted no ha seleccionado ningún texto, pulse en el botón de E-mail aparecerá un cuadro de entrada pidiendo la primera dirección de e-mail y la etiqueta de enlace (opcional).<br />
                        Esto es lo que debe ser similar: <code>[email=la_direccion@que_introdujiste]La etiqueta[/email]</code>.
                        </p>
                <h3 id="image_button"><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/image.png" alt="Imagen" /> Botón de Imagen</h3>
                        <p>
                        Si ha seleccionado texto que no es una URL, antes de pulsar en el botón de imagen, debe ver aparecer un cuadro de entrada pidiendole la dirección URL de la imagen. El texto seleccionado será tratado como el texto alternativo (<code>alt</code> atribuible en lenguaje HTML). Si ha seleccionado una dirección, entonces se le pidirá un texto alternativo. Si no se ha seleccionado, se le pedirá en primer lugar para la dirección URL de la imagen, luego para el texto alternativo (esto es opcional).<br />
                        Esto es lo que debe ser similar: <code>[img=Your alt text]http://image_url.en[/img]</code>.
                        </p>
                <h3>Summary</h3>
                        <table>
                                <tbody>
                                        <tr>
                                        <th>Botones</th>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/link.png" alt="URL" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/email.png" alt="E-mail" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/image.png" alt="Imagen" /></td>
                                        </tr>
                                        <tr>
                                        <th>Usar</th>
                                        <td>Un Enlace Web</td>
                                        <td>Un Enlace E-mail</td>
                                        <td>Una imagen</td>
                                        </tr>
                                        <tr>
                                        <th>Etiquetas BBCode</th>
                                        <td><code>[url=http://website.com]…[/url]</code></td>
                                        <td><code>[email=your_email@somewhere.com]…[/email]</code></td>
                                        <td><code>[img=Texto alternativo]…[/img]</code></td>
                                        </tr>
                                        <tr>
                                        <th>Etiquetas HTML</th>
                                        <td><code>&lt;a href="http://…"&gt;…&lt;/a&gt;</code></td>
                                        <td><code>&lt;a href="mailto:…"&gt;…&lt;/a&gt;</code></td>
                                        <td><code>&lt;img src="…" alt="…" /&gt;</code></td>
                                        </tr>
                                </tbody>
                        </table>
        
        <h2>Botones de Código y Citar</h2>
                <h3 id="quote_button"><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/quote.png" alt="Quote" /> Botón Citar</h3>
                        <p>
                        Si no se selecciona, se le pedirá que introduzca una primera cita a continuación, el autor de esta cita (opcional). Si algo fue seleccionado, se le pedirá un nombre de autor.<br />
                        Esto es lo que debe ser similar:<br />
                        <code>[quote=Nombre autor]<br />
                        Citado<br />
                        [/quote]</code>
                        </p>
                <h3 id="code_button"><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/code.png" alt="Código" /> Botón de Código</h3>
                        <p>
                        Si no fue seleccionado, se le pedirá que introduzca un código primero y luego el idioma de este código (PHP, HTML, Javascript… - opcional). Si seleccionó algo, se le pedirá el idioma.<br />
                        Esto es como debe verse:<br />
                        <code>[code]<br />
                        [== idioma ==]<br />
                        Código<br />
                        [/code]</code>
                        </p>
                <h3>Sumario</h3>
                        <table>
                                <tbody>
                                        <tr>
                                        <th>Botones</th>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/quote.png" alt="Citar" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/code.png" alt="Código" /></td>
                                        </tr>
                                        <tr>
                                        <th>Usar</th>
                                        <td>Citar</td>
                                        <td>Código</td>
                                        </tr>
                                        <tr>
                                        <th>Etiquets BBCode</th>
                                        <td><code>[quote=Nombre del autor]…[/quote]</code></td>
                                        <td><code>[code]…[/code]</code></td>
                                        </tr>
                                        <tr>
                                        <th>Etiquetas HTML</th>
                                        <td><code>&lt;blockquote&gt;…&lt;/blockquote&gt;</code></td>
                                        <td><code>&lt;pre&gt;&lt;code&gt;…&lt;/code&gt;&lt;/pre&gt;</code></td>
                                        </tr>
                                </tbody>
                        </table>
        
        <h2 id="list_buttons">Botones de Lista</h2>
                <h3>Use</h3>
                        <p>
                        Si ha seleccionado varias líneas y pulsó en un botón de la lista, cada línea será considerada como un elemento de la lista. Por ejemplo, si ha seleccionado 3 líneas, obtendrá una lista con 3 elementos. Si usted no selecciona nada, aparecerá un mensaje y le preguntará por el primer artículo de la lista. Después de haber introducido el primer punto y validar (pulse en el botón Aceptar o presione la tecla Enter), una alerta se mostrará explicando lo que tienes que hacer cuando se quiere interrumpir la entrada de artículo: sólo tienes que validar sin entrar nada en el campo de entrada.
                        </p>
                <h3>Sumario</h3>
                        <table>
                                <tbody>
                                        <tr>
                                        <th>Botones</th>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/list-unordered.png" alt="Lista desordenada" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/list-ordered.png" alt="Lista ordenada" /></td>
                                        <td><img class="button" src="../../style/<?php echo $ezbbc_style_folder ?>/images/list-ordered-alpha.png" alt="Lista ordenada alfabeticamente" />										</td>
                                        </tr>
                                        <tr>
                                        <th>Usar</th>
                                        <td>Una lista desordenada</td>
                                        <td>Una lista ordenada</td>
                                        <td>Una lista ordenada alfabeticamente</td>
                                        </tr>
                                        <tr>
                                        <th>Etiquetas BBCode</th>
                                        <td style="text-align: left;"><code>[list=*]<br />[*]…[/*]<br />[*]…[/*]<br />[*]…[/*]<br />[/list]</code></td>
                                        <td style="text-align: left;"><code>[list=1]<br />[*]…[/*]<br />[*]…[/*]<br />[*]…[/*]<br />[/list]</code></td>
                                        <td style="text-align: left;"><code>[list=a]<br />[*]…[/*]<br />[*]…[/*]<br />[*]…[/*]<br />[/list]</code></td>
                                        </tr>
                                        <tr>
                                        <th>Etiquetas HTML</th>
                                        <td style="text-align: left;"><code>&lt;ul&gt;<br />&lt;li&gt;…&lt;/li&gt;<br />&lt;li&gt;…&lt;/li&gt;<br />&lt;li&gt;…&lt;/li&gt;<br />&lt;/ul&gt;</code></td>
                                        <td style="text-align: left;"><code>&lt;ol&gt;<br />&lt;li&gt;…&lt;/li&gt;<br />&lt;li&gt;…&lt;/li&gt;<br />&lt;li&gt;…&lt;/li&gt;<br />&lt;/ol&gt;</code></td>
                                        <td style="text-align: left;"><code>&lt;ol type="a"&gt;<br />&lt;li&gt;…&lt;/li&gt;<br />&lt;li&gt;…&lt;/li&gt;<br />&lt;li&gt;…&lt;/li&gt;<br />&lt;/ol&gt;</code></td>
                                        </tr>
                                </tbody>
                        </table>

         <h2 id="smilies">Emoticón</h2>
                <table>
                                <tbody>
                                        <tr>
                                        <th>BBCode</th>
                                        <td><code>:)</code><br />o<br /><code>=)</code></td>
                                        <td><code>:|</code><br />o<br /><code>=)</code></td>
                                        <td><code>:(</code><br />o<br /><code>=(</code></td>
                                        <td><code>:D</code><br />o<br /><code>=D</code></td>
                                        <td><code>:o</code><br />o<br /><code>:O</code></td>
                                        <td><code>;)</code></td>
                                        <td><code>:/</code></td>
                                        <td><code>:P</code><br />r<br /><code>:p</code></td>
                                        <td><code>:lol:</code></td>
                                        <td><code>:mad:</code></td>
                                        <td><code>:rolleyes:</code></td>
                                        <td><code>:cool:</code></td>
                                        </tr>
                                        <tr>
                                        <th>FluxBB emoticones por defecto</th>
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
                                         <th>EZBBC emoticones personalizados</th>
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
                                        <th>BBCode</th>
                                        <td><code>O:)</code><br />o<br /><code>:angel:</code></td> 
                                        <td><code>8.(</code><br />o<br /><code>:cry:</code></td> 
                                        <td><code>]:D</code><br />o<br /><code>:devil:</code></td> 
                                        <td><code>8)</code><br />o<br /><code>:glasses:</code></td>
                                        <td><code>{)</code><br />o<br /><code>:kiss:</code></td>
                                        <td><code>8o</code><br />o<br /><code>:monkey:</code></td> 
                                        <td><code>:8</code><br />o<br /><code>:ops:</code></td>
                                        </tr>
                                        <tr>
                                        <th>FluxBB emoticones por defecto</th>
                                        <td>O:)</td> 
                                        <td>8.(</td> 
                                        <td>]:D</td> 
                                        <td>8)</td>
                                        <td>{)</td>
                                        <td>8o</td> 
                                        <td>:8</td>
                                        </tr>
                                        <tr>
                                         <th>EZBBC emoticones personalizados</th>
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
