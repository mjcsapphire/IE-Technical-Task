<?php 
namespace Drupal\custom_events\EventSubscriber;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class 403ResponseSubscriber implements EventSubscriberInterface {
  
  public function onRespond(FilterResponseEvent $event) {
    if ($event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
      return;
    }
    
    $response = $event->getResponse();
    if ($response->getStatusCode() == 403) {

      $event->setResponse(new RedirectResponse('/403page'));

    }
  }
  
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('onRespond');
    return $events;
  }
  
}