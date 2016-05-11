<?php
namespace StartupAPI;

/**
 * @package StartupAPI
 */
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
