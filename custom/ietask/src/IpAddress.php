<?php
namespace Drupal\ietask;

   class IpAddress {

      var $address;
      var $searched_time;
      var $country;
      var $isp;
      var $abuse_score;
      var $status;
      
      function setAddress($par){
         $this->address = $par;
      }
      function setSearchedTime($par){
         $this->searched_time = $par;
      }
      function setAbuseScore($par){
         $this->abuse_score = $par;
      }
      function setCountry($par){
         $this->country = $par;
      }
      function setIsp($par){
         $this->isp = $par;
      }
      function setStatus($par){
         $this->status = $par;
      }

      
      function getAddress(){
         return $this->address;
      }
      function getSearchedTime(){
         return $this->searched_time;
      }
      function getCountry(){
         return $this->country;
      }
      function getIsp(){
         return $this->isp;
      }
      function getAbuseScore(){
         return $this->abuse_score;
      }
      function getStatus(){
         return $this->status;
      }
   }
?>