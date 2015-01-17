<?php
namespace Doctrine\Common\Cache;

class VoidCache implements Cache
{
    /**
     * {@inheritDoc}
     */
    public function fetch($id)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function contains($id)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function save($id, $data, $lifeTime = 0)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($id)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getStats()
    {
        return;
    }
}