# Upgrade to 1.4

## Minor BC Break: `Doctrine\Common\Cache\FileCache#$extension` is now `private`.

If you need to override the value of `Doctrine\Common\Cache\FileCache#$extension`, then use the
second parameter of `Doctrine\Common\Cache\FileCache#__construct()` instead of overriding
the property in your own implementation.
