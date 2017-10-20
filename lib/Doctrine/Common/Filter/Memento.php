<?php

namespace Doctrine\Common\Filter;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
final class Memento
{
    /** @var  string */
    private $hashClass;
    /** @var array */
    private $params;

    /**
     * @param string $hashClass
     * @return Memento
     */
    public function setHashClass(string $hashClass): Memento
    {
        $this->hashClass = $hashClass;

        return $this;
    }

    /**
     * @return string
     */
    public function getHashClass(): string
    {
        return $this->hashClass;
    }

    /**
     * @param string $key
     * @param $value
     * @return Memento
     */
    public function addParam(string $key, $value): Memento
    {
        $this->params[$key] = $value;

        return $this;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function getParam(string $key)
    {
        if (array_key_exists($key, $this->params)) {
            return $this->params[$key];
        }

        return null;
    }
}
