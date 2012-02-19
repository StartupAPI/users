<?php

error_reporting(E_ALL);

require_once('../../simpletest/autorun.php');

class AllTests extends TestSuite {
  function AllTests()
  {
    $this->TestSuite('All tests');
    $this->addFile('TestPlan.php');
    $this->addFile('TestUser.php');
  }
}

?>
