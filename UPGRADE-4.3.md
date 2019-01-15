UPGRADE FROM 4.2 to 4.3
=======================

BrowserKit
----------

 * Marked `Response` final.
 * Deprecated `Response::buildHeader()`
 * Deprecated `Response::getStatus()`, use `Response::getStatusCode()` instead

Config
------

 * Deprecated using environment variables with `cannotBeEmpty()` if the value is validated with `validate()`

FrameworkBundle
---------------

 * Not passing the project directory to the constructor of the `AssetsInstallCommand` is deprecated. This argument will
   be mandatory in 5.0.
