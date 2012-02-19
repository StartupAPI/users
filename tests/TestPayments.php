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
    $acc -> paymentReceived( $acc->getSchedule()->charge_amount  );
    $this -> assertEqual( count($acc -> getCharges()), 0);
  }

  function testAddPaymentPartial()
  {
    $user = $this -> user;
    $acc = Account::getCurrentAccount($user);
    $acc -> paymentIsDue();
    $acc -> paymentReceived( $acc->getSchedule()->charge_amount - 1  );
    $this -> assertEqual( count($acc -> getCharges()), 1);
    $charges = $acc -> getCharges();
    $this -> assertEqual( $charges[0]['amount'], 1 );
  }

  function testAddPaymentInMultipleParts()
  {
    $user = $this -> user;
    $amount = $acc->getSchedule()->charge_amount;

    $acc = Account::getCurrentAccount($user);
    $acc -> paymentIsDue();
    $this -> assertTrue( $amount > 3 );
    $acc -> paymentReceived( $amount - 3  );
    $this -> assertEqual( count($acc -> getCharges()), 1);
    $charges = $acc -> getCharges();
    $this -> assertEqual( $charges[0]['amount'], 3 );
    $acc -> paymentReceived( 1 );
    $charges = $acc -> getCharges();
    $this -> assertEqual( $charges[0]['amount'], 2 );
    $acc -> paymentReceived( 2 );
    $this -> assertEqual( count($acc -> getCharges()), 0);
  }

  function testAddPaymentExcessive() {
    $user = $this -> user;
    $acc = Account::getCurrentAccount($user);
    $acc -> paymentIsDue();
    $acc -> paymentReceived( $acc->getSchedule()->charge_amount + 3 );
    $this -> assertEqual( count($acc -> getCharges()), 0);

    // TODO test excessive payment storage
  }

  function testManyCharges()
  {
    $user = $this -> user;
    $amount = $acc->getSchedule()->charge_amount;

    $acc = Account::getCurrentAccount($user);
    $acc -> paymentIsDue();
    sleep(1); // FIXME due to second-wise uniqueness of datetime key in charges
    $acc -> paymentIsDue();
    sleep(1); // FIXME due to second-wise uniqueness of datetime key in charges
    $acc -> paymentIsDue();

    $acc -> paymentReceived( $amount );

    $this -> assertEqual( count($acc -> getCharges()), 2);
    $charges = $acc -> getCharges();
    $this -> assertEqual( $charges[0]['amount'], $amount );
    $this -> assertEqual( $charges[1]['amount'], $amount );

    $acc -> paymentReceived( $amount );

    $this -> assertEqual( count($acc -> getCharges()), 1);
    $charges = $acc -> getCharges();
    $this -> assertEqual( $charges[0]['amount'], $amount );

    $acc -> paymentReceived( $amount );

    $this -> assertEqual( count($acc -> getCharges()), 0);

  }

  function testSInglePaymentCoveringManyCharges()
  {
    $user = $this -> user;
    $amount = $acc->getSchedule()->charge_amount;

    $acc = Account::getCurrentAccount($user);
    $acc -> paymentIsDue();
    sleep(1); // FIXME due to second-wise uniqueness of datetime key in charges
    $acc -> paymentIsDue();
    sleep(1); // FIXME due to second-wise uniqueness of datetime key in charges
    $acc -> paymentIsDue();
    
    $acc -> paymentReceived( $amount * 3);

    $this -> assertEqual( count($acc -> getCharges()), 0);
  }
}

?>
