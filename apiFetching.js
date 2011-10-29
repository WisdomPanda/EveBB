var xhttp;
var corpText = new String("");
var charText = new String("");
var currentBanner = new String("");
var preview = false;

function previewImage(img) {
	//alert("Got value: " + img);
	if (currentBanner == img) {
		return false;
	} //End if.
	
	var banner = document.getElementById("current_banner_preview");
	var banner_dir = document.getElementById("preview_banner_dir").value;
	banner.src = banner_dir + "/" + img;
	currentBanner = img;
	
} //End previewImage().

function is_preview(value) {
	preview = value;
	return true;
} //End is_preview.

function postwith (to,p) {
	var myForm = document.createElement("form");
	myForm.method="post" ;
	myForm.action = to ;
	for (var k in p) {
		var myInput = document.createElement("input") ;
		myInput.setAttribute("name", k) ;
		myInput.setAttribute("value", p[k]);
		myForm.appendChild(myInput) ;
	}
	document.body.appendChild(myForm) ;
	myForm.submit() ;
	document.body.removeChild(myForm) ;
} //End postwith().

function previewPost() {
	
	if (!preview) {
		return false;
	} //End if.
	
	var previewBox = document.getElementById("postpreview");
	var content = new String("<img src=\"img/ajax-loader.gif\" width=\"16\" height=\"16\">");
	
	var url = document.getElementById("quickpostform");
	if (url == null) {
		url = document.getElementById("post");
		if (url == null) {
			alert("Can't find a form to check.");
			return false;
		} //End if.
	} //End if.
	
	url = url.action;
	
	var post = new String(document.getElementById("req_message").value);
	if (post == null || post.length == 0) {
		post = "";
		return false;
	} //End if.
	
	var params = new String("preview=1&as_xml=1&req_message="+encodeURIComponent(post));
	
	if (window.XMLHttpRequest) {
		xhttp = new XMLHttpRequest();
	} else {
		xhttp = new ActiveXObject("Microsoft.XMLHTTP");
	} //End if - else.
	
	xhttp.open("POST", url, true);

	//Send the proper header information along with the request
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.setRequestHeader("Content-length", params.length);
	xhttp.setRequestHeader("Connection", "close");
	xhttp.onreadystatechange = previewCallback;	
	xhttp.send(params.toString());
	
	previewBox.innerHTML = content;
	
	return true;
	
} //End previewPost().

function previewCallback() {
	if(xhttp.readyState == 4 && xhttp.status == 200) {
		
		preview = false;
		
		var previewBox = document.getElementById("postpreview");
		var response = xhttp.responseText;
		var xml;
		var form = document.getElementById("quickpostform");
		
		var url = document.getElementById("quickpostform");
		if (url == null) {
			url = document.getElementById("post");
			if (url == null) {
				alert("Can't find a form to check.");
				return false;
			} //End if.
		} //End if.
		
		url = url.action;
		
		if (window.DOMParser) {
			var parser = new DOMParser();
			xml = parser.parseFromString(response, "text/xml");
		} else {
			var xml = new ActiveXObject("Microsoft.XMLDOM");
			xml.async = false;
			xml.loadXML(response);
		} //End if - else.
		
		temp = xml.getElementsByTagName("error");
		
		if (temp.length > 0) {
			form.submit(); //Make the form submit.
			return false;
		} //End if.
		
		var previewElement = xml.getElementsByTagName("preview");
		
		if (previewElement == null) {
			form.submit();
			return false;
		} //End if.
		
		previewBox.innerHTML = xml.getElementsByTagName("preview")[0].firstChild.nodeValue;
	} //End if.
} //End previewCallback().

