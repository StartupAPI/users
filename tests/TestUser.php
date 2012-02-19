<?php

require_once(dirname(dirname(dirname(__FILE__))).'/simpletest/autorun.php');

require_once(dirname(dirname(__FILE__)).'/Plan.php');
require_once(dirname(dirname(__FILE__)).'/users.php');

class TestUser extends UnitTestCase {
  private $user = null;

  function setUp()
  {
    $this -> user = User::createNew('me', 'me', 'me@internet.com', 'password');
  }

  function tearDown()
  {
    $this -> user -> delete();
  }

  function testSimple()
  {
    $this->assertTrue(true, 'should be true');
  }

  function testGetUsers()
  {
    $users = User::getUsers();
    $this -> assertNotNull( $users );
    $me = $users[6]; 
    $this -> assertNotNull( $me );
    $this -> assertEqual( $me -> getUsername(), 'spacediver' );
    //$this -> dump($me);
  }

  function testAddUser()
  {
    $found = User::getUsersByEmailOrUsername('me1');
    $this -> assertEqual( count($found), 0 );

		$user = User::createNew('me1', 'me1', 'me1@internet.com', 'password');

    $found = User::getUsersByEmailOrUsername('me1');
    $this -> assertEqual( count($found), 1 );
    $user = $found[0];
    $this -> assertEqual( $user -> getName(), 'me1' );

    $user -> delete();
  }

  function testDeleteUser()
  {
		$user = User::createNew('me2', 'me2', 'me2@internet.com', 'password');

    $found = User::getUsersByEmailOrUsername('me2');
    $this -> assertEqual( count($found), 1 );
    $user = $found[0];
    $this -> assertEqual( $user -> getName(), 'me2' );

    $user->delete();

    $found = User::getUsersByEmailOrUsername('me2');
    $this -> assertEqual( count($found), 0 );
  }
  
  function testSetAccount()
  {
    $user = $this -> user; 

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
    $plan = $acc -> getPlan();
    $this -> assertNotNull( $plan );
    $this -> assertEqual( $plan -> id, 'personal-pro');
    $schedule = $acc -> getSchedule();
    $this -> assertNotNull( $schedule );
    $this -> assertEqual( $schedule -> id, 'monthly' );
    //$this -> dump($schedule);

    //$this -> assertEquals( $lan
  }

}

?>
