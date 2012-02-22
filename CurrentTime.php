<?php

class CurrentTime {

  function format( $format )
  {
    return $this -> getDateTime() -> format ( $format );
  }

  function date ( $format )
  {
    return $this -> format ( $format );
  }
}

class RealCurrentTime extends CurrentTime {
  function getDateTime()
  {
    return new DateTime();
  }
}

class ModelledCurrentTime extends CurrentTime {
  private $datetime;

  function __construct() {
    $this -> datetime = new DateTime( '00010101T000000' );
  }

  function getDateTime()
  {
    return $this -> datetime;
  }

  function addTime( $date_interval ) 
  {
    $this -> datetime -> add( new DateInterval ( $date_interval ));
  }

}

