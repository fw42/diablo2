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
require_once('IsterXmlNode.php');



// states of default handler

/**
 * @access private
 */
define('ISTER_XML_DS_ZERO',          0);
/**
 * @access private
 */
define('ISTER_XML_DS_CDATA',         1);
/**
 * @access private
 */
define('ISTER_XML_DS_ENTITY',        2);
/**
 * @access private
 */
define('ISTER_XML_DS_HEADER',        3);
/**
 * @access private
 */
define('ISTER_XML_DS_DOCTYPE',       4);
/**
 * @access private
 */
define('ISTER_XML_DS_DTD',           5);
/**
 * @access private
 */
define('ISTER_XML_DS_ENTITYDECL',    6);
/**
 * @access private
 */
define('ISTER_XML_DS_NOTATIONDECL',  7);
/**
 * @access private
 */
define('ISTER_XML_DS_ANYDECL',       8);


/**
 * namespace of XInclude
 * @access private
 */
define('ISTER_NS_XINCLUDE_1_0', 'http://www.w3.org/2001/XInclude');

/**
 * This class represents a non validating expat parser.
 *
 * The parser expects UTF-8 input.
 *
 * Note: There is still a problem with character entities if the
 * resulting character is multibyte. They are automatically resolved
 * by the underlying expat parser but at the moment it is not possible
 * to restore them for write back of the XML document.
 *
 * Note: Since this builds a complete document tree, it has a
 * relatively large memory footprint.
 *
 * @package ister.xml
 * @author Ingo Schramm
 * @copyright Copyright &copy; 2005 Ister.ORG Ingo Schramm
 */