function fetchCorp() {
	//Get the information, put our little loading image in,then submit the auth to the XML fetcher.
	var corpID,key,url;
	var newContent = new String();
	
	if (corpText.length == 0) {
		corpText = document.getElementById("corp_fetch_text").innerHTML;
	} //End if,
	
	corpID = document.getElementById("api_corp_id").value;
	
	//Lets check if they clicked OK without putting anything in (noobs)
	if (corpID == null || !isNumeric(corpID)) {
		newContent += "<span>Corp ID Incorrect</span><br />";
	} //End if.
	
	if (newContent.length > 0) {
		newContent += "<a class=\"fetch_corp\" href=\"index.php\" onClick=\"fetchCorp(); return false;\">"+corpText+"</a>";
		document.getElementById("api_holder").innerHTML = newContent;
		return false;
	} //End if.
	
	//Pretty loading image.
	newContent += "<img src=\"img/ajax-loader.gif\" width=\"16\" height=\"16\">";

	//Now lets get the ball rolling in regards to the XML data.
	//url = "api.php?action=corp_name&corpID="+corpID;
	url = "api.php?corp_name="+corpID;
	
	document.getElementById("api_holder").innerHTML = newContent;
	
	if (window.XMLHttpRequest) {
		xhttp = new XMLHttpRequest();
	} else {
		xhttp = new ActiveXObject("Microsoft.XMLHTTP");
	} //End if - else.
	
	xhttp.onreadystatechange = corpCallback;
	xhttp.open("GET", url, true);
	xhttp.send(null);
	
	return false;
} //End fetchCorp().

function corpCallback() {
	//First lets see if we are in the right area...
	if (xhttp.readyState == 4) {
		
		var response = xhttp.responseText;
		var xml;
		var newContent = new String();
		
		if (window.DOMParser) {
			var parser = new DOMParser();
			xml = parser.parseFromString(response, "text/xml");
		} else {
			var xml = new ActiveXObject("Microsoft.XMLDOM");
			xml.async = false;
			xml.loadXML(response);
		} //End if - else.
		
		temp = xml.getElementsByTagName("error");
		
		if (temp.length > 0) {
			newContent += "<span>"+temp[0].firstChild.nodeValue+"</span><br />";
			newContent += "<a class=\"fetch_corp\" href=\"index.php\" onClick=\"fetchCorp(); return false;\">"+corpText+"</a>";
			document.getElementById("api_holder").innerHTML = newContent;
			return false;
		} //End if.
		
		newContent += '<div class="infldset">'+
		'<table class="aligntop" cellspacing="0">';
		
		newContent += '<tr>';
		newContent += '	<th scope="row"  style="width: 64px;"><img src="http://www.evecorplogo.net/logo.php?id='+xml.getElementsByTagName("corporationID")[0].firstChild.nodeValue+'" width="64px" height="64px" alt="" /></th>';
		newContent += '	<td>';
		newContent += '		<strong>'+xml.getElementsByTagName("corporationName")[0].firstChild.nodeValue+'</strong><br/>';
		if (xml.getElementsByTagName("allianceID")[0].firstChild.nodeValue > 0) {
			newContent += '<em>'+xml.getElementsByTagName("allianceName")[0].firstChild.nodeValue+'</em>';
		} //End if.
		newContent += '	</td>';
		newContent += '	<td style="text-align: center;"><input type="radio" name="api_corp_name" value="'+xml.getElementsByTagName("corporationName")[0].firstChild.nodeValue+'" checked="checked"/>&#160;<strong></strong></td>';
		newContent += '</tr>';
		
		newContent += "</table><br /><a class=\"fetch_corp\" href=\"index.php\" onClick=\"fetchCorp(); return false;\">"+corpText+"</a></div>";
		
		document.getElementById("api_holder").innerHTML = newContent;
		
	} //End if.

} //End corpCallback().

function fetchCharacters() {
	//Get the information, put our little loading image in,then submit the auth to the XML fetcher.
	var userID,key,url;
	var newContent = new String();
	
	if (charText.length == 0) {
		charText = document.getElementById("char_fetch_text").innerHTML;
	} //End if,
	
	userID = document.getElementById("api_user_id").value;
	key = document.getElementById("api_key").value;
	
	//Lets check if they clicked OK without putting anything in (noobs)
	if (userID == null || !isNumeric(userID)) {
		newContent += "<span>User ID Incorrect</span><br />";
	} //End if.
	
	if (key == null || !isAlphaNumeric(key)) {
		newContent += "<span>API Key Incorrect</span><br />";
	} //End if.
	
	if (newContent.length > 0) {
		newContent += "<a class=\"fetch_chars\" href=\"index.php\" onClick=\"fetchCharacters(); return false;\">"+charText+"</a>";
		document.getElementById("api_holder").innerHTML = newContent;
		return false;
	} //End if.
	
	//Pretty loading image.
	newContent += "<img src=\"img/ajax-loader.gif\" width=\"16\" height=\"16\">";

	//Now lets get the ball rolling in regards to the XML data.
	url = "api.php?char_list="+userID+"-"+key;
	
	document.getElementById("api_holder").innerHTML = newContent;
	
	if (window.XMLHttpRequest) {
		xhttp = new XMLHttpRequest();
	} else {
		xhttp = new ActiveXObject("Microsoft.XMLHTTP");
	} //End if - else.
	
	xhttp.onreadystatechange = characterCallback;
	xhttp.open("GET", url, true);
	xhttp.send(null);
	
	return false;
} //End fetchCharacters().

