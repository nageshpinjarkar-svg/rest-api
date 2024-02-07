<?php

namespace AppBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Api\ApiProblem;
use AppBundle\Api\ApiProblemException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

// When we throw an ApiProblemException , we need our app to automatically turn that into a nicely-formatted API Problem JSON 
// response and return it.  That code needs to live in a global spot.
// Whenever an exception is thrown in Symfony, it dispatches an event called kernel.exception . 
// If we attach a listener function to that event, we can take full control of how exceptions are handled.

class ApiExceptionSubscriber implements EventSubscriberInterface
{
	// create a debug mode
	
	private $debug;

	public function __construct($debug){
		$this->debug = $debug;
	}
	// whenever an exception is thrown, Symfony will call this method, with a GetResponseForExceptionEvent object
	public function onKernelException(GetResponseForExceptionEvent $event)
	{
		// if I just invent a URL, on the web interface I get a JSON response. 
		// This makes sense because the subscriber has completely taken over the error handling for our site
		// fix it, when the URL correspond to /api ApiExceptionSubscriber it works but if it's not it leaft ! 
		if (strpos($event->getRequest()->getPathInfo(), '/api') !== 0) { 
			return;
		}
		// exception
		$e = $event->getException();
		// statut code 500
		$statusCode = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

		if ($statusCode == 500 && $this->debug){
			// Symfony's normal exception handling will take over from here.
			return;
		}
		if ($e instanceof ApiProblemException) { 
			$apiProblem = $e->getApiProblem();
		} else {
			
			$apiProblem = new ApiProblem($statusCode);
			// create detail
			if ($e instanceof HttpExceptionInterface) {
				$apiProblem->set('detail', $e->getMessage());
			}
		}
		$data = $apiProblem->toArray();
		if ($data['type'] != 'about:blank'){
			$data['type'] = 'http://localhost:8000/docs/errors#'.$data['type'];
		}

		$response = new JsonResponse(
		 	$data, 
		 	$apiProblem->getStatusCode()
		 );
        $response->headers->set('Content-Type', 'application/problem+json');
        // tell Symfony to use this
		$event->setResponse($response);

	}
	// one method from EventSubscriberInterface
	public static function getSubscribedEvents() 
	{
		return array(
			KernelEvents::EXCEPTION => 'onKernelException'
		);
	}
}