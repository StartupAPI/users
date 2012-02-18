<?

require_once('../../simpletest/autorun.php');
require_once('../Plan.php');
require_once('../users.php');

class TestUser extends UnitTestCase {
  private $plan = null;

  function testSimple()
  {
    $this->assertTrue(true, 'should be true');
  }

  function testGetUsers()
  {
    $users = User::getUsers();
    $this -> assertNotNull( $users );
    $me = $users[5]; 
    $this -> assertNotNull( $me );
    $this -> assertEqual( $me -> getUsername(), 'spacediver' );
    //$this -> dump($me);
   }

  function testGetCurrentAccount()
  {
    $me = User::getUsers();
    $me = $me[5];
    $acc = Account::getCurrentAccount($me);
    //$this -> dump($acc);
    $this -> assertNotNull( $acc );
    $this -> assertEqual( $acc -> getName(), 'FREE (Paul)' );

  }

}

?>
