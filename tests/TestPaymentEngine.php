<?php

require_once(dirname(dirname(dirname(__FILE__))).'/simpletest/autorun.php');

require_once(dirname(dirname(__FILE__)).'/users.php');

class TestPaymentEngine extends UnitTestCase {
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

  function testSwitchWithZeroBalance()
  {
    $user = $this -> user;
    $acc = Account::getCurrentAccount($user);

    $acc -> activatePlan('personal-pro', 'monthly');
    //$this -> dump($user);
    //$this -> dump($acc);

  }

}

?>
