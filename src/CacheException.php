<?php
/**
 * Created by Fernando Robledo <fernando.robledo@opinno.com>
 */

namespace Overdesign\PsrCache;

use Exception;


class CacheException extends Exception implements \Psr\Cache\CacheException {

    const ERROR_CANT_WRITE = 1;

}
