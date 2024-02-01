<?php

namespace AppBundle\Api;

use Symfony\Component\HttpKernel\Exception\HttpException;

// ApiProblemException : a special exception class generate a json response from the apiProblem class
// So whenever something goes wrong, we'll just need to create the ApiProblem object and then throw this special exception.

class ApiProblemException extends HttpException
{
    // independence injection, an HttpException with an ApiProblem
    private $apiProblem;

    public function __construct(ApiProblem $apiProblem, \Exception $previous = null, array $headers = array(), $code = 0)
    {
        $this->apiProblem = $apiProblem;
        $statusCode = $apiProblem->getStatusCode();
        $message = $apiProblem->getTitle();

        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }

    public function getApiProblem()
    {
        return $this->apiProblem;
    }
}