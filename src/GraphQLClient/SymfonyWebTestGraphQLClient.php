<?php

namespace GraphQLClient;

use Symfony\Bundle\FrameworkBundle\KernelBrowser as SymfonyClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class SymfonyWebTestGraphQLClient
 *
 * @package parku\AppBundle\Tests\GraphQL
 */
class SymfonyWebTestGraphQLClient extends Client
{
    /** @var SymfonyClient */
    private $symfonyClient;

    /**
     * WebTestGraphQLClient constructor.
     *
     * @param SymfonyClient $client
     * @param string        $baseUrl
     */
    public function __construct(SymfonyClient $client, string $baseUrl)
    {
        parent::__construct($baseUrl);

        $this->symfonyClient = $client;
    }

    protected function postQuery(array $data): array
    {
        $this->symfonyClient->request('POST', $this->getBaseUrl(), $data);
        $responseBody = json_decode($this->symfonyClient->getResponse()->getContent(), true);

        if (isset($responseBody['errors'])) {
            throw new GraphQLException(sprintf('Query failed with error %s', json_encode($responseBody['errors'])));
        }

        return $responseBody;
    }
}
