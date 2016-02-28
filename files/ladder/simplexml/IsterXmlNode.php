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




require_once('IsterObject.php');
//require_once('IsterSimpleXMLElement.php');

/**
 * 
 */
define('ISTER_XML_DOCUMENT',  1);
/**
 * 
 */
define('ISTER_XML_TAG'     ,  2);
/**
 * 
 */
define('ISTER_XML_CDATA'   ,  3);
/**
 * 
 */
define('ISTER_XML_COMMENT' ,  4);
/**
 * 
 */
define('ISTER_XML_PI'      ,  5);
/**
 * 
 */
define('ISTER_XML_NOTATION',  6);
/**
 * 
 */
define('ISTER_XML_ENTITY'  ,  7);
/**
 * 
 */
define('ISTER_XML_PENTITY' ,  8);
/**
 * 
 */
define('ISTER_XML_DOCTYPE' ,  9);


/**
 * This class represents a generic XML node.
 *
 * It may represent a complete document, a document fragment
 * or only a single node of any type such as tag, comment, cdata.
 *
 * @package ister.xml
 * @author Ingo Schramm
 * @copyright Copyright &copy; 2005 Ister.ORG Ingo Schramm
 */
class IsterXmlNode extends IsterObject
  {

    /**
     * type
     * @access protected
     */
    var $___t;
    /**
     * level
     * @access protected
     */
    var $___l;
    /**
     * namespace
     * @access protected
     */
    var $___ns;
    /**
     * name (for cdata: content)
     * @access protected
     */
    var $___n;    
    /**
     * attributes
     * @access protected
     */
    var $___a;    
    /**
     * children
     * @access protected
     */
    var $___c;

    /**
     * Constructor
     *
     * @param integer
     * @param integer
     * @param string
     * @param string
     */
    function IsterXmlNode($type = null, $level = null, $name = null, $ns = null)
      {
        parent::IsterObject();

        $this->___a  = array();
        $this->___t  = $type;
        $this->___l  = $level;
        $this->___n  = $name;
        $this->___ns = $ns;
        $this->___c  = array();
      }

    /**
     * Return the current node formatted as XML.
     *
     * Note: Since this node only knows of its encoding if it is an ISTER_XML_DOCUMENT,
     * you have to recode it as needed by yourself. The default is to return UTF-8.
     *
     * @return string
     */
    function asXML()
    {
      $xml = '';

      switch( $this->___t ) { 
      case ISTER_XML_DOCUMENT:
        $xml .= $this->getChildrenXml();
        break;
      case ISTER_XML_TAG:
        // add namespace declarations for root node
        if( ($this->___l == 1) && (is_array($this->___ns)) ) {
          $count = 0;
          foreach( $this->___ns as $id => $uri ) {
            if( ++$count % 2 ) {
              if(! $id )
                $this->___a['xmlns'] = $uri;
              else
                $this->___a['xmlns:'.$id] = $uri;
            }
          }
        }
        $xml .= '<';
        if($this->___ns && (! is_array($this->___ns)))
          $xml .= $this->___ns.':';
        $xml .= $this->___n;
        if( count($this->___a) ) {
          foreach( $this->___a as $name => $value )
            $xml .= ' '.$name.'="'.$value.'"';
        }
        if(! $this->hasChildren() ) {
          $xml .= '/>';
        }
        else {
          $xml .= '>';
          $xml .= $this->getChildrenXml();
          $xml .= '</'.$this->___n.'>';
        }
        break;
      case ISTER_XML_COMMENT:
      case ISTER_XML_CDATA:
        $xml .= $this->___n;
        break;
      case ISTER_XML_PENTITY:
      case ISTER_XML_ENTITY:
        $xml .= '&'.$this->___n.';';
        break;
      case ISTER_XML_PI:
        $xml .= '<?'.$this->___ns.' '.$this->___n.'?>';
        break;
      default:
        $this->log('unknown node type: '.$this->___t, E_USER_WARNING, 'asXML');
      }

      return $xml;
    }


    /**
     * Runs Xpath query on the node.
     *
     * Note: currently this function supports only a subset of XPath.
     *
     * @return mixed   array or object IsterXmlNode
     */
    function xpath()
    {
      return $this->log('not implemented', E_USER_WARNING, 'xpath');
    }


    /**
     * Append an element.
     *
     * @param object IsterXmlNode
     * @return boolean
     */     
    function append($element)
     {
       // is it a descendant element?
       // then pass it to the last child
       if( $element->___l > ($this->___l + 1 ) ) {
         // find last ISTER_XML_TAG
         foreach( array_reverse(array_keys($this->___c)) as $index ) {
           if( $this->___c[$index]->___t == ISTER_XML_TAG )
             return $this->___c[$index]->append($element);
         }
       }
       $this->___c[] = $element;
       return true;
     }


    /**
     * Add or alter an attribute.
     * @param string
     * @param string
     * @return boolean
     */
    function setAttribute($name, $value)
    {
      $this->___a[$name] = $value;
      return true;
    }

    
    /**
     * Return number of children.
     * @return integer
     */
    function hasChildren()
    {
      if($count = count($this->___c)) {
        //search for parsed entities and add count
        foreach( $this->___c as $child ) {
          if($child->___t == ISTER_XML_PENTITY ) {
            // count entitie's nodes
            $count += count($child->___ns);
            // don't count entity node itself
            --$count;
          }//if
        }//foreach
      }//if
      return $count;
    }


    /**
     * Perform conversion of an IsterXmlNode to a SimpleXMLElement.
     *
     * @return object SimpleXMLElement
     */
    function toSimpleXML()
    {
      //TODO benchmark eval'ed or plain require
      eval('require_once("IsterSimpleXMLElement.php");');
      // traverse tree and convert all descendants
      foreach( $this->___c as $idx => $child ) {
        $this->___c[$idx] = $child->toSimpleXML();
      }
      // make this an instance of the proper class
      $simple = new IsterSimpleXMLElement;
      $simple->init($this);
      return $simple;
    }


    /**
     * 
     * @return object IsterDOMObject
     */
    function toDOM()
    {
      return $this->log('not implemented', E_USER_WARNING, 'toDOM');
    }

    /**
     * Get the documents root node if this node represents a document. 
     *
     * Return false on error.
     * @return object IsterXmlNode 
     */
    function getRoot()
    {
      if(! $this->___t == ISTER_XML_DOCUMENT )
        return false;
      foreach( $this->___c as $child ) {
        if( $child->___t == ISTER_XML_TAG )
          return $child;
      }
      return false;
    }

    /**
     * @access protected
     */
    function getChildrenXml()
    {
      $xml = '';
      if( $this->hasChildren() ) {
		  foreach( $this->___c as $child )
			  $xml .= $child->asXML();
      }
      return $xml;
    }

}
?>
