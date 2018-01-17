<?php

namespace GraphQLClient;

/**
 * Class ResponseData
 *
 * @package GraphQLClient
 */
class ResponseData
{
    /** @var array */
    private $data;

    /**
     * ResponseData constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
}
