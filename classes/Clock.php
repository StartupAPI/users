<?php

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

class RealClock extends Clock {
  function _getDateTime()
  {
    return new DateTime();
  }
}

class ModelledClock extends Clock {
  private $datetime;

  function __construct() {
    $this -> datetime = new DateTime( '00010101T000000' );
  }

  function _getDateTime()
  {
    return $this -> datetime;
  }

  function _addTime( $date_interval )
  {
    $this -> datetime -> add( new DateInterval ( $date_interval ));
  }
}