class IsterXmlExpatNonValid extends IsterXmlExpat
  {

    /**
     * @access private
     */
    var $doc;
    /**
     * @access private
     */
    var $level;
    /**
     * @access private
     */
    var $defbuf;
    /**
     * @access private
     */
    var $defstack;

    /**
     * Constructor
     *
     */
    function IsterXmlExpatNonValid($options = null)
      {
        parent::IsterXmlExpat($options);

        $this->doc      = new IsterXmlNode(ISTER_XML_DOCUMENT, 0);
        // level is used only for insertion
        // think about *not* to store level in node
        $this->level    = 1;
        $this->header   = '';
        $this->defbuf   = array();
        $this->dtdbuf   = '';
        $this->defstack = array();
      }


    /**
     *
     * @return object IsterXmlElement
     */
    function getDocument()
    {
      return $this->doc;
    }


    /**
     * Process XInclude
     */
    function xinclude()
    {
      return $this->log('not implemented', E_USER_WARNING, 'xinclude');
    }


    /**
     * @param resource
     * @param string
     * @param array
     * @return boolean
     */
    function tag_open($parser, $tag, $attributes)
    {
      $len   = strlen($tag);
      $delim = strrpos($tag, ':');
      $ns    = substr($tag, 0, $len - ($len - $delim) );
      $ns    = $this->doc->___ns[$ns];
      $name  = substr($tag, ++$delim);
      $elem  = new IsterXmlNode(ISTER_XML_TAG, $this->level++, $name, $ns);
      //++$this->level;
      $elem->___a = $attributes;
      // add namespace declarations to root node
      if( $this->level == 1 )
        $elem->___ns = $this->doc->___ns;
      return $this->doc->append($elem);
    }

    /**
     * @param resource
     * @param string
     * @return boolean
     */
    function tag_close($parser, $tag)
    {
      --$this->level;
      return true;
    }

    /**
     * @param resource
     * @param string
     * @return boolean
     */
    function character_data($parser, $data)
    {
      if( strlen($data) == 1 ) {
        if( strstr('<>&\'"', $data) !== false ) {
          $ent = htmlentities($data);
          $ent = substr( $ent, 1, strlen($ent) - 2);
          return $this->doc->append( new IsterXmlNode(ISTER_XML_ENTITY,
                                                      $this->level,
                                                      $ent,
                                                      $data) );
        }
      }
      // TODO: try to determine if a character was stored as
      //       character reference and store an entity node with that reference
      $this->doc->append( new IsterXmlNode(ISTER_XML_CDATA, $this->level, $data) );
      return true;
    }

    /**
     * @param resource
     * @param string
     * @return boolean
     */
    function default_data($parser, $data)
    {
      switch( substr($data, 0, 1) ) {
      case '<': //open
        switch( substr($data, 1, 1) ) {
        case '?': //xml declaration
          $what = '';
          $decl = preg_split('/\s+/', $data);
          $this->doc->___xml = array();
          foreach($decl as $idx => $value) {
            switch($idx) {
            case 1:
              $what = 'ver';
              break;
            case 2:
              $what = 'enc';
              break;
            case 3:
              $what = 'sdl';
              break;
            }
            if($what)
              $this->doc->___xml[$what] = $this->getAttrValue($value);
          }
          $this->doc->append( new IsterXmlNode(ISTER_XML_CDATA, $this->level, $data) );
          return true;
        case '!': //doctype, entity, notation, comment
          switch( substr($data, 2, 1) ) {
          case 'D': //doctype
            $this->defstack[] = ISTER_XML_DS_HEADER;
            $this->defstack[] = ISTER_XML_DS_DOCTYPE;
            break;
          case 'E': //entity
            $this->defstack[] = ISTER_XML_DS_ENTITYDECL;
            break;
          case '[': //CDATA
            // return after adding
            return $this->doc->append( new IsterXmlNode(ISTER_XML_CDATA, $this->level, $data) );
          case '-': //comment
            // return after adding comment node
            // otherwise it will be added second time at bottom of method
            return $this->doc->append( new IsterXmlNode(ISTER_XML_COMMENT, $this->level, $data) );
          default:
            //ELEMENT, ATTLIST etc.
            $this->defstack[] = ISTER_XML_DS_ANYDECL;
          }
          break;
        }
        break;
      case '[': // open DTD
        $this->defstack[] = ISTER_XML_DS_DTD;
        break;
      case ']': // end something
        switch( substr($data, 1, 1) ) {
        case false: // end DTD
          array_pop($this->defstack);
          break;
        case ']':
          //end CDATA; return after adding
          return $this->doc->append( new IsterXmlNode(ISTER_XML_CDATA, $this->level, $data) );
        }
        break;
      case '>':
        //end declaration
        switch( end($this->defstack) ) {
        case ISTER_XML_DS_DOCTYPE:
          $arr = preg_split('/\s+/', trim($this->dtdbuf));
          $doctype['type'] = $arr[0];
          $doctype['sys']  = trim($arr[2], '"');
          if($arr[1] == 'PUBLIC')
            $doctype['pub']  = $arr[0];
          $this->doc->___doctype = $doctype;          
          break;
        case ISTER_XML_DS_ENTITYDECL:
          // external entities
          if( count($this->defbuf) > 2 )
            break;
          $name = $this->defbuf[0];
          $val  = trim($this->defbuf[1], '"');
          // internal analysed entities
          if( preg_match('/^\s*</', $val) ) {
            $xml = '<?xml version="1.0"';
            if( isset($this->doc->___xml['enc']) )
              $xml .=  ' encoding="'.$this->doc->___xml['enc'].'"';
            $xml .= '?><ister:r>'.$val.'</ister:r>';
            $root = $this->parseExtra($xml);
            $val  = $root->___c;
          }
          $this->doc->___entities[$name] = $val;
          $this->defbuf = array();
          break;
        default:
          //close ELEMENT, ATTLIST etc. here if needed
        }
        array_pop($this->defstack);
        break;
      case '&':
        if( substr($data, -1, 1) == ';' ) {
          // substitute entity
          $ent   = substr($data, 1, strlen($data) - 2);
          $subst = $this->doc->___entities[$ent];
          // store entity in 'name' field
          // and value in 'ns' field
          $type = is_array($subst) ? ISTER_XML_PENTITY : ISTER_XML_ENTITY;
          return $this->doc->append( new IsterXmlNode($type, $this->level, $ent, $subst) );
        }
        break;
      default:
        if( end($this->defstack) == ISTER_XML_DS_DOCTYPE )
          $this->dtdbuf .= $data;
        if( end($this->defstack) == ISTER_XML_DS_ENTITYDECL )
          if( preg_match('/\S/', $data) )
            $this->defbuf[] = $data;
      }
      $stack = end($this->defstack);
      if( $stack < ISTER_XML_DS_HEADER )
        return $this->doc->append( new IsterXmlNode(ISTER_XML_CDATA, $this->level, $data) );
      $this->header .= $data;
      if( $stack == ISTER_XML_DS_HEADER ) {
        array_pop($this->defstack);
        $this->doc->append( new IsterXmlNode(ISTER_XML_CDATA, $this->level, $this->header) );
      }
      return true;
    }

    /**
     * @param resource
     * @param string
     * @return boolean
     */
    function end_namespace_decl($parser, $data)
    {
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
      if(! $id )
        // default namespace
        $id = 0;
      $this->doc->___ns[$id]  = $uri;
      $this->doc->___ns[$uri] = $id;
      return true;
    }

    /**
     * Note: returning a false value triggers a warning in expat parser.
     * @param resource
     * @param string
     * @param string
     * @param string
     * @param string
     * @return boolean
     */
    function external_entity_ref($parser, $openentitynames, $base, $systemid, $publicid)
    {
      //TODO check if file exists
      if( $systemid ) {
        $file = $systemid;
      }
      else {
        //TODO
        $this->log('handling of PUBLIC not implemented',
                            E_USER_WARNING, 'external_entity_ref');
      }
      $external = $this->parseExtra($file, true);
      $this->doc->append($external);

      unset($expat);
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
      $this->doc->___notations[$notationname] = $this->dtd2arr('NOTATION',
                                                               $notationname, 
                                                               $base,
                                                               $systemid, 
                                                               $publicid);
      return true;
    }

    /**
     * Handle processing instructions.
     *
     * Subclasses of this class may implement a method
     * <pre>pi_run(string $target, string $data)</pre>. If such a
     * method exists, it is called whenever a processing instruction
     * was parsed.
     *
     * @param resource
     * @param string
     * @param string
     * @return boolean
     */
    function processing_instruction($parser, $target, $data)
    {
      if( method_exists($this, 'pi_run') )
        return $this->pi_run($target, $data);
      $this->doc->append( new IsterXmlNode(ISTER_XML_PI, $this->level, $data, $target) );
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
      $this->doc->___unparsed[$entityname] = $this->dtd2arr('ENTITY',
                                                            $entityname,
                                                            $base,
                                                            $systemid,
                                                            $publicid,
                                                            $notationname);
      return true;
    }


    /**
     * @access private
     */
    function dtd2arr($type, $name, $base, $sys, $pub, $notation = null)
    {
      $pointer = $pub ? 'PUBLIC'  : 'SYSTEM';
      $arr = array('name' => $name,
                   'base' => $base,
                   'sys'  => $sys);
      $str = '<!'.$type.' '.$name.' '.$pointer.' "'.$sys.'"';
      if($pub) {
        $arr['pub'] = $pub;
        $str .= ' "'.$pub.'"';
      }
      if($notation) {
        // unparsed entities are passed to default_handler too
        $arr['not'] = $notation;
        $str = $notation;
      }
      else
        $str .= '>';
      if( end($this->defstack) > ISTER_XML_DS_HEADER )
        $this->header .= $str;
      return $arr;
    }


    /**
     * @access private
     */
    function getAttrValue($def)
    {
      if( preg_match('/^.+"(.+)"\s*.*$/', $def, $matches) )
        return $matches[1];
      return false;
    }


    /**
     * @access private
     */
    function parseExtra($source, $file = false)
    {
      $myclass = get_class($this);
      $expat   = new $myclass;
      if($file)
        $expat->setSourceFile($source);
      else
        $expat->setSourceString($source);
      $expat->parse();
      $extra = $expat->getDocument();
      $extra = $extra->getRoot();
      $extra->___l =  $this->level;
      return $extra;
    } 
}
?>
