<?php

require_once(dirname(dirname(dirname(__FILE__))).'/simpletest/autorun.php');

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

    $acc = Account::getCurrentAccount($user);
    $this -> assertNotNull( $acc );
    $plan = $acc -> getPlan();
    $this -> assertNotNull( $plan );
    $this -> assertEqual( $plan -> slug, 'PLAN_FREE');

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

  function _activatePlan( $user, $acc, $plan_slug, $schedule_slug )
  {
    $acc->activatePlan($plan_slug, $schedule_slug);
    $this -> assertEqual( $acc -> getPlan() -> slug, $plan_slug );

    // lookup again, and check back from DB
    $found = User::getUsersByEmailOrUsername($user -> getUsername());
    $this -> assertEqual( count($found), 1 );
    $user = $found[0];

    $acc = Account::getCurrentAccount($user);
    $this -> assertNotNull( $acc );
    $plan = $acc -> getPlan();
    $this -> assertNotNull( $plan );
    $this -> assertEqual( $plan -> slug, $plan_slug );
    $schedule = $acc -> getSchedule();
    if( $plan_slug != 'PLAN_FREE' ) {
      $this -> assertNotNull( $schedule );
      $this -> assertEqual( $schedule -> slug, $schedule_slug );
    }
    //$this -> dump($schedule);
  }

  function testActivatePlan()
  {
    $user = $this -> user;

    $acc = Account::getCurrentAccount($user);
    $this -> assertNotNull( $acc );
    $this -> assertEqual( $acc -> getPlan() -> slug, 'PLAN_FREE');

    $this -> _activatePlan( $user, $acc, 'personal-pro', 'monthly' );
    $this -> _activatePlan( $user, $acc, 'PLAN_FREE', NULL );
    $this -> _activatePlan( $user, $acc, 'personal-pro', 'every6months' );
    $this -> _activatePlan( $user, $acc, 'personal-pro', 'monthly' );

  }

}

?>
