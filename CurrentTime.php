<?php

class RealCurrentTime  {
  function getDateTime()
  {
    return new DateTime();
  }
}

class ModelledCurrentTime {
  private $datetime;

  function __construct() {
    $this -> datetime = new DateTime( '00010101T000000' );
  }

  function getDateTime()
  {
    return $this -> datetime;
  }

  function addTime( $interval )
  {
    $this -> datetime -> add( $interval );
  }

  function format( $format )
  {
    return $this -> datetime -> format ( $format );
  }

  function date ( $format )
  {
    return $this -> format ( $format );
  }

}

