<?php
namespace Drupal\ietask\Controller;
use Drupal\Core\Cache\CacheBackendInterface;
use Symfony\Component\HttpFoundation\Response;

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
		$cacheId = 'cached_address:'.$ip_address;
		$data = time();
		$string = '';

		//If no data in cache
		$cache = \Drupal::cache()->get($cacheId);
		if ($cache == false) {
		  	\Drupal::cache()->set($cacheId, $data);
		  	$string .= ' Not stored in cache.';
		}
		else { //If data stored in cache
		$string .= ' Stored in cache.';	 

					//If IP is already blocked
					if($cache->data == 'Blocked'){

						//Send 403 response
						$response = new Response();
					    $response->setContent('Blocked IP Address');
					    $response->setMaxAge(10);
					    $response->setStatusCode(403);
					    return $response;
					} 

					if(time() < $cache->data + 86400){ //If less than 24 hours.

						//Do not perform check / API call
						$check_address = false;
						$string .= ' API not called.';

					}	
		}

		//If the current IP address needs to be checked
		if($check_address){
		$string .= ' API called.';

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

			//If the abuse score is greater than 50
			if($abuseScore > 50){

				// ! TASK 2 - BAD TRAFFIC RESPONSE !

				//Store IP as blocked
				\Drupal::cache()->set($cacheId, 'Blocked');

				//Send 403 response
				$response = new Response();
			    $response->setContent('Blocked IP Address');
			    $response->setMaxAge(10);
			    $response->setStatusCode(403);
			    return $response;

			}

			//Store IP as not blocked
			\Drupal::cache()->set($cacheId, $data);
		}

		//RENDER IP ADDRESS INFO 
		$html = '
		<h1>Current IP Address</h1>
		<p>';

		$html .= $ip_address . $string;

		$html .= '</p>
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
		<p>Please find the code <a href="https://github.com/mjcsapphire/IE-Technical-Task" target="_new">here</a></p>';

	    return array(
	      '#type' => 'markup',
	      '#markup' => $html,
	      );
	}
}