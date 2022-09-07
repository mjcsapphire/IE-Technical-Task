<?php
namespace Drupal\ietaskupdated\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Drupal\Core\Cache\CacheBackendInterface;
use Symfony\Component\HttpFoundation\Response;

class RedirectBadIpSubscriber implements EventSubscriberInterface {

  public function checkIPStatus(GetResponseEvent $event) {

    //Store visitor IP Address
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // $ip_address = '181.224.94.11'; //TEST BAD IP ADDRESS

    //Define variables
    $check_address = true;

    // ! TASK 3 - PREVENT CALL IF CHECKED WITHIN 24 HOURS !

    //Check if IP address is cached:
    $cacheId = 'cached_address:'.$ip_address;
    $data = time();

    //If no data in cache
    $cache = \Drupal::cache()->get($cacheId);
    if ($cache == false) {
      \Drupal::cache()->set($cacheId, $data);
    }
    else { //If data stored in cache

      //If IP is already blocked
      if($cache->data == 'Blocked'){

      //Store IP as blocked
      \Drupal::cache()->set($cacheId, 'Blocked');

      //Send 403 response
      $response = new Response();
      $response->setContent('Blocked IP Address');
      $response->setMaxAge(10);
      $response->setStatusCode(403);
      $response->send();
      exit();

    } 

    if(time() < $cache->data + 86400){ //If less than 24 hours.

      //Do not perform check / API call
      $check_address = false;

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
      $response->send();
      exit();

    }

    //Store IP as not blocked
    \Drupal::cache()->set($cacheId, $data);
  }

}

public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkIPStatus', 100];

    return $events;
  }

}