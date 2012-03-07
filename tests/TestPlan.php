<?php

require_once(dirname(dirname(dirname(__FILE__))).'/simpletest/autorun.php');

require_once(dirname(dirname(__FILE__)).'/users.php');

class TestSimple1 extends UnitTestCase {
  private $plan = null;

  function testSimple()
  {
    $this->assertTrue(true, 'should be true');
  }

  function testMandatory()
  {
    $this -> expectException();
    $plan = new Plan(0,array());
  }

  function testSimpleCreation()
  {
    $plan = new Plan(0,array('name' => 'test plan', 
                             'details_url' => 'lalala'));
    $this -> assertNotNull( $plan );
    $this -> assertEqual( $plan->name, 'test plan' );
    $this -> assertEqual( $plan->details_url, 'lalala' );
    $this -> assertEqual( $plan -> getPaymentScheduleSlugs(), array());
  }

  function testSchedules()
  {
    $plan = new Plan(0,array('name' => 'test plan', 
      'details_url' => 'lalala',
      'payment_schedules' => array(0 => array('slug' => '0', 
                                   'name' => 'some schedule',
                                   'charge_amount' => '5',
                                   'charge_period' => '10'))
      ));
    $this -> assertEqual( $plan -> getPaymentScheduleSlugs(), array(0));
  }

  function testDefaultSchedule()
  {
    $plan = new Plan(0,array('name' => 'test plan', 
      'details_url' => 'lalala',
      'payment_schedules' => array(
                                    0 => array('slug' => '0', 
                                   'name' => 'some schedule',
                                   'charge_amount' => '5',
                                   'charge_period' => '10'),

                                    1 => array('slug' => '1', 
                                   'name' => 'some other schedule',
                                   'charge_amount' => '6',
                                   'charge_period' => '11'), 
       )));
    $this -> assertEqual( $plan -> getPaymentScheduleSlugs(), array(0, 1));
    $this -> assertNotNull( $plan -> getDefaultPaymentSchedule() );
    $this -> assertEqual( $plan -> getDefaultPaymentSchedule() -> slug, 0 );
  }

  function testGetSchedule()
  {
    $plan = new Plan(0,array('name' => 'test plan', 
      'details_url' => 'lalala',
      'payment_schedules' => array(
                                    0 => array('slug' => '0', 
                                   'name' => 'some schedule',
                                   'charge_amount' => '5',
                                   'charge_period' => '10'),

                                    1 => array('slug' => '1', 
                                   'name' => 'some other schedule',
                                   'charge_amount' => '6',
                                   'charge_period' => '11'), 
       )));
    $this -> assertEqual( $plan -> getPaymentScheduleSlugs(), array(0, 1));

    $this -> assertNotNull( $plan -> getPaymentScheduleBySlug( 0 ));
    $this -> assertEqual( $plan -> getPaymentScheduleBySlug( 0 ) -> slug, 0 );
    $this -> assertEqual( $plan -> getPaymentScheduleBySlug( 0 ) -> name, 'some schedule' );
    $this -> assertEqual( $plan -> getPaymentScheduleBySlug( 0 ) -> charge_amount, 5 );
    $this -> assertEqual( $plan -> getPaymentScheduleBySlug( 0 ) -> charge_period, 10 );

    $this -> assertNotNull( $plan -> getPaymentScheduleBySlug( 1 ) );
    $this -> assertEqual( $plan -> getPaymentScheduleBySlug( 1 ) -> slug, 1 );
    $this -> assertEqual( $plan -> getPaymentScheduleBySlug( 1 ) -> name, 'some other schedule' );
    $this -> assertEqual( $plan -> getPaymentScheduleBySlug( 1 ) -> charge_amount, 6 );
    $this -> assertEqual( $plan -> getPaymentScheduleBySlug( 1 ) -> charge_period, 11 );

    $this -> assertNull( $plan -> getPaymentScheduleBySlug( 2 ) );

  }

  /* TODO
  function testDuplicateIDsInSchedules()
  {
    // TEST what happens if there are duplicate IDs of plans specified 
  }
   */

}

?>
