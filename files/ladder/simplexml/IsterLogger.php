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



define( 'ISTER_DEBUG_NOTICE', 1 );

/**
 * The base class of IsterLoggers.
 *
 * @package ister.util
 * @author Ingo Schramm
 * @copyright Copyright &copy; 2005 Ister.ORG Ingo Schramm
 */
class IsterLogger
  {

    /**
     * @var integer
     * @access protected
     */
    var $loglevel;

    /**
     * Constructor
     */
    function IsterLogger()
      {
        $this->loglevel = E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE;
      }

    /**
     * Return a string representation of the log level.
     * @param integer
     * @return string
     */
    function getLevelStr($level)
    {
      switch ( $level )
        {
        case E_NOTICE:
        case E_USER_NOTICE:
          return "NOTICE";
        case E_WARNING:
        case E_USER_WARNING:
          return "WARNING";
        case E_USER_ERROR:
          return "ERROR";
        case ISTER_DEBUG_NOTICE:
          return "DEBUG";
        }
      return false;
    }

    /**
     * Print the log message.
     *
     * @param string
     * @param integer
     * @param string
     * @param string
     */
    function log($msg, $level, $caller, $context)
      {
        print ($this->getLevelStr)." IsterLogger->log(): $msg\n";
      }


    /**
     * Setup the logger.
     * The array will be parsed, each key becomes a property of
     * the logger, each value the property's value.
     * @param array An array cntaining setup data.
     * @boolean
     */
    function setup( $array )
      {
        foreach( $array as $key => $value )
          $this->$key = $value;
        return true;
      }

    

}
?>
