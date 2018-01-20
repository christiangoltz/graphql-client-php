<?php

namespace GraphQLClient;

use Laravel\Lumen\Testing\Concerns\MakesHttpRequests;


/**
 * Class LaravelTestGraphQLClient
 *
 * @package parku\AppBundle\Tests\GraphQL
 */
class LaravelTestGraphQLClient extends Client
{
    use MakesHttpRequests;

    protected function postQuery(array $data): array
    {
        $response = $this->post($this->getBaseUrl(), $data);
        $responseBody = json_decode($response->getContent(), true);

        if (isset($responseBody['errors'])) {
            throw new GraphQLException(sprintf('Mutation failed with error %s', json_encode($response['errors'])));
        }

        return $responseBody;
    }
}