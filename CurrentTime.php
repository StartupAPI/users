<?php

class RealCurrentTime  {
  function getDateTime()
  {
    return new DateTime();
  }
}

class ModelledCurrentTime {
  function getDateTime()
  {
    return new DateTime('0000-00-00 00:00:00');
  }
}

