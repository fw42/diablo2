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



require_once("IsterObject.php");

/**
 * Compare two objects.
 *
 * An IsterObjectChecker is initialized with a given object, which becomes the 
 * owner of this checker. Then the owner can compare other objects with given
 * class names.
 *
 * The benefit of this class is mainly consistent error reporting. Note that
 * warnings will be thrown at the owner object, not at the IsterObjectChecker object.
 *
 * @package ister.util
 * @author Ingo Schramm
 * @copyright Copyright &copy; 2005 Ister.ORG Ingo Schramm
 */
class IsterObjectChecker extends IsterObject
{

  /**
   * @access private
   */
  var $owner;

  /**
   * Constructor
   *
   * @param object IsterObject  Owner object.
   */
  function IsterObjectChecker($owner)
    {
      parent::IsterObject();
      if(! $this->_isObject($owner) )
        return false;
      if(! is_a( $owner, 'IsterObject' ) )
        return $this->log("Owner is not a subclass of 'IsterObject'",
                                   E_USER_WARNING, "IsterObjectChecker");
      $this->owner = $owner;
    }


  /**
   * Compare the owner class with the class of another object, strict.
   *
   * Return false if the class name of $object is not equal to that
   * given with $check, true otherwise, null on error.
   *
   * @param object Object The object to check.
   * @param string Name of class to check for.
   * @return boolean
   */
  function compareClassStrict( $object, $check )
    {
      if(! $this->_isObject( $object ) )
        return null;
      $objectclass = get_class($object);
      if( strcmp( strtolower($check), $objectclass ) )
        return $this->owner->log("Unexpected object '".$objectclass."', expected '".
                                          $check."'", E_USER_WARNING, "compareClassStrict");
      return true;
    }

  /**
   * Compare the owner class with the class of another object.
   *
   * Return false if class of $object is not a subclass of or the same
   * class as the class given with $check, true otherwise.
   *
   * @param object Object The object to check.  
   * @param string   Name of class to check for.
   * @return boolean
   */
  function compareClass( $object, $check )
    {
      if(! $this->_isObject( $object ) )
        return null;
      if( is_a( $object, $check ) )
        return true;
      $objectclass = get_class($object);
      return $this->owner->log("Class '".$objectclass."' not at least subclass of '".
                                        $check."'",
                                        E_USER_WARNING, "compareClass");
    }
  
  /**Check whether $object is an instance of a subclass of the class
   * given with $check.
   *
   * @param object Object The object to check.
   * @param string Name of class to check for.
   * @return boolean.
   */
  function isSubclassOf($object, $check)
    {
      if (! is_subclass_of($object, $check) )
        return $this->owner->log("expected sublass of '$check', got '".
                                          (get_class($object))."'",
                                          E_USER_WARNING, "isSubclassOf");
      return true;
    }


  /**
   * @access private
   */
  function _isObject( $object )
    {
      if( is_object($object) )
        return true;
      return $this->owner->log("Expected object, got '".(gettype($object))."'",
                                        E_USER_WARNING, "_isObject");
    }

}

?>
