<?php
class UConnMenFullScoreboard{

	public function __construct()
    {
		$this->makeScoreboard();
	}

	private function makeScoreboard(){
		/* live spreadsheet */
		$url = 'https://docs.google.com/spreadsheets/d/1yu3PJqaNBmJKhNeJMyocaUZZCDjYJXTQ1MCg8NuycB8/edit?usp=sharing';
		/* get spreadsheet and convert to array */
		$scoreboard = $this->google_spreadsheet_to_array_v3($url);
		/* take array and build scoreboard */
		$scores = $this->buildScoreboard($scoreboard);
		/* p2p api key */
		$P2Paccesstoken = '874ai9840kqvuyojkyqp4k49o6q56yyfa35';
		/* slug of story to update */
		$P2Pslug = 'hc-uconn-mens-basketball-schedule-results';
		/* p2p api location of item to update */
		$P2Purl = 'http://content-api.p2p.tribuneinteractive.com/content_items/'.$P2Pslug.'.json';

		/* update body of array */
		$data = array( 'content_item' => array(
			'body' => $scores
			)
		);
		$data_string = json_encode($data);

		/* Build the authentication array for CURLOPT_HTTPHEADER. */
		$headr = array();
		$headr[] = 'Authorization: Bearer ' . $P2Paccesstoken;
		$headr[] = 'Content-type: application/json';
		/* End authentication.  */

		/* Initiate cURL.  */
		$ch = curl_init($P2Purl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch, CURLOPT_HTTPHEADER,$headr);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$data_string);
		 
		$response = curl_exec($ch);

