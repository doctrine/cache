<?php

namespace Doctrine\Common\Filter;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
interface Restorable
{
    /**
     * @return Memento
     */
    public function saveState(): Memento;

    /**
     * @param Memento $memento
     */
    public function restoreState(Memento $memento);
}
