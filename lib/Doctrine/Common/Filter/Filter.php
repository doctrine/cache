<?php

namespace Doctrine\Common\Filter;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
interface Filter
{
    /**
     * @param string $value
     * @return $this
     */
    public function add(string $value);

    /**
     * @param array $valueList
     * @return $this
     */
    public function addBulk(array $valueList);

    /**
     * @param string $value
     * @return bool
     */
    public function has(string $value): bool;
}
