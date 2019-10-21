<?php
$apiKey = "OBSERVEPOINT API KEY";

//Optional Debug Parameters
//ini_set('display_errors', 'On');
//error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *"); //This might be adjustable to be more secure (i.e. observepoint.com)

if($_SERVER['REQUEST_METHOD']=="POST") {
	if($_GET['token'] != "USER-DEFINED AUTHENTICATION TOKEN") {
		$errors = "<reason>Invalid Token Specified in URL: '".$_SERVER["REQUEST_URI"]."</reason>".PHP_EOL;
		print '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
		print '<status>FAILURE</status>'.PHP_EOL.$errors;
		exit;
	}
	
	$redirect = urldecode($_GET['redirect']);
	$opbody = json_decode($HTTP_RAW_POST_DATA);
	
	if(strlen($redirect) < 1) $redirect = "MICROSOFT TEAMS WEBHOOK URL";
	
	$itemType = $opbody->itemType;
	$itemId = $opbody->itemId;
	$runId = $opbody->runId;
	$formattedDate = date(DATE_RFC2822);
	
	//Grab audit/journey result from OP
	$completedAt = "";
	$startedAt = "";
	$auditScore = null;
	$journeyStatus = "";
	$itemName = "";
	
	switch($itemType) {
		case "audit":
		$url = "https://api.observepoint.com/v2/web-audits/$itemId?withRuns=true&runsLimit=1";
		break;
		case "web-journey":
		$url = "https://api.observepoint.com/v2/web-journeys/$itemId";
		break;
		default:
		$url = "";
		break;
	}
	$opch = curl_init();
	curl_setopt($opch,CURLOPT_URL, $url);
	curl_setopt($opch,CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 
	curl_setopt($opch,CURLOPT_HTTPHEADER, array("Authorization: api_key $apiKey")); 
	curl_setopt($opch,CURLOPT_RETURNTRANSFER, true); 
	$opresult = json_decode(curl_exec($opch));
	switch($itemType) {
		case "audit":
		$auditScore = $opresult->score;
		$startedAt = $opresult->started;
		$completedAt = $opresult->completed;
		$itemName = $opresult->name;
		break;
		case "web-journey":
		$journeyStatus = $opresult->status;
		$startedAt = $opresult->startedAt;
		$completedAt = $opresult->completedAt;
		$itemName = $opresult->name;
		break;
		default:
		break;
	}
	
	//Send notification to Teams
	//The url you wish to send the POST request to
	$url = $redirect;

	//JSON template that you want to send to Microsoft Teams via POST
	$teamsTemplate = '{
    "@type": "MessageCard",
    "@context": "http://schema.org/extensions",
    "themeColor": "F4D228",
    "summary": "ObservePoint <<itemType>> results",
    "sections": [{
        "activityTitle": "<<itemType>> <<itemName>> results",
        "activitySubtitle": "Run ID: <<runId>>",
        "activityImage": "https://parser.scdebugger.com/img/OP-logo.jpg",
        "facts": [{
            "name": "<<Audit Score/Journey Status>>",
            "value": "<<auditScore/journeyStatus>>"
        },{
            "name": "started at",
            "value": "<<startedAt>>"
        },{
            "name": "completed at",
            "value": "<<completedAt>>"
        },{
            "name": "link to report",
            "value": "[app.observepoint.com](https://app.observepoint.com/<<itemType>>/<<itemId>>/reports/summary/run/<<runId>>)"
        }],
        "markdown": true
    }]
}';

//Populate the dynamic elements of the template
	$teamsData = json_decode($teamsTemplate);
	$teamsData->summary = "ObservePoint $itemType results";
	$teamsData->sections[0]->activityTitle = "ObservePoint $itemType results";
	$teamsData->sections[0]->activitySubtitle = "Run ID: $runId";
	if(strlen($journeyStatus) == 0) {
		$teamsData->sections[0]->facts[0]->name = "audit score";
		$teamsData->sections[0]->facts[0]->value = "$auditScore";
	} else {
		$teamsData->sections[0]->facts[0]->name = "journey status";
		$teamsData->sections[0]->facts[0]->value = "$journeyStatus";
	}
	$teamsData->sections[0]->facts[1]->value = "$startedAt";
	$teamsData->sections[0]->facts[2]->value = "$completedAt";
	switch($itemType) {
		case "audit":
		$teamsData->sections[0]->facts[3]->value = "[https://app.observepoint.com/$itemType/$itemId/reports/summary/run/$runId](https://app.observepoint.com/$itemType/$itemId/reports/summary/run/$runId)";
		break;
		case "web-journey":
		$teamsData->sections[0]->facts[1]->name = "last checked on";
		$teamsData->sections[0]->facts[2]->name = "next run on";
		$teamsData->sections[0]->facts[3]->value = "[https://app.observepoint.com/journey/$itemId/run/$runId/results](https://app.observepoint.com/journey/$itemId/run/$runId/results)";
		break;
		default:
		$teamsData->sections[0]->facts[3]->value = "error generating report link";
		break;
	}
		
	//json-ify the data for the POST
	$encoded_data = json_encode($teamsData);
	
	//Open the connection to Microsoft Teams
	$ch = curl_init();

	//set the url, number of POST vars, POST data
	curl_setopt($ch,CURLOPT_URL, $url);
	curl_setopt($ch,CURLOPT_POST, true);
	curl_setopt($ch,CURLOPT_POSTFIELDS, $encoded_data);
	//Set the content type to application/json
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 

	//So that curl_exec returns the contents of the cURL; rather than echoing it
	curl_setopt($ch,CURLOPT_RETURNTRANSFER, true); 

	$result = curl_exec($ch);
	print_r($result);
	exit;
} else {
	print 'Unexpected/Invalid Request Method'.PHP_EOL;
	print_r($_SERVER['REQUEST_METHOD']);
}

?>