function characterCallback() {
	//First lets see if we are in the right area...
	if (xhttp.readyState == 4) {
		
		var response = xhttp.responseText;
		var xml;
		var newContent = new String();
		
		//Let's turn off autocomplete, assuming we're in the register stage.
		var registerForm = document.getElementById('register');
		if (registerForm != null) {
			registerForm.setAttribute('autoComplete', 'off');
		} //End if.
		
		if (window.DOMParser) {
			var parser = new DOMParser();
			xml = parser.parseFromString(response, "text/xml");
		} else {
			var xml = new ActiveXObject("Microsoft.XMLDOM");
			xml.async = false;
			xml.loadXML(response);
		} //End if - else.
		
		temp = xml.getElementsByTagName("error");
		
		if (temp.length > 0) {
			newContent += "<span>"+temp[0].firstChild.nodeValue+"</span><br />";
			newContent += "<a class=\"fetch_chars\" href=\"index.php\" onClick=\"fetchCharacters(); return false;\">"+charText+"</a>";
			document.getElementById("api_holder").innerHTML = newContent;
			return false;
		} //End if.
		
		newContent += '<div class="infldset">'+
			'<table class="aligntop" cellspacing="0">';
		
		var row = xml.getElementsByTagName("row");
		for (i = 0; i < row.length; i++) {			
			newContent += '<tr>';
			newContent += '	<th scope="row"  style="width: 64px;"><img src="http://image.eveonline.com/Character/'+row[i].getAttribute("characterID")+'_64.jpg" width="64px" height="64px" alt="" /></th>';
			newContent += '	<td>';
			newContent += '		&nbsp;&nbsp;<strong>'+row[i].getAttribute("name")+'</strong><br/>';
			newContent += '		&nbsp;&nbsp;<em>'+row[i].getAttribute("corporationName")+'</em><br/>';
			newContent += '	</td>';
			newContent += '	<td style="text-align: center;"><input id="char_radio_'+i+'" type="radio" name="api_character_id" value="'+row[i].getAttribute("characterID")+'" autocomplete="off"/>&#160;<strong></strong></td>';
			newContent += '</tr>';
		} //End 'i' for loop.

		newContent += '</table>';
		
		newContent += "<br/><a class=\"fetch_chars\" href=\"index.php\" onClick=\"fetchCharacters(); return false;\">"+charText+"</a></div>";
		
		document.getElementById("api_holder").innerHTML = newContent;
		
		var firstRadio = document.getElementById("char_radio_0");
		if (firstRadio != null) {
			firstRadio.checked = true;
		} //End if.
		
	} //End if.

} //End characterCallback().


function isNumeric(s) {
	
	if (s.length == 0) {
		return false;
	} //End if.
	
	var numbers = "1234567890.-";
	
	for (i = 0; i < s.length; i++) {
		if (numbers.indexOf(s.charAt(i)) == -1) {
			return false;
		} //End if.
	} //End 'i' for loop.
	
	return true;
	
} //End isNumeric.

/**
 * Returns true if the string (s) contains only A-Z0-9. (Automatically converts to upperCase)
 * Returns false if length is 0 or non-alphanumeric.
 */
function isAlphaNumeric(s) {
	if (s.length == 0) {
		return false;
	} //End if.

	s = s.toUpperCase();
	
	var numbers = "ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
	
	for (i = 0; i < s.length; i++) {
		if (numbers.indexOf(s.charAt(i)) == -1) {
			return false;
		} //End if.
	} //End 'i' for loop.
	
	return true;
} //End isAlphaNumeric.