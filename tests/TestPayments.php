<?php

require_once(dirname(dirname(dirname(__FILE__))).'/simpletest/autorun.php');

require_once(dirname(dirname(__FILE__)).'/users.php');

class TestPayments extends UnitTestCase {
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

  function testPaymentIsDue()
  {
    $user = $this -> user;
    $user -> getCurrentAccount() -> activatePlan('personal-pro', 'monthly');
    $acc = Account::getCurrentAccount($user);

    $this -> assertNotNull( $acc );
    $this -> assertNotNull( $acc -> getCharges());
    $this -> assertEqual( count($acc -> getCharges()), 1);
    $charges = $acc -> getCharges();
    $this -> assertEqual( $charges[0]['amount'], -$acc->getSchedule()->charge_amount);
    $acc -> paymentIsDue();
    $this -> assertNotNull( $acc -> getCharges());
    $this -> assertEqual( count($acc -> getCharges()), 2);
    $charges = $acc -> getCharges();
    $this -> assertEqual( $charges[0]['amount'], -$acc->getSchedule()->charge_amount);
    $this -> assertEqual( $charges[1]['amount'], -$acc->getSchedule()->charge_amount);
  }

  function testAddPaymentExact()
  {
    $user = $this -> user;
    $user -> getCurrentAccount() -> activatePlan('personal-pro', 'monthly');
    $acc = Account::getCurrentAccount($user);

    $acc -> paymentReceived( $acc->getSchedule()->charge_amount  );
    $this -> assertEqual( count($acc -> getCharges()), 0);
  }

  function testAddPaymentPartial()
  {
    $user = $this -> user;
    $user -> getCurrentAccount() -> activatePlan('personal-pro', 'monthly');
    $acc = Account::getCurrentAccount($user);

    $acc -> paymentReceived( $acc->getSchedule()->charge_amount - 1  );
    $this -> assertEqual( count($acc -> getCharges()), 1);
    $charges = $acc -> getCharges();
    $this -> assertEqual( $charges[0]['amount'], -1 );
  }

  function testAddPaymentInMultipleParts()
  {
    $user = $this -> user;
    $user -> getCurrentAccount() -> activatePlan('personal-pro', 'monthly');
    $acc = Account::getCurrentAccount($user);
    $amount = $acc->getSchedule()->charge_amount;

    $this -> assertTrue( $amount > 3 );
    $acc -> paymentReceived( $amount - 3  );
    $this -> assertEqual( count($acc -> getCharges()), 1);
    $charges = $acc -> getCharges();
    $this -> assertEqual( $charges[0]['amount'], -3 );
    $acc -> paymentReceived( 1 );
    $charges = $acc -> getCharges();
    $this -> assertEqual( $charges[0]['amount'], -2 );
    $acc -> paymentReceived( 2 );
    $this -> assertEqual( count($acc -> getCharges()), 0);
  }

  function testAddPaymentExcessive() {
    $user = $this -> user;
    $user -> getCurrentAccount() -> activatePlan('personal-pro', 'monthly');
    $acc = Account::getCurrentAccount($user);

    $acc -> paymentReceived( $acc->getSchedule()->charge_amount + 3 );

    $this -> assertEqual( count($acc -> getCharges()), 1);

    $acc -> paymentIsDue();

    $acc -> paymentReceived( $acc->getSchedule()->charge_amount - 3 );
    
    $this -> assertEqual( count($acc -> getCharges()), 0, 'should use my excessive money!');

    // TODO test excessive payment storage
  }

  function testManyCharges()
  {
    $user = $this -> user;
    $user -> getCurrentAccount() -> activatePlan('personal-pro', 'monthly');
    $acc = Account::getCurrentAccount($user);
    $amount = $acc->getSchedule()->charge_amount;

    $acc -> paymentIsDue();
    $acc -> paymentIsDue();

    $acc -> paymentReceived( $amount );

    $this -> assertEqual( count($acc -> getCharges()), 2);
    $charges = $acc -> getCharges();
    $this -> assertEqual( $charges[0]['amount'], -$amount );
    $this -> assertEqual( $charges[1]['amount'], -$amount );

    $acc -> paymentReceived( $amount );

    $this -> assertEqual( count($acc -> getCharges()), 1);
    $charges = $acc -> getCharges();
    $this -> assertEqual( $charges[0]['amount'], -$amount );

    $acc -> paymentReceived( $amount );

    $this -> assertEqual( count($acc -> getCharges()), 0);

  }

  function testSinglePaymentCoveringManyCharges()
  {
    $user = $this -> user;
    $user -> getCurrentAccount() -> activatePlan('personal-pro', 'monthly');
    $acc = Account::getCurrentAccount($user);
    $amount = $acc->getSchedule()->charge_amount;

    $acc -> paymentIsDue();
    $acc -> paymentIsDue();
    
    $acc -> paymentReceived( $amount * 3);

    $this -> assertEqual( count($acc -> getCharges()), 0);

  }

}

?>
