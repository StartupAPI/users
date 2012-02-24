<?php

require_once(dirname(dirname(dirname(__FILE__))).'/simpletest/autorun.php');

require_once(dirname(dirname(__FILE__)).'/users.php');
require_once(dirname(dirname(__FILE__)).'/Clock.php');

class TestClock extends UnitTestCase {

  function testRealClock()
  {
    $ct = new RealClock();

    $this -> assertEqual( $ct -> getDateTime(), new DateTime() );
  }

  function testModelledClock()
  {
    Clock::setInstance( new ModelledClock() );

    $zero_time = new DateTime( '00010101T000000' );
    $this -> assertEqual( $zero_time -> format('Y-m-d H:i:s'), '0001-01-01 00:00:00' );

    $this -> assertEqual( Clock::getDateTime(), $zero_time );
    $this -> assertEqual( Clock::format ('Y-m-d H:i:s'), '0001-01-01 00:00:00' );

    $interval_spec = 'P1M1D'; // period of 1 month and 1 day
    Clock::addTime( $interval_spec ); 
    $zero_time -> add( new DateInterval( $interval_spec ));

    $this -> assertEqual( Clock::getDateTime(), $zero_time );
    $this -> assertEqual( Clock::format ('Y-m-d H:i:s'), '0001-02-02 00:00:00' );
    $this -> assertEqual( Clock::date ('Y-m-d H:i:s'), '0001-02-02 00:00:00' );
    sleep(1); // should not tick on with real time
    $this -> assertEqual( Clock::getDateTime(), $zero_time, 'should not tick on with real time' );
  }

  function testPluggableClock()
  {
    Clock::setInstance( new RealClock() );

    $this -> assertEqual( Clock::getDateTime(), new DateTime() );
    $this -> assertEqual( Clock::date ('Y-m-d H:i:s'), date ('Y-m-d H:i:s'));

    Clock::setInstance( new ModelledClock() );

    $this -> assertEqual( Clock::getDateTime(), new DateTime( '00010101T000000' ));
    $this -> assertEqual( Clock::date ('Y-m-d H:i:s'), '0001-01-01 00:00:00' );
  }

}


?>
