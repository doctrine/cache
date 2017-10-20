<?php

namespace Doctrine\Common\Filter\Hash;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
interface Hash
{
    /**
     * @param $value
     * @return string
     */
    public function generate(string $value): string;
}
