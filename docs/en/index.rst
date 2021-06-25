Deprecation Notice
==================

Please note that doctrine/cache is deprecated and no longer maintained. The last
version to include cache drivers is 1.11. The 2.x major release series only
provides the interfaces for libraries that need to maintain backward
compatibility. For all cache uses, we suggest relying on PSR-6 or PSR-16 instead
and using a cache library that supports those interfaces.

Introduction
============

Doctrine Cache is a library that provides an interface for caching data.
Here is what the ``Cache`` interface looks like.

.. code-block:: php
    namespace Doctrine\Common\Cache;

    interface Cache
    {
        public function fetch($id);
        public function contains($id);
        public function save($id, $data, $lifeTime = 0);
        public function delete($id);
        public function getStats();
    }
