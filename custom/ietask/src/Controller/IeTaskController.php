<?php
namespace Drupal\ietask\Controller;
use Drupal\ietask\IpAddress; //Require IP address class

if(!isset($_SESSION))
{ 
	ini_set('session.gc_maxlifetime', 999999);
	session_start(); //Start session
} 

class IeTaskController
{
	public function Ietask()
	{
		//Store visitor IP Address
		$ip_address = $_SERVER['REMOTE_ADDR'];

		// $ip_address = '181.224.94.11'; //TEST BAD IP ADDRESS

		//Define variables
		$check_address = true;

		// ! TASK 3 - PREVENT CALL IF CHECKED WITHIN 24 HOURS !

		//Check if IP address is cached:

		//Check if IP addresses are stored in session variable
		if(!isset($_SESSION['ipaddresses']) || empty($_SESSION['ipaddresses'])){				
			$ip_array = array();
		}else{
			$ip_array = $_SESSION['ipaddresses'];

			//Loop through stored IP addresses
			foreach ($ip_array as $key=>$row) {

				$stored_address = $row->getAddress();

				//If current IP address is already stored
				if($stored_address == $ip_address){

					//Check if the search time has exceeded 24 hours
					$searched_time = $row->getSearchedTime();
					$searched_time_coverted = strtotime($searched_time);

					if(time() < $searched_time_coverted + 86400){ //If less than 24 hours

						//Do not perform check / API call
						$check_address = false;

						//If the stored address is marked as blocked, throw a 403 error
						if($row->getStatus() == 'Blocked'){

							header('HTTP/1.0 403 Forbidden');
							die('Error 403 Forbidden.'); 

						}
					}else{ //If 24 hours has elapsed

						//Remove IP address from array and perform a new check
						unset($ip_array[$key]);

					}
				}
			}
		}

		//If the current IP address needs to be checked
		if($check_address){

		// ! TASK 1 - API CALL !

			//Perform API call to retrieve abuse score
			$ch =  curl_init();
			curl_setopt($ch, CURLOPT_URL, 'https://api.abuseipdb.com/api/v2/check?ipAddress='.$ip_address);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
			curl_setopt($ch, CURLOPT_TIMEOUT, 3);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Key: 7550f05f3b1d181d3d80cf566ae73e2c35e6b1bb9952b81fba9c9a056f4a6798097063261f3dc333'));
			curl_setopt($ch, CURLOPT_HEADER, 0);
			$cresult = curl_exec($ch);
			$geojson = json_decode($cresult,true);
			// print_r($geojson);

			//Store results
			$abuseScore = $geojson['data']['abuseConfidenceScore'];
			$country = $geojson['data']['countryCode'];
			$isp = $geojson['data']['isp'];
			// print 'Abuse Score is = '.$abuseScore;

			//Store results using IP class
			$ipAddress = new IpAddress();
			// $ipAddress = \Drupal::classResolver(IpAddress::class);
			$ipAddress->setAddress($ip_address);
			$ipAddress->setCountry($country);
			$ipAddress->setIsp($isp);
			$ipAddress->setSearchedTime(date("Y-m-d h:i:sa"));
			$ipAddress->setAbuseScore($abuseScore);

			//If the abuse score is greater than 50
			if($abuseScore > 50){

				// ! TASK 2 - BAD TRAFFIC RESPONSE !

				//Mark IP as blocked and store in session
				$ipAddress->setStatus('Blocked');
				array_push($ip_array, $ipAddress);
				$_SESSION['ipaddresses'] = $ip_array;

				//throw a 403 error and exit
				header('HTTP/1.0 403 Forbidden');
				die('Error 403 Forbidden.'); 

			}else{ //If score is 50 or less

				//Mark IP as allowed and store in session
				$ipAddress->setStatus('Allowed');
				array_push($ip_array, $ipAddress);
				$_SESSION['ipaddresses'] = $ip_array;

			}
		}

		//RENDER IP ADDRESS INFO 
		$html = '
		<h1>Checked IP Addresses</h1>
		<table>
		<tr>';

		foreach($ip_array[0] as $key=>$value){
			$html .= '<td>' . htmlspecialchars(strtoupper($key)) . '</td>';
		}
		$html .= '</tr>';

		foreach( $ip_array as $key=>$value){
			$html .= '<tr>';
			foreach($value as $key2=>$value2){
				$html .= '<td style="text-align:center">' . htmlspecialchars($value2) . '</td>';
			}
			$html .= '</tr>';
		}

		$html .= '</table>
		<hr>
		<h1>Task Breakdown</h1>
		<p>Comments: <br>I really enjoyed this task as it is the kind of coding that I like doing. I think the objectives have been achieved but I\'m not too sure if the solution is the most optimal. But I put that partly down to having zero knowledge of Drupal - perhaps there are some pre-build Drupal functions that I could\'ve leveraged to make life easier?!<br><br>I\'m not happy with my solution to the caching task as I don\'t think session variables are the way to go because the same IP address will be checked if the page is accessed via different browsers - which is not technically fulfilling the objective. I have not done server-side caching before so please excuse me if it is not the intended solution. Typically I would do client-side caching or use a database table to handle this. I would be keen to know how you would do the third bullet point.</p>
		<hr>
		<p>Time elapsed learning and playing with Drupal: <br>- 2.5 hours</p>
		<p>Time elapsed with bullet point 1 and 2: <br>- 45 minutes</p>
		<p>Time elapsed with bullet point 3: <br>- 1 hour</p>
		<p>Time elapsed with testing, commenting, and anything else: <br>- 1 hour</p>
		<b>Approx time elapsed: <br>- Just over 5 hours</b>
		<hr>		
		<h1>Task Code</h1>
		<p>Please find the code <a href="https://www.arsenal.com/" target="_new">here</a></p>';

	    return array(
	      '#type' => 'markup',
	      '#markup' => $html,
	      );
	}
}