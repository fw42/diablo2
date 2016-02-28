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

require_once('IsterXmlExpatNonValid.php');

/**
 * This class represents a SimpleXML implementation.
 *
 * The XML Parser extension (expat) is required to use IsterXmlSimpleXMLImpl.
 *
 * The class provides a pure PHP4 implementation of the PHP5
 * interface SimpleXML. As with PHP5's simpleXML it is what it says:
 * simple. Nevertheless, it is an easy way to deal with XML data,
 * especially for read only access.
 *
 * Because it's not possible to use the PHP5 ArrayIterator interface
 * with PHP4 there are some differences between this implementation
 * and that of PHP5:
 *
 * <ul> 
 * <li>The access to the root node has to be explicit in
 * IsterXmlSimpleXMLImpl, not implicit as with PHP5. Write
 * $doc->root->node instead of $doc->node</li>
 * <li>You cannot acces CDATA using array syntax. Use methods
 * CDATA() and setCDATA() instead.</li>
 * <li>You cannot access attributes directly with array syntax. 
 * Always use attributes() to read and setAttribute() to write attributes.</li>
 * <li>Comments are ignored.</li>
 * <li>Last and least, this is not as fast as PHP5 SimpleXML--it is pure PHP4.</li> 
 * </ul>
 *
 * The PHP5 implementation of IsterXmlSimpleXMLImpl will provide a
 * wrapper or proxy object with the same name to keep compatibility.
 *
 * Example:
 * <code>
 * :simple.xml:
 * <?xml version="1.0" encoding="utf-8" standalon="yes"?>
 * <root>
 *   <node>
 *     <child gender="m">Tom Foo</child>
 *     <child gender="f">Tamara Bar</child>
 *   <node>
 * </root>
 *
 * ---
 *
 * // read and write a document
 * $impl = new IsterXmlSimpleXMLImpl;
 * $doc  = $impl->load_file('simple.xml');
 * print $doc->asXML();
 *
 * // access a given node's CDATA
 * print $doc->root->node->child[0]->CDATA(); // Tom Foo
 *
 * // access attributes
 * $attr = $doc->root->node->child[1]->attributes();
 * print $attr['gender']; // f
 * 
 * // access children
 * foreach( $doc->root->node->children() as $child ) {
 *   print $child->CDATA();
 * }
 * 
 * // change or add CDATA
 * $doc->root->node->child[0]->setCDATA('Jane Foo');
 * 
 * // change or add attribute
 * $doc->root->node->child[0]->setAttribute('gender', 'f');
 * </code>
 *
 * Note: SimpleXML cannot be used to access sophisticated XML doctypes
 * using datatype ANY (e.g. XHTML). With a DOM implementation you can
 * handle this.
 *
 * 
 * @package xml
 * @subpackage simplexml
 * @author Ingo Schramm
 * @copyright Copyright (c) 2005 Ister.ORG Ingo Schramm
 */
class IsterXmlSimpleXMLImpl extends IsterObject
  {

    /**
     * @access private
     */
    var $expat;

    /**
     * Constructor
     *
     */
    function IsterXmlSimpleXMLImpl()
      {
        parent::IsterObject();

        $this->expat = new IsterXmlExpatNonValid;
      }

    /**
     * @param string
     * @param string  currently ignored
     * @return object SimpleXMLElement
     */
    function load_file($path, $classname = null)
    {
      $this->expat->setSourceFile($path);
      return $this->parse();
    }

    /**
     * @param string
     * @param string  currently ignored
     * @return object SimpleXMLElement
     */
    function load_string($string, $classname = null)
    {
      $this->expat->setSourceString($string);
      return $this->parse();
    }

    /**
     * This method is curently not implemented.
     *
     * @param string
     * @param string   currently ignored
     * @return object SimpleXMLElement
     */
    function import_dom($node, $classname = null)
    {
      return $this->log('not implemented', E_USER_WARNING, 'import_dom');
    }

    /**
     * @access private
     */
    function parse()
    {
      $this->expat->parse();
      $doc = $this->expat->getDocument();
	  // create a reference to doc to manipulate internal reference counter
	  // otherwise references won't work with nested arrays
	  // this is pretty a hack! (even works with 4.4.x)
	  // thanks to chat~kaptain524 at neverbox dot com at php.net
	  $r =& $doc;
      return $doc->toSimpleXML();
    }
    
}
?>
