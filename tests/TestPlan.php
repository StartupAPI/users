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
}

?>
