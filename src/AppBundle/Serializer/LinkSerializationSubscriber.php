<?php 

namespace AppBundle\Serializer;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\JsonSerializationVisitor;
use Symfony\Component\Routing\RouterInterface;
use AppBundle\Annotation\Link;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

// we want to include the URL to the programmer in its JSON representation. To make that URL, you need the router service. 
// But it isn't possible to access services from within a method in entity
// One way to do that it's with an event subscriber on the serializer.

// To create a subscriber with the JMSSerializer, you need to implement EventSubscriberInterface
class LinkSerializationSubscriber implements EventSubscriberInterface
{
	// To generate the real URI, we need the router
	private $router;

	// to read anotation we need annotation reader ...
	private $annotationReader;

	// to Evaluate the Expression
	private $expressionLanguage;

	public function __construct(RouterInterface $router, Reader $annotationReader) {
		$this->router = $router; 
		$this->annotationReader = $annotationReader;
		$this->expressionLanguage = new ExpressionLanguage();
	}
	
	public function onPostSerialize(ObjectEvent $event)
	{
		// The visitor is kind of in charge of the serialization process.
		// this will be an instance of JsonSerializationVisitor
		/** @var JsonSerializationVisitor $visitor */
		$visitor = $event->getVisitor();

		$object = $event->getObject();
		// To read the annotations off of that object :
		$annotations = $this->annotationReader
			->getClassAnnotations(new \ReflectionObject($object));

		$links = array();

		foreach ($annotations as $annotation) {
            if ($annotation instanceof Link) {
                $uri = $this->router->generate(
                    $annotation->route,
                    $this->resolveParams($annotation->params, $object)
                );
                $links[$annotation->name] = $uri;
            }
        }

		if ($links) { 
			$visitor->addData('_links', $links);
		}
	}

	// In this method, we'll tell the serializer exactly which events we want to hook into. 
	// One of those will allow us to add a new field... which will be the URL to whatever Programmer is being serialized.
	public static function getSubscribedEvents()
	{

		return array( 
			array(
				'event' => 'serializer.post_serialize',
				'method' => 'onPostSerialize',
				'format' => 'json',
			)
		);
	}
	// you can find it in the finish code ! 
	private function resolveParams(array $params, $object)
    {
    	// each parameter is evaluated through the expression language
        foreach ($params as $key => $param) {
            $params[$key] = $this->expressionLanguage
                ->evaluate($param, array('object' => $object));
        }

        return $params;
    }

}