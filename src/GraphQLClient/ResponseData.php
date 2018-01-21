<?php

namespace GraphQLClient;

/**
 * Class ResponseData
 *
 * @package GraphQLClient
 */
class ResponseData
{
    /** @var mixed */
    private $data;

    /**
     * ResponseData constructor.
     *
     * @param mixed $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}
