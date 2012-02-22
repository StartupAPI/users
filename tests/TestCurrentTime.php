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

    $zero_time = new DateTime( '0000-00-00 00:00:00' );
    $this -> assertEqual( $ct -> getDateTime(), $zero_time );

  }
}

?>