		if((string)$response == ''){
			echo '<div id="update"><h1>Updated ' . $P2Pslug . '</h1>';
			date_default_timezone_set('EST');
			echo '<p>' . date('F j, Y, g:i a') . '</p></div>';
			echo $scores;
		}
		else{
			echo 'Error updating' . $P2Pslug . 'please try again.';
		}
		/* End cURL call. */
	}
	/*
	 * Get a google spreadsheet and return its contents as an array
	 * @param $url the url of the public spreadsheet
	 */
	private function google_spreadsheet_to_array_v3($url=NULL) {
		/* make sure we have a URL */
		if (is_null($url)) {
			return array();
		}
		/* initialize curl */
		$curl = curl_init();
		/* set curl options */
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		/* get the spreadsheet data using curl */
		$google_sheet = curl_exec($curl);
		/* close the curl connection */
		curl_close($curl);
		/* parse out just the html table */
		preg_match('/(<table[^>]+>)(.+)(<\/table>)/', $google_sheet, $matches);
		$data = $matches['0'];
		/* Convert the HTML into array (by converting into HTML, then JSON, then PHP array */
		$cells_xml = new SimpleXMLElement($data);
		$cells_json = json_encode($cells_xml);
		$cells = json_decode($cells_json, TRUE);
		/* Create the array */
		$array = array();
		foreach ($cells['tbody']['tr'] as $row_number=>$row_data) 
		{
			$column_name = 'A';
			foreach ($row_data['td'] as $column_index=>$column) {
				$array[($row_number+1)][$column_name++] = $column;
			}
		}
		return $array;
	}
	/*
	 * Build the scoreboard
	 * @param $data the array of results from the spreadsheet
	 */
	private function buildScoreboard($data){
		/* array keys */
		$STATUS = 'A'; $DATE = 'B'; $TIME = 'C'; $HOME_AWAY = 'D'; $OPP = 'E'; 
		$MASCOT = 'F'; $SITE = 'G'; $TV = 'H'; $UC_SCORE = 'I'; $OPP_SCORE = 'J'; $W_L = 'K'; 
		$UC_RECORD = 'L'; $OPP_RECORD = 'M'; $LOGO = 'N'; $STORY = 'O'; $PICTURES = 'P';  $BOX = 'Q';
		$GAME_URL = 'http://www.courant.com/sports/';
		
		/* html string for the scoreboard */
		$scoreboard = "<link href='http://hc-assets.s3.amazonaws.com/css/uc-full-sked.css' rel='stylesheet' type='text/css'>";
		$scoreboard .= "<div id='hc-mm-wrapper'>";

		for($i=3; $i<=sizeof($data); $i++){
	    	/* check tv status */
	    	if($data[$i][$TV] != "-"){$game_tv = " (" . $data[$i][$TV] . ")";}
		    else{$game_tv = ""; }
		    /*check for home team */
		    if($data[$i][$HOME_AWAY] == "TRUE"){
		        $home_team = "UConn";
		        $home_mascot = "Huskies";
		        $home_logo = "<img src='http://s3.amazonaws.com/hc-assets/logos/sports/college/uconn_huskies.svg'/>";
		        
		        ($data[$i][$UC_SCORE] != "-") ? $home_score = $data[$i][$UC_SCORE] : $home_score = "&nbsp;";
		        ($data[$i][$UC_RECORD] != "-") ? $home_record = $data[$i][$UC_RECORD] : $home_record = "&nbsp;";
		        
		        $away_team = $data[$i][$OPP];
		        $away_mascot = $data[$i][$MASCOT];      
		        
		        if($data[$i][$LOGO] != "TBA"){
		        	$away_logo = "<img src='http://s3.amazonaws.com/hc-assets/logos/sports/college/{$data[$i][$LOGO]}.svg'/>";
		        }else{
		        	$away_logo = "&nbsp;";
		        }
				($data[$i][$OPP_SCORE] != "-") ? $away_score = $data[$i][$OPP_SCORE] : $away_score = "&nbsp;";
		        ($data[$i][$OPP_RECORD] != "-") ? $away_record = $data[$i][$OPP_RECORD] : $away_record = "&nbsp;";
		    }
		    else{
		        
		        $home_team = $data[$i][$OPP];
		        $home_mascot = $data[$i][$MASCOT];
		        
		        if($data[$i][$LOGO] != "TBA"){
		        	$home_logo = "<img src='http://s3.amazonaws.com/hc-assets/logos/sports/college/{$data[$i][$LOGO]}.svg'/>";
		        }else{
		        	$home_logo = "&nbsp;";
		        }
		        ($data[$i][$OPP_SCORE] != "-") ? $home_score = $data[$i][$OPP_SCORE] : $home_score = "&nbsp;";
		        ($data[$i][$OPP_RECORD] != "-") ? $home_record = $data[$i][$OPP_RECORD] : $home_record = "&nbsp;";
		        
		        $away_team = "UConn";
		        $away_mascot = "Huskies";
		        $away_logo = "<img src='http://s3.amazonaws.com/hc-assets/logos/sports/college/uconn_huskies.svg'/>";
		        
		        ($data[$i][$UC_SCORE] != "-") ? $away_score = $data[$i][$UC_SCORE] : $away_score = "&nbsp;";
		        ($data[$i][$UC_RECORD] != "-") ? $away_record = $data[$i][$UC_RECORD] : $away_record = "&nbsp;";
		      }
		    /* check game status */
		    if($data[$i][$UC_SCORE] != "-"){
		      	$game_status = $data[$i][$STATUS];
		    }else{
		      	$game_status = $data[$i][$TIME] . $game_tv;
		    }
		    $scoreboard .= "<div class='score_card {$data[$i][$STATUS]}'>";
		    $scoreboard .= "<div class='card_strip full'><p class='date'>{$data[$i][$DATE]}</p><p class='period'>{$game_status}</p></div>";
		    $scoreboard .= "<table class='card'><tr>";
		    $scoreboard .= "<td class='logo away'>{$away_logo}</td>";
		    $scoreboard .= "<td class='score away'>{$away_score}</td>";
		    $scoreboard .= "<td class='break'>&nbsp;</td>";
		    $scoreboard .= "<td class='logo home'>{$home_logo}</td>";
		    $scoreboard .= "<td class='score home'>{$home_score}</td>";
		    $scoreboard .= "</tr></table>";
		    $scoreboard .= "<table class='card'><tr>";
		    $scoreboard .= "<td class='name away'>{$away_team}<br/>{$away_mascot}<br/><span class='record'>{$away_record}</span></td>";
		    $scoreboard .= "<td class='where'><span class='circle'>&#64;</span></td>";
		    $scoreboard .= "<td class='name home'>{$home_team}<br/>{$home_mascot}<br/><span class='record'>{$home_record}</span></td>";
		    $scoreboard .= "</tr></table>";
		    /* links to story and gallery */
		    $scoreboard .= "<table class='card'><tr>";
		    if($data[$i][$PICTURES] != "-"){
		    	$scoreboard .= "<td class='site'>" . $data[$i][$SITE] . "<br/><a href='" . $GAME_URL . $data[$i][$STORY] . "-story.html'>Game Story</a> | <a href='" . $GAME_URL . $data[$i][$PICTURES] . "-photogallery.html'>Pictures</a></td>";
		    }
		    else{
		    	$scoreboard .= "<td class='site'>" . $data[$i][$SITE] . "<br/>&nbsp;</td>";
		    }
		    $scoreboard .= "</tr></table>";
		    
		    $scoreboard .= "</div>";
	    }
	    $scoreboard .= "</div>"; 

	    return $scoreboard;
	}

}
$uc = new UConnMenFullScoreboard();
?>