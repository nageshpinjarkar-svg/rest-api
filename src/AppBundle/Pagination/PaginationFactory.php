<?php 

namespace AppBundle\Pagination;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Routing\RouterInterface;

// service pagination to re-use it 
class PaginationFactory
{
	// create the route , we need the router to generate url
	private $router;

	public function __construct(RouterInterface $router){
		$this->router = $router;
	}
	// createCollection will create the entire final PaginatedCollection object for some collection resource
	public function createCollection(QueryBuilder $qb, Request $request, $route, array $routeParams = array())
	{
		$page = $request->query->get('page', 1);
		
		$adapter = new DoctrineORMAdapter($qb);
		$pagerfanta = new Pagerfanta($adapter);
		$pagerfanta->setMaxPerPage(10); 
		$pagerfanta->setCurrentPage($page);

		$programmers = [];
		foreach ($pagerfanta->getCurrentPageResults() as $result) {
			$programmers[] = $result;
		}

		$paginatedCollection = new PaginatedCollection($programmers, $pagerfanta->getNbResults());

		// make sure query parameters are included in pagination links
		$routeParams = array_merge($routeParams, $request->query->all());

		$createLinkUrl = function($targetPage) use ($route, $routeParams) {
			// generate the route
			return $this->router->generate($route, array_merge( 
				$routeParams,
				array('page' => $targetPage)
			));
		};

		$paginatedCollection->addLink('self', $createLinkUrl($page));
		$paginatedCollection->addLink('first', $createLinkUrl(1));
		$paginatedCollection->addLink('last', $createLinkUrl($pagerfanta->getNbPages()));

		if ($pagerfanta->hasNextPage()) {
			$paginatedCollection->addLink('next', $createLinkUrl($pagerfanta->getNextPage()));
		}
		if ($pagerfanta->hasPreviousPage()) {
			$paginatedCollection->addLink('prev', $createLinkUrl($pagerfanta->getPreviousPage()));
		}

		return $paginatedCollection;
	}
}