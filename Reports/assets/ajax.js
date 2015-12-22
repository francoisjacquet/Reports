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
	url = "Modules.php?modname=" + getRequestVar('modname') + "&_ROSARIO_PDF=true&modfunc=" + extra;
	elems = document.forms[formname].elements;
	for(elemindex = 0;elemindex<elems.length;elemindex++)
	{
		elem = document.forms[formname].elements[elemindex];
		if(elem.value)
			url = url + "&" + elem.name + "=" + encodeURIComponent(elem.value);
		else if(elem.options)
			url = url + "&" + elem.name + "=" + encodeURIComponent(elem.options[elem.selectedIndex].value);
	}

	//document.location.href = url;
	connection.open("GET",url,true);
	connection.send(null);
}

function processRequest()
{
	// LOADED && ACCEPTED
	if(connection.readyState == 4 && connection.status == 200)
	{
		XMLResponse = connection.responseXML;
		document.getElementById("XMLHttpRequestResult").style.visibility = "visible";
		results_list = XMLResponse.getElementsByTagName("results");
		results_list = results_list[0];
		results = results_list.getElementsByTagName("result");

		document.getElementById("XMLHttpRequestResult").innerHTML = '';
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
		document.getElementById("XMLHttpRequestResult").innerHTML = document.getElementById("XMLHttpRequestResult").innerHTML + table;
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