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

Use with PSR-6
==============

If you are using the ``Cache`` interface in your application, then you need to
upgrade your application to use a PSR-6 cache library and wrap the PSR-6
``CacheItemPoolInterface`` into the
``Doctrine\Common\Cache\Psr6\DoctrineProvider`` wrapper:

.. code-block:: php

    use Doctrine\Common\Cache\Psr6\DoctrineProvider;

    $cache = DoctrineProvider::wrap($psr6CachePool);

An implementation of the PSR-6 cache is provided by `"symfony/cache" library
<https://symfony.com/doc/current/components/cache.html>`_ for example, you can install it
via Composer with:

::

    composer require symfony/cache

A full example to setup a filesystem based cache with symfony/cache then looks
like this:

.. code-block:: php

    use Doctrine\Common\Cache\Psr6\DoctrineProvider;
    use Symfony\Component\Cache\Adapter\FilesystemAdapter;

    $cachePool = new FilesystemAdapter();
    $cache = DoctrineProvider::wrap($cachePool);
    // $cache instanceof \Doctrine\Common\Cache\Cache
