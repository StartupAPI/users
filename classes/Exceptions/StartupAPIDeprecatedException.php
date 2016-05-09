<?php
namespace StartupAPI\Exceptions;

/**
 * Exception thrown when deprecated method is called
 *
 * Replace deprecated code with this exception to make sure instances that use
 * deprecated functionality have last warning to remove it.
 *
 * @package StartupAPI
 */
class StartupAPIDeprecatedException extends StartupAPIException {

}
