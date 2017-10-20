<?php

namespace Doctrine\Common\Filter\Persist;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
interface CountPersister
{
    /**
     * @param int $bit
     * @return int
     */
    public function decrementBit(int $bit): int;

    /**
     * @param int $bit
     * @return int
     */
    public function incrementBit(int $bit): int;

    /**
     * @param array $bits
     * @return array
     */
    public function incrementBulk(array $bits): array;

    /**
     * @param int $bit
     * @return int
     */
    public function get(int $bit): int;

    /**
     * @return void
     */
    public function reset();
}
