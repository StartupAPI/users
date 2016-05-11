<?php
namespace StartupAPI;

/**
 * @package StartupAPI
 */
class RealClock extends Clock {
  function _getDateTime()
  {
    return new DateTime();
  }
}
