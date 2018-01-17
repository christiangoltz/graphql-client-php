<?php


namespace GraphQLClient;


class Variable
{
    /** @var string */
    private $name;

    /** @var mixed */
    private $value;

    /** @var string */
    private $type;

    /**
     * Field constructor.
     *
     * @param string $name
     * @param mixed $value
     * @param string $type
     * @internal param Field[]|array $children
     */
    public function __construct(string $name, $value, string $type = 'String')
    {
        $this->name = $name;
        $this->value = $value;
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}
