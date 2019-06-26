<?php

namespace GraphQLClient;

use PHPUnit\Framework\Assert;

abstract class Client
{
    /** @var string */
    protected $baseUrl;

    /** @var array */
    protected $variables;

    public function __construct(string $baseUrl) {
        $this->baseUrl = $baseUrl;
        $this->variables = [];
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
        if ($query instanceof Query && 0 !== count($query->getParams())) {
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

        return $this->postQuery($data);
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

    public function assertGraphQlFields(array $fields, Query $query)
    {
        foreach ($query->getChildren() as $field) {
            $this->assertFieldInArray($field, $fields);
        }
    }

    protected function assertFieldInArray(Field $field, array $result)
    {
        if ($this->hasStringKeys($result)) {
            Assert::assertArrayHasKey($field->getName(), $result);
            if ($result[$field->getName()] !== null) {
                foreach ($field->getChildren() as $child) {
                    $this->assertFieldInArray($child, $result[$field->getName()]);
                }
            }
        } else {
            foreach ($result as $element) {
                $this->assertFieldInArray($field, $element);
            }
        }
    }

    abstract protected function postQuery(array $data): array;
}
