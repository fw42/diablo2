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



require_once('IsterXmlExpat.php');


/**
 * This class represents an expat parser dumping everything as it is parsed to STDOUT.
 *
 * The parser expects UTF-8 input.
 *
 * @package ister.xml
 * @author Ingo Schramm
 * @copyright Copyright &copy; 2005 Ister.ORG Ingo Schramm
 */
class IsterXmlExpatDumper extends IsterXmlExpat
  {

    /**
     * @access private
     */
    var $dump;

    /**
     * Constructor
     * @param boolean   whether to dump to STDOUT or not
     */
    function IsterXmlExpatDumper($dump = true, $options = null)
      {
        parent::IsterXmlExpat($options);
        $this->dump = $dump;
      }


    /**
     * @param resource
     * @param string
     * @param array
     * @return boolean
     */
    function tag_open($parser, $tag, $attributes)
    {
      if(! $this->dump)
        return true;
      print 'tag_open:'."\t\t".$tag."\n";
      if( count($attributes) ) {
        print "\t\t\t".'attr:'."\n";
        foreach($attributes as $name => $val)
          print  "\t\t\t\t".$name.' = '.$val."\n";
      }
      return true;
    }

    /**
     * @param resource
     * @param string
     * @return boolean
     */
    function tag_close($parser, $tag)
    {
      if(! $this->dump)
        return true;
      print 'tag_close:'."\t\t".$tag."\n";
      return true;
    }

    /**
     * @param resource
     * @param string
     * @return boolean
     */
    function character_data($parser, $data)
    {
      if(! $this->dump)
        return true;
      $data = strtr($data, "\n", '%');
      $data = strtr($data, ' ', '.');
      print 'character_data:'."\t\t".$data."\n";
      return true;
    }


    /**
     * @param resource
     * @param string
     * @return boolean
     */
    function default_data($parser, $data)
    {
      if(! $this->dump)
        return true;
      $data = strtr($data, "\n", '%');
      $data = strtr($data, ' ', '.');
      print 'default_data:'."\t\t".$data."\n";
      return true;
    }

    /**
     * @param resource
     * @param string
     * @return boolean
     */
    function end_namespace_decl($parser, $data)
    {
      if(! $this->dump)
        return true;
      print 'end_namespace_decl:'."\t".$data."\n";
      return true;
    }

    /**
     * @param resource
     * @param string
     * @param string
     * @return boolean
     */
    function start_namespace_decl($parser, $id, $uri)
    {
      if(! $this->dump)
        return true;
      if(!$id)
        $id = '[default]';
      print 'start_namespace_decl:'."\t".$id.'='.$uri."\n";
      return true;
    }

    /**
     * @param resource
     * @param string
     * @param string
     * @param string
     * @param string
     * @return boolean
     */
    function external_entity_ref($parser, $openentitynames, $base, $systemid, $publicid)
    {
      if(! $this->dump)
        return true;
      print 'external_entity_ref:'."\t".$openentitynames.' '.$base.' '.$systemid.' '.$publicid."\n";
      return true;
    }

    /**
     * @param resource
     * @param string
     * @param string
     * @param string
     * @param string
     * @return boolean
     */
    function notation_decl($parser, $notationname, $base, $systemid, $publicid )
    {
      if(! $this->dump)
        return true;
      print 'notation_decl:'."\t".$notationname.' '.$base.' '.$systemid.' '.$publicid."\n";
      return true;
    }

    /**
     * @param resource
     * @param string
     * @param string
     * @return boolean
     */
    function processing_instruction($parser, $target, $data)
    {
      if(! $this->dump)
        return true;
      print 'processing_instruction:'."\t".$target.' '.$data."\n";
      return true;
    }

    /**
     * @param resource
     * @param string
     * @param string
     * @param string
     * @param string
     * @param string
     * @return boolean
     */
    function unparsed_entity($parser, $entityname, $base, $systemid, $publicid, $notationname)
    {
      if(! $this->dump)
        return true;
      print 'unparsed_entity:'."\t".$entityname.' '.$base.' '.$systemid.' '.$publicid.' '.$notationname."\n";
      return true;
    }



}
?>
