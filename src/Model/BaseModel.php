<?php

namespace Radvance\Model;

use Radvance\Exception\BadMethodCallException;

abstract class BaseModel
{
    public static function createNew()
    {
        $class = get_called_class();
        return new $class;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $fields = get_object_vars($this);

        $result = array();
        foreach ($fields as $key => $value) {
            $propertyName = $this->underscoredTocamelCase($key);
            $getter = sprintf('get%s', ucfirst($propertyName));
            $result[$key] = $this->$getter($value);
        }

        return $result;
    }

    public function __toString()
    {
        if (property_exists($this, 'name')) {
            return (string)$this->getName();
        }

        return (string)$this->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function loadFromArray($data, $allowed_keys = null)
    {
        if (is_null($allowed_keys)) {
            $allowed_keys = array_keys((array)$data);
        }

        foreach ($data as $key => $value) {
            if (!in_array($key, $allowed_keys)) {
                continue;
            }

            $propertyName = $this->underscoredTocamelCase($key);
            $setter = sprintf('set%s', ucfirst($propertyName));
            $this->$setter($value);
        }

        return $this;
    }

    protected function camelCaseToUnderscored($name)
    {
        return strtolower(preg_replace('/([A-Z])/', '_$1', lcfirst($name)));
    }

    protected function underscoredTocamelCase($string, $capitalizeFirstCharacter = false)
    {
        $str = str_replace(' ', '', ucwords(preg_replace('/\_/', ' ', $string)));

        if (!$capitalizeFirstCharacter) {
            $str[0] = strtolower($str[0]);
        }

        return $str;
    }

    /**
     * Magic getter
     *
     * @param  mixed $name
     * @return mixed
     */
    public function __get($propertyName)
    {
        $propertyName = $this->camelCaseToUnderscored($propertyName);

        if (!property_exists($this, $propertyName)) {
            throw new BadMethodCallException(
                sprintf(
                    'Entity %s does not have a property named %s',
                    get_class($this),
                    $propertyName
                )
            );
        }

        $propertyName = $this->underscoredTocamelCase($propertyName);
        $getter = sprintf('get%s', ucfirst($propertyName));

        return $this->$getter();
    }

    /**
     * Magic getter
     *
     * @param  mixed $name
     * @return mixed
     */
    public function __set($propertyName, $propertyValue)
    {
        $propertyName = $this->camelCaseToUnderscored($propertyName);

        if (!property_exists($this, $propertyName)) {
            throw new BadMethodCallException(
                sprintf(
                    'Entity %s does not have a property named %s',
                    get_class($this),
                    $propertyName
                )
            );
        }

        $propertyName = $this->underscoredTocamelCase($propertyName);
        $setter = sprintf('set%s', ucfirst($propertyName));

        return $this->$setter($propertyValue);
    }

    /**
     * Magic getters/setters
     *
     * @param  mixed $name
     * @param  mixed $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (property_exists($this, $name)) {
            // if (empty($arguments)) {
                return $this->$name;
            // } else {
            //     $this->$name = $arguments[0];
            //     return $this;
            // }
        }

        if (!preg_match('/^(get|set)(.+)$/', $name, $matchesArray)) {
            throw new BadMethodCallException(
                sprintf(
                    'Method "%s" does not exist on entity "%s"',
                    $name,
                    get_class($this)
                )
            );
        }

        // CamelCase to underscored
        $propertyName = $this->camelCaseToUnderscored($matchesArray[2]);

        if (!property_exists($this, $propertyName)) {
            throw new BadMethodCallException(
                sprintf(
                    'Entity %s does not have a property named %s',
                    get_class($this),
                    $propertyName
                )
            );
        }

        switch ($matchesArray[1]) {
            case 'set':
                $this->$propertyName = $arguments[0];
                return $this;

            case 'get':
                return $this->$propertyName;
        }
    }
}
