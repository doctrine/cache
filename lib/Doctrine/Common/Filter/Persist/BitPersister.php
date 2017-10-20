<?php

namespace Doctrine\Common\Filter\Persist;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
interface BitPersister
{

    /**
     * @param int $bit
     * @return int
     */
    public function get(int $bit): int;

    /**
     * @param array $bits
     * @return int[]
     */
    public function getBulk(array $bits): array;

    /**
     * @param int $bit
     * @return void
     */
    public function set(int $bit);

    /**
     * @param array $bits
     * @return void
     */
    public function setBulk(array $bits);

    /**
     * @param int $bit
     * @return void
     */
    public function unset(int $bit);

    /**
     * @param array $bits
     * @return void
     */
    public function unsetBulk(array $bits);

    /**
     * @return void
     */
    public function reset();
}
