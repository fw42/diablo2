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

//
//
// $Id$

require_once('IsterObject.php');


/**
 * This class represents a generic namespace aware expat parser.
 *
 * Child classes <b>must</b> implement the following methods:
 * <code>
 * $child->tag_open($parser, $tag, $attributes);
 * $child->tag_close($parser, $tag);
 * $child->character_data($parser, $data);
 * $child->default_data($parser, $data);
 * $child->end_namespace_decl($parser, $data);
 * $child->start_namespace_decl($parser, $data);
 * $child->external_entity_ref($parser, $openentitynames, $base, $systemid, $publicid);
 * $child->notation_decl($parser, $notationname, $base, $systemid, $publicid );
 * $child->processing_instruction($parser, $target, $data);
 * $child->unparsed_entity_decl($parser, $entityname, $base, $systemid, $publicid, $notationname);
 * </code>
 *
 * The parser expects UTF-8 input.
 *
 * 
 * @package xml
 * @author Ingo Schramm
 * @copyright Copyright (c) 2005 Ister.ORG Ingo Schramm
 */
class IsterXmlExpat extends IsterObject
  {

    /**
     * @access protected
     */
    var $string;
    /**
     * @access protected
     */
    var $parser;
    /**
     * @access protected
     */
    var $xml;
    /**
     * @access private
     */
    var $registered;
    /**
     * @access private
     */
    var $fp;

    /**
     * Constructor
     *
     */
    function IsterXmlExpat($options = null)
    {
      parent::IsterObject();

      if(! function_exists('xml_parser_create_ns'))
        return $this->log('expat extension seems not to be present',
                                   E_USER_WARNING, 'IsterXmlSimpleXMLExpat');

      $this->string     = false;
      $this->registered = false;
      $this->fp         = null;
      $this->bufsize    = 0;
      $this->parser     = xml_parser_create_ns('UTF-8');
      // check parser resource
      xml_set_object($this->parser, $this);
      xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
      if( is_array($options) ) {
        foreach( $options as $opt => $val )
          xml_parser_set_option($this->parser, $opt, $val);
      }
    }


    /**
     *
     * @param string
     * @param integer
     * @return boolean
     */
    function setSourceFile($path, $bufsize = 8192)
    {
      if(! $this->fp = fopen($path, 'rb') ) {
        return $this->log('Could not open file: '.$path, E_USER_WARNING, 'setSourceFile');
      }
      $this->bufsize = $bufsize;
      return true;
    }

    /**
     * @param string
     * @return boolean
     */
    function setSourceString($string)
    {
      $this->xml = $string;
      return $this->string = true;
    }


    /**
     *
     * @return resource
     */
    function getParser()
    {
      return $this->parser;
    }

    /**
     * @param resource
     * @return boolean
     */
    function setParser($parser)
    {
      return $this->parser = $parser;
    }

    /**
     * Parse the current XML source.
     *
     * @return mixed   true if final, false otherwise, null on error
     */
	 function parse()
	 {
		 $final = false;
		 
		 if(! $this->registered)
			 $this->register();
		 
		 if(! $this->string && ! $this->fp) {
			 $this->log('nothing to read', E_USER_WARNING, 'parse');
			 return null;
		 }
		 
		 while(! $final ) {
			 
			 if( $this->string ) {
				 $final = true;
			 }
			 else {
				 $this->xml = fread($this->fp, $this->bufsize);
				 $final = feof($this->fp);
			 }
			 
			 if(! xml_parse($this->parser, $this->xml, $final) ) {
				 $err = xml_error_string( xml_get_error_code( $this->parser ));
				 $this->log('expat: '.$err.' ['.(implode('/',$this->locate())).']', E_USER_WARNING, 'parse');
				 return null;
			 }
		 }
		 xml_parser_free($this->parser);
		 return $final;
	 }

    
    /**
     * Get current parser position.
     *
     * <code>
     * array( 'byte'   => byte index,
     *        'column' => column number,
     *        'line'   => line number)
     * </code>
     *
     * @return array
     */
    function locate()
    {
      return array( 'byte'   => xml_get_current_byte_index($this->parser),
                    'column' => xml_get_current_column_number($this->parser),
                    'line'   => xml_get_current_line_number($this->parser)
                    );
    }

    /**
     *
     * @access protected
     */
    function register()
    {
      xml_set_character_data_handler($this->parser, 'character_data');
      xml_set_default_handler($this->parser, 'default_data');
      xml_set_element_handler($this->parser, 'tag_open', 'tag_close');
      xml_set_end_namespace_decl_handler($this->parser, 'end_namespace_decl');
      xml_set_external_entity_ref_handler($this->parser, 'external_entity_ref');
      xml_set_notation_decl_handler($this->parser, 'notation_decl');
      xml_set_processing_instruction_handler($this->parser, 'processing_instruction');
      xml_set_start_namespace_decl_handler($this->parser, 'start_namespace_decl');
      xml_set_unparsed_entity_decl_handler($this->parser, 'unparsed_entity');
      return $this->registered = 1;
    }



}
?>
