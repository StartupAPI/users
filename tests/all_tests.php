<?php

error_reporting(E_ALL);

require_once(dirname(dirname(__DIR__)).'/simpletest/autorun.php');

class AllTests extends TestSuite {
  function AllTests()
  {
    $this->TestSuite('All tests');
    $this->addFile('TestPlan.php');
    $this->addFile('TestUser.php');
    $this->addFile('TestPayments.php');
    $this->addFile('TestPaymentEngine.php');
    $this->addFile('TestClock.php');
  }
}

?>
