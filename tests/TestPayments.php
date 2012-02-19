<?php

require_once(dirname(dirname(dirname(__FILE__))).'/simpletest/autorun.php');

require_once(dirname(dirname(__FILE__)).'/Plan.php');
require_once(dirname(dirname(__FILE__)).'/users.php');

class TestPayments extends UnitTestCase {
  private $user = null;

  function setUp()
  {
    $this -> user = User::createNew('me', 'me', 'me@internet.com', 'password');
    $this -> user -> getCurrentAccount() -> activatePlan('personal-pro', 'monthly');

  }

  function tearDown()
  {
    $this -> user -> delete();
  }

  function testSimple()
  {
    $this->assertTrue(true, 'should be true');
  }

  function testPaymentIsDue()
  {
    $user = $this -> user;
    $acc = Account::getCurrentAccount($user);
    $this -> assertNotNull( $acc );
    $this -> assertNotNull( $acc -> getCharges());
    $this -> assertEqual( count($acc -> getCharges()), 0);
    $acc -> paymentIsDue();
    $this -> assertNotNull( $acc -> getCharges());
    $this -> assertEqual( count($acc -> getCharges()), 1);
    $charges = $acc -> getCharges();
    $this -> assertEqual( $charges[0]['amount'], $acc->getSchedule()->charge_amount);
  }

  function testAddPaymentExact()
  {
    $user = $this -> user;
    $acc = Account::getCurrentAccount($user);
    $acc -> paymentIsDue();
    $this -> assertNotNull( $acc -> getCharges());
    $this -> assertEqual( count($acc -> getCharges()), 1);
    $charges = $acc -> getCharges();
    $this -> assertEqual( $charges[0]['amount'], $acc->getSchedule()->charge_amount);
    $acc -> paymentReceived( $acc->getSchedule()->charge_amount  );
    $this -> assertEqual( count($acc -> getCharges()), 0);
  }

}

?>
