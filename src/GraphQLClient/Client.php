<?php

namespace GraphQLClient;

use App\Helpers\GraphQLClient\Field;
use App\Helpers\GraphQLClient\GraphQLException;
use App\Helpers\GraphQLClient\Query;
use App\Helpers\GraphQLClient\Variable;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;

class Client
{
    /** @var string */
    protected $baseUrl;

    /** @var array */
    protected $responseFields;

    /** @var array */
    protected $variables;

    /** @var ClientInterface */
    protected $client;

    /** @var ResponseInterface */
    protected $response;

    public function __construct(string $baseUrl, ClientInterface $client) {
        $this->baseUrl = $baseUrl;
        $this->responseFields = null;
        $this->variables = [];
        $this->client = $client;
        $this->response = null;
    }

    private function getQueryData(Query $query): array
    {
        $queryString = 'query { ' . $this->getQueryString($query) . ' }';
        return [
            'query' => $queryString,
            'variables' => null
        ];
    }

    private function getMutationData(Query $query): array
    {
        $queryBody = $this->getQueryString($query);
        $queryString = sprintf(
            'mutation %s { %s }',
            $query->getQueryHeader($this->variables),
            $queryBody
        );

        return [
            'query' => $queryString,
            'variables' => $this->getVariableContent($this->variables)
        ];
    }

    private function fieldToString(Field $field): string
    {
        $result = $field->getName();

        if (!empty($field->getChildren())) {
            $children = '';
            foreach ($field->getChildren() as $child) {
                $children .= $this->fieldToString($child);
            }
            $result .= sprintf(' { %s }', $children);
        }

        $result .=  PHP_EOL;

        return $result;
    }

    private function hasStringKeys(array $array):bool
    {
        return count(array_filter(array_keys($array), 'is_string')) > 0;
    }

    /**
     * @param array $params
     * @return string
     */
    private function getParamString(array $params): string
    {
        $result = '';

        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $result .= $key . ' : ';
            }
            if (is_array($value)) {
                if ($this->hasStringKeys($value)) {
                    $result .= sprintf('{ %s } ', $this->getParamString($value));
                } else {
                    $result .= sprintf('[ %s ] ', $this->getParamString($value));
                }
            } else if ($value instanceof Variable) {
                $result .= sprintf('$%s ', $value->getName());
                $this->variables[$value->getName()] = $value;
            } else {
                $result .= sprintf('%s ', json_encode($value));
            }

        }

        return $result;
    }

    private function getQueryString(Field $query): string
    {
        $fieldString = '';

        if ($query->getChildren()) {
            $fieldString .= '{';
            foreach ($query->getChildren() as $field) {
                $fieldString .= sprintf('%s', $this->getQueryString($field));
                $fieldString .= PHP_EOL;
            }
            $fieldString .= '}';
        }

        $paramString = '';
        if ($query instanceof Query) {
            $paramString = '(' . $this->getParamString($query->getParams()) . ')';
        }
        $queryString = sprintf('%s%s %s', $query->getName(), $paramString, $fieldString);


        return $queryString;

    }

    public function executeQuery(array $data, array $multipart = null)
    {
        if (is_array($multipart)) {
            $data = array_merge(['operations' => json_encode($data)], $multipart);
        }

        $response = $this->client->request('POST', $this->getBaseUrl(), $data);
        $responseBody = json_decode($response->getBody()->getContents(), true);

        if (isset($responseBody['errors'])) {
            throw new GraphQLException(sprintf('Mutation failed with error %s', json_encode($response['errors'])));
        }

        return $responseBody;
    }

    public function mutate(Query $query, array $multipart = null): ResponseData
    {
        $response = $this->executeQuery($this->getMutationData($query), $multipart);
        return new ResponseData($response['data'][$query->getName()]);
    }

    public function query(Query $query):ResponseData
    {
        $response = $this->executeQuery($this->getQueryData($query));

        return new ResponseData($response['data'][$query->getName()]);
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getFields()
    {
        return $this->responseFields;
    }

    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param array|Variable[] $variables
     * @return array
     */
    private function getVariableContent(array $variables)
    {
        $result = [];

        foreach ($variables as $variable) {
            $result[$variable->getName()] = $variable->getValue();
        }

        return $result;
    }
}
