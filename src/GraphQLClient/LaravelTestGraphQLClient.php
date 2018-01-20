<?php

namespace GraphQLClient;

use Laravel\Lumen\Testing\Concerns\MakesHttpRequests;
use Laravel\Lumen\Application;


/**
 * Class LaravelTestGraphQLClient
 *
 * @package parku\AppBundle\Tests\GraphQL
 */
class LaravelTestGraphQLClient extends Client
{
    use MakesHttpRequests;

    /** @var Application */
    private $app;

    /**
     * WebTestGraphQLClient constructor.
     *
     * @param Application $app
     * @param string      $baseUrl
     */
    public function __construct(Application $app, string $baseUrl)
    {
        parent::__construct($baseUrl);

        $this->app = $app;
    }

    protected function postQuery(array $data): array
    {
        $response = $this->post($this->getBaseUrl(), $data);
        $responseBody = json_decode($this->response->getContent(), true);

        if (isset($responseBody['errors'])) {
            throw new GraphQLException(sprintf('Mutation failed with error %s', json_encode($response['errors'])));
        }

        return $responseBody;
    }
}