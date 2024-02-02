<?php

namespace AppBundle\Annotation;

// Creating an Annotation , start by create his class ...
// @Target("CLASS") : This means that this annotation is expected to live above class declarations

/**
 * @Annotation
 * 
 * @Target("CLASS") 
 */
class Link
{
	// Inside the Link class, we need to add a public property for each option that can be passed to the annotation,
	
	/**
	 * @Required
	 * 
	 * @var string 
	 */
	public $name;

	/**
	 * @Required
	 * 
	 * @var string 
	 */
	public $route;

	public $params = array();

}