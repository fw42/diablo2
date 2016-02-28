<?php

//
/*
simplexml44 Version 0.4.4

Copyright (c) 2006 - Ingo Schramm, Ister.ORG
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
    this list of conditions and the following disclaimer.

    * Redistributions in binary form must reproduce the above copyright notice,
    this list of conditions and the following disclaimer in the documentation
    and/or other materials provided with the distribution.

    * Neither the name of Ingo Schramm and Ister.ORG nor the names of its
    contributors may be used to endorse or promote products derived from this
    software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/



require_once('IsterLoggerStd.php');

// fake static property
$OrgIsterUtilIsterObjectStaticLoggers = null;

/**
 * Base class of Ister Framework.
 *
 * IsterObjects have at least two important features: they can log
 * their states and they can serialize themselves. The logging
 * facility works global (unless you call setLogLocal() for a single
 * object). That means, if you add a logger to one IsterObject this
 * logger will be used by <i>all</i> IsterObjects.
 *
 * @package ister.util
 * @author Ingo Schramm
 * @copyright Copyright &copy; 2005 Ister.ORG Ingo Schramm
 */
class IsterObject
{
  
  /**
   * @access private
   */
  var $loggers;
  /**
   * @access private
   */
  var $loglocal;

  /**
   * Constructor
   *
   * The constructor adds an IsterStdLogger automatically.
   *
   * @see passPHPmessage
   * @param boolean Whether to catch E_WARNING and E_NOTICE messages.
   * @return object
   */
  function IsterObject($phperr = false)
  {
    global $OrgIsterUtilIsterObjectStaticLoggers;

    if( $OrgIsterUtilIsterObjectStaticLoggers === null ) {
      $OrgIsterUtilIsterObjectStaticLoggers = array();
      $this->loggers  =& $OrgIsterUtilIsterObjectStaticLoggers;
      $this->addLogger('IsterLoggerStd');
      if( $phperr )
        set_error_handler(array($this, 'passPHPmessage'));
    }
    else
      $this->loggers  =& $OrgIsterUtilIsterObjectStaticLoggers;

    $this->loglocal =  false;
  }

  /**
   * Add an IsterLogger to process log messages.
   *
   * Loggers are always static, unless IsterObject::setLogLocal() was called.
   *
   * @see IsterLogger
   * @see IsterLoggerStd
   * @see IsterLoggerDebug
   * @see IsterLoggerFile
   *
   * @param  string  Name of the IsterLogger to register.
   * @return boolean
   */
  function addLogger($logger)
  {
    // already registered?
    if( isset($this->loggers[$logger]) )
      return true;
    // no, include definition
    include_once($logger.'.php');
    //create and register
    if ($object = new $logger) {
      $this->loggers[$logger] = $object;
      return true;
    }
    return false;
  }

  /**
   * Setup a logger.
   * @see IsterLogger
   * @param string Name of the logger
   * @param array  Array containing setup data
   */
  function setupLogger($name, $array)
  {
    $this->loggers[$name]->setup($array);
  }

  /**
   * Delete an already registered IsterLogger.
   *
   * @param string Name of the IsterLogger to delete.
   * @return boolean
   */
  function deleteLogger($logger)
  {
    unset($this->loggers[$logger]);
    return;
  }

  /**
   * Fetch the names of all currently registered IsterLoggers.
   *
   * @return array
   */
  function getLoggerNames()
  {
    return array_keys($this->loggers);
  }

  /**
   * Set logging local for the current object.
   *
   * After a call to this method, the object will have no IsterLoggers registered.
   * You must register one before you can benefit from the logging facility.
   *
   * @return boolean
   */
  function setLogLocal()
  {
    unset($this->loggers);
    $this->loggers = array();
    return true;
  }

  /**
   * Trigger a log message.
   *
   * A log mesage is not necessarily an error message. Choose one level of E_USER_ERROR,
   * E_USER_WARNING, E_USER_NOTICE and ISTER_DEBUG_NOTICE. The caller usually identifies
   * the method that triggers the log message. Additional information may be passed
   * via $context.
   *
   * The method will call exit() on E_USER_ERROR after logging has been completed.
   *
   * Note that it is up to the added IsterLogger objects to actually process
   * the logging information.
   *
   * @see addLogger()
   *
   * @param string
   * @param integer
   * @param string
   * @param string
   * @return boolean False on E_USER_WARNING, true otherwise.
   */
  function log($msg, $level, $caller, $context = null)
  {
    if( $caller != 'PHP' )
      $caller = (get_class($this)).'->'.$caller.'()';
    foreach ( $this->loggers as $logger )
      $logger->log($msg, $level, $caller, $context);
    if ( $level == E_USER_ERROR ) {
      while( ob_get_level() )
        ob_end_flush();
      exit($level);
    }
    return ($level == E_USER_WARNING) ? false : true;
  }
  
  /**
  * Alias for log().
  * @param string
  * @param integer
  * @param string
  * @param string
  * @return boolean False on E_USER_WARNING, true otherwise.
  */
  function triggerError($msg, $level, $caller, $context = null)
  {
	  return $this->log($msg, $level, $caller, $context);
  }

  /**
   * Catch PHP E_WARNING and E_NOTICE messages.  
   *
   * This method is registered automatically using
   * <i>set_error_handler()</i> on the very first IsterObject
   * created if you assign the $phperr parameter of the constructor a
   * true value.
   *
   * @param integer 
   * @param string 
   * @param string 
   * @param integer
   * return boolean
   */
  function passPHPmessage($errno, $str, $file, $line)
  {
    return $this->log($str.' in '.$file.' at line '.$line, $errno, 'PHP');
  }

  /**
   * Report attempt to call an abstract method.
   * @param string Name of method.
   * @return boolean
   */
  function abstractMethodError($name)
  {
    return $this->log('Abstract method called', E_USER_WARNING, $name);
  }

  /**
   * Serialize the object.
   *
   * @return string
   */
  function serialize()
  {
    return serialize($this);
  }
  
  /**
   * Unserialize the object.
   * The benefits of using this method over using a plain PHP unserialize() is
   * that you already must have defined the class to use this method. Thus
   * you have an improvement in robustness.
   * Note: This is a class method.
   * <code>
   * $o    = new IsterObject;
   * $data = $o->serialze();
   * unset($o);
   * $o    = IsterObject::unserialize($data);
   * </code>
   * @param string
   */
  function unserialize($data)
  {
    return unserialize($data);
  }

  /**
   * Executed prior to serialize().
   * This method should be overwritten. The default implementation is:
   * <code>
   * function __sleep() {
   *    return array_keys( get_object_vars($this) );
   * }
   * </code>
   * @return array
   */
  function __sleep()
  {
    //TODO: make sure to close and reopen logger filehandles
    return array_keys( get_object_vars($this) );
  }

  
  /**
   * Executed prior to unserialize().
   * This method should be overwritten.
   */
  function __wakeup()
  {}


}
?>
