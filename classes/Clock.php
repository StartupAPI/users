<?php
namespace StartupAPI;

/**
 * @package StartupAPI
 */
class Clock {
  static protected $instance;

  static function format( $format )
  {
    return self::$instance -> getDateTime() -> format ( $format );
  }

  static function date ( $format )
  {
    return self::$instance -> format ( $format );
  }

  static function setInstance( $a_instance )
  {
    self::$instance = $a_instance;
  }

  static function getDateTime()
  {
    if (!isset( self::$instance))
      self::$instance = new RealClock();

    return self::$instance -> _getDateTime();
  }

  static function addTime( $interval )
  {
    if(self::$instance instanceof ModelledClock )
    {
      self::$instance -> _addTime( $interval );
    }
    else throw new Exception ('programming error: cannot add time to RealClock instance' );
  }
}
