<?

require_once('../../simpletest/autorun.php');
require_once('../Plan.php');

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
    $this -> assertEqual( $plan -> getPaymentScheduleIDs(), array());
  }

  function testSchedules()
  {
    $plan = new Plan(0,array('name' => 'test plan', 
      'details_url' => 'lalala',
      'payment_schedules' => array(0 => array('id' => '0', 
                                   'name' => 'some schedule',
                                   'charge_amount' => '5',
                                   'charge_period' => '10'))
      ));
    $this -> assertEqual( $plan -> getPaymentScheduleIDs(), array(0));
  }

  function testDefaultSchedule()
  {
    $plan = new Plan(0,array('name' => 'test plan', 
      'details_url' => 'lalala',
      'payment_schedules' => array(
                                    0 => array('id' => '0', 
                                   'name' => 'some schedule',
                                   'charge_amount' => '5',
                                   'charge_period' => '10'),

                                    1 => array('id' => '1', 
                                   'name' => 'some other schedule',
                                   'charge_amount' => '6',
                                   'charge_period' => '11'), 
       )));
    $this -> assertEqual( $plan -> getPaymentScheduleIDs(), array(0, 1));
    $this -> assertNotNull( $plan -> getDefaultPaymentSchedule() );
    $this -> assertEqual( $plan -> getDefaultPaymentSchedule() -> id, 0 );
  }


  /* uncomment me
  function testIDCorrespondence()
  {
    $plan = new Plan(0,array('name' => 'test plan', 
      'details_url' => 'lalala',
      'payment_schedules' => array(
                                    0 => array('id' => '1',  // these are actual IDs
                                   'name' => 'some schedule',
                                   'charge_amount' => '5',
                                   'charge_period' => '10'),

                                    1 => array('id' => '2',  // these are actual IDs
                                   'name' => 'some other schedule',
                                   'charge_amount' => '6',
                                   'charge_period' => '11'), 
       )));
    $this -> assertEqual( $plan -> getPaymentScheduleIDs(), array(1, 2)); // should match!
    $this -> assertNotNull( $plan -> getDefaultPaymentSchedule() );
    $this -> assertEqual( $plan -> getDefaultPaymentSchedule() -> id, 0 );
  }
   */

  /* uncomment me
  function testDuplicateIDsInSchedules()
  {
    $this -> expectException(); // because of duplicate IDs in schedule initializer list
    $plan = new Plan(0,array('name' => 'test plan', 
      'details_url' => 'lalala',
      'payment_schedules' => array(
                                    0 => array('id' => '0', 
                                   'name' => 'some schedule',
                                   'charge_amount' => '5',
                                   'charge_period' => '10'),

                                    1 => array('id' => '0',  // the same id! 
                                   'name' => 'some schedule',
                                   'charge_amount' => '5',
                                   'charge_period' => '10'), 
       )));
    $this -> assertEqual( $plan -> getPaymentScheduleIDs(), array(0, 1));
  }
   */

}

?>
