<?php

namespace Doctrine\Common\Filter\Hash;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
final class Crc32b implements Hash
{
    /**
     * @inheritdoc
     */
    public function generate(string $value): string
    {
        return sprintf('%u', hexdec(hash('crc32b', $value)));
    }
}
