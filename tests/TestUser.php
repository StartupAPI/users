<?php

require_once('../../simpletest/autorun.php');
require_once('../Plan.php');
require_once('../users.php');

class TestUser extends UnitTestCase {
  private $user = null;

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

  function testSetAccount()
  {
		$user = User::createNew('me', 'me', 'me@internet.com', 'password');
    $acc = Account::getCurrentAccount($user);
    $this -> assertNotNull( $acc );
    $this -> assertEqual( $acc -> getPlan() -> id, 'PLAN_FREE');
    $acc->activatePlan('personal-pro','monthly');
    $this -> assertEqual( $acc -> getPlan() -> id, 'personal-pro');

    // lookup again, and check back from DB
    $found = User::getUsersByEmailOrUsername('me');
    $this -> assertEqual( count($found), 1 );
    $user = $found[0];

    $acc = Account::getCurrentAccount($user);
    $this -> assertNotNull( $acc );
    $this -> assertEqual( $acc -> getPlan() -> id, 'personal-pro');
  }

}

?>
