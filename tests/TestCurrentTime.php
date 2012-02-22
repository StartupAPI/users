<?php

require_once(dirname(dirname(dirname(__FILE__))).'/simpletest/autorun.php');

require_once(dirname(dirname(__FILE__)).'/users.php');
require_once(dirname(dirname(__FILE__)).'/CurrentTime.php');

class TestCurrentTime extends UnitTestCase {

  function testRealCurrentTime()
  {
    $ct = new RealCurrentTime();

    $this -> assertEqual( $ct -> getDateTime(), new DateTime() );
  }

  function testModelledCurrentTime()
  {
    $ct = new ModelledCurrentTime();

    $zero_time = new DateTime( '00010101T000000' );
    $this -> assertEqual( $zero_time -> format('Y-m-d H:i:s'), '0001-01-01 00:00:00' );

    $this -> assertEqual( $ct -> getDateTime(), $zero_time );
    $this -> assertEqual( $ct -> format ('Y-m-d H:i:s'), '0001-01-01 00:00:00' );

    $interval_spec = 'P1M1D'; // period of 1 month and 1 day
    $ct -> addTime( $interval_spec ); 
    $zero_time -> add( new DateInterval( $interval_spec ));

    $this -> assertEqual( $ct -> getDateTime(), $zero_time );
    $this -> assertEqual( $ct -> format ('Y-m-d H:i:s'), '0001-02-02 00:00:00' );
    $this -> assertEqual( $ct -> date ('Y-m-d H:i:s'), '0001-02-02 00:00:00' );
    sleep(1); // should not tick on with real time
    $this -> assertEqual( $ct -> getDateTime(), $zero_time, 'should not tick on with real time' );
  }

  function testPluggableCurrentTime()
  {
    $ct = new RealCurrentTime();

    $this -> assertEqual( $ct -> getDateTime(), new DateTime() );
    $this -> assertEqual( $ct -> date ('Y-m-d H:i:s'), date ('Y-m-d H:i:s'));

    $ct = new ModelledCurrentTime();

    $this -> assertEqual( $ct -> getDateTime(), new DateTime( '00010101T000000' ));
    $this -> assertEqual( $ct -> date ('Y-m-d H:i:s'), '0001-01-01 00:00:00' );
  }
}

?>
