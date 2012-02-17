<?

require_once('../../simpletest/autorun.php');
require_once('../Plan.php');

class TestSimple1 extends UnitTestCase {
  function testSimple()
  {
    $this->assertTrue(true, 'should be true');
  }

  function testCreation()
  {
    $plan = new Plan(0,array('name' => 'test plan', 'details_url' => 'lalala'));
    $this -> assertNotNull( $plan );
  }
}

?>
