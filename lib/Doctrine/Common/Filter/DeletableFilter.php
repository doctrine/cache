<?php

namespace Doctrine\Common\Filter;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
interface DeletableFilter
{
    /**
     * @param string $value
     * @return $this
     */
    public function delete(string $value);

    /**
     * @param array $valueList
     * @return $this
     */
    public function deleteBulk(array $valueList);
}
