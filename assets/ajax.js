/**
 * AJAX functions
 *
 * @package Reports
 * @subpackage assets / JS
 */

function SendXMLRequest(formname,extra)
{
	document.getElementById('XMLHttpRequestResult').innerHTML = '<img src="modules/Reports/assets/spinning.gif" />';
	if(window.XMLHttpRequest)
		connection = new XMLHttpRequest();
	else if(window.ActiveXObject)
	{
		try
		{
			connection = new ActiveXObject("Msxml2.XMLHTTP");
		}
		catch(e)
		{
			try
			{
				connection = new ActiveXObject("Microsoft.XMLHTTP");
			}
			catch(e)
			{
				connection = false;
			}
		}
	}
	if(!connection)
		alert('AJAX connection could not be made.');

	connection.onreadystatechange = processRequest;
	var url = "Modules.php?modname=" + getRequestVar('modname') + "&_ROSARIO_PDF=true&modfunc=" + extra;
	var elems = document.forms[formname].elements;
	var postvars = '';
	for(var elemindex = 0;elemindex<elems.length;elemindex++)
	{
		elem = document.forms[formname].elements[elemindex];
		if(elem.value)
			postvars += "&" + elem.name + "=" + encodeURIComponent( elem.value );
		else if(elem.options)
			postvars += "&" + elem.name + "=" + encodeURIComponent( elem.options[elem.selectedIndex].value );
	}

	//document.location.href = url;
	connection.open("POST",url,true);
	connection.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	connection.send(postvars.substr(1));
}

function processRequest()
{
	// LOADED && ACCEPTED
	if(connection.readyState == 4 && connection.status == 200)
	{
		XMLResponse = connection.responseXML;
		document.getElementById("XMLHttpRequestResult").style.visibility = "visible";
		results_list = XMLResponse.getElementsByTagName("results");
		/*console.log(XMLResponse,results_list);*/
		results_list = results_list[0];
		results = results_list.getElementsByTagName("result");

		table = '<table>';

		for(i=0;i<results.length;i++)
		{
			table = table + '<tr>';
			id = results[i].getElementsByTagName("id")[0].firstChild.data;
			if(id!='~')
				table = table + '<td>' + id + '</td>';
			title = results[i].getElementsByTagName("title")[0].firstChild.data;
			if(title=='Saved')
				title = '<img src="modules/Reports/assets/check.gif" /> Saved';
			table = table + '<td>' + title + '</td></tr>';
		}

		table = table + '</table>';

		document.getElementById("XMLHttpRequestResult").innerHTML = table;
	}
}

function getRequestVar(variable)
{
	var request = window.location.search.substring(1);
	var vars = request.split("&");
	for(var i=0;i<vars.length;i++)
	{
		var pair = vars[i].split("=");
		if(pair[0] == variable)
			return pair[1];
	}
	return false;
}

/*function urlencode(variable)
{
	return escape(variable)
		.replace("+",'%2B')
		.replace("\"",'%22')
		.replace("\'", '%27')
		.replace("\/",'%2F');
}*/