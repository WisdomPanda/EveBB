function visibility(id)
{
	if(document.getElementById) {
		var element = document.getElementById(id);
	} else if(document.all) {
		var element = document.all[id];
	} else return;

	if(element.style.display == 'none') {
	        element.style.display = 'inline';
	} else {
		element.style.display = 'none';
	}
}
