<?php

namespace Doctrine\Common\Filter;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
interface ResetableFilter
{
    /**
     * @return void
     */
    public function reset();
}
