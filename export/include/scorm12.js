/*

Dernière mise à jour : 10 / 08 / 2011
Par : Dominique DUPLAN
Auteur : Symetrix
Version : 1.0
Description : 

*/
//Initialisation du temps
var d = new Date();
StartSessionTime = d.getTime();
var showLmsWarn = true;

// mm_getAPI, which calls findAPI as needed
function mm_getAPI()
{
	var myAPI = null;
	var tries = 0, triesMax = 500;

	while (tries < triesMax && myAPI == null)
	{
	  myAPI = findAPI(window);
	  if (myAPI == null && typeof(window.parent) != 'undefined') myAPI = findAPI(window.parent)
	  if (myAPI == null && typeof(window.top) != 'undefined') myAPI = findAPI(window.top);
	  if (myAPI == null && typeof(window.opener) != 'undefined') if (window.opener != null && !window.opener.closed) myAPI = findAPI(window.opener);
	  tries++;
	}
	  
	if (myAPI == null)
	{
	  window.status = 'API not found';
	  alert('JavaScript Warning: API object not found in window or opener. (' + tries + ')');
	}
	  else
	  {
		//on affecte l'api
		mm_adl_API = myAPI;
		
		window.status = 'API found';
		
		//on a trouvé l'API on peut initialiser
		var params = new Array("")
		DoCommand("LMSInitialize",params)
		
		//on récupère les informations
		GetValue("cmi.core.lesson_status")
		statusGet = GetInitialValue("cmi.core.lesson_status");
		
		//
		GetValue("cmi.suspend_data")
		//
		GetValue("cmi.core.student_name")
		//
		GetValue("cmi.core.score.raw")
		//
		GetValue("cmi.lesson_location")
		
		
		//si statut différent de completed
		if (statusGet != "completed")
		{
			SetStatus ("incomplete")
		}
		
		//on set le temps de session
		SetSessionTime ()
		
		
		
	  }
}

  
// returns LMS API object (or null if not found)
function findAPI(win)
{
  // look in this window
  if (typeof(win) != 'undefined' ? typeof(win.API) != 'undefined' : false)
  {
    if (win.API != null )  return win.API;
  }
  // look in this window's frameset kin (except opener)
  if (win.frames.length > 0)  for (var i = 0 ; i < win.frames.length ; i++){
  {
    if (typeof(win.frames[i]) != 'undefined' ? typeof(win.frames[i].API) != 'undefined' : false)
    {
	     if (win.frames[i].API != null)  return win.frames[i].API;
    }
  }
}
  return null;
}


function detectionAPI() {
 		clearTimeout(timer)
 		mm_getAPI();
 		
}
// retarde l'appel de la fonction de 1 seconde
var timer = setTimeout("detectionAPI()", 3000);

/*
Pour transformer le temps de seconde en HH:MM:SS
*/
function GetEllapsedTime(secondes) {
	var minutes = Math.floor(secondes / 60); 
	secondes %= 60;
	secondes = Math.floor(secondes);
	
	var heures = Math.floor(minutes / 60); 
	minutes %= 60;

	return (heures<10?("0"+heures):heures) + ":" + (minutes<10?("0"+minutes):minutes) + ":" +  (secondes<10?("0"+secondes):secondes);
}


/*
Fonction utilisé lorsque la fenetre se ferme
*/
function QuitWindow()
{
	//mise à jour du status
	//SetStatus ("completed")
	
	
	// on set le temps de session 
	SetSessionTime();
	
		
	//on finish
	finishLMS();
	
	

}

//fonction générique permettant de faire apparaitre les message d'erreur si nécessaire
function DoCommand(command, param)
{
	
	var value = "value_not_inialized";
	
	//on regarde quel type de fonction
	switch (command)
	{
		
		case "LMSInitialize": case "LMSCommit": case "LMSFinish":
			err = eval('mm_adl_API.' + command + '(\"' + param[0] + '\")');
			break;
			
		case "LMSSetValue":
			
			err = eval('mm_adl_API.' + command + '(\"' + param[0] + '\",\"' + param[1] + '\")');
			break
			
		case "LMSGetValue":
			value = eval('mm_adl_API.' + command + '(\"' + param[0] + '\",\"' + param[1] + '\")');
			break
			
		default:
			err = true;
	}
	
	if ((err == 0 || err == "false") && showLmsWarn)  {
      if (! confirm('LMS API adapter returns error code: ' + err + '\rWhen calling API.' + command + 'with ' + args + '\r\rSelect cancel to disable future warnings'))  showLmsWarn = false;
    }
	
	//si value est différent de la valeur d'initialisation on la renvoie
	if (value != "value_not_inialized")
	{
		return value;
	}
}

