<?php
/**
 * Temper Tag Library
 *
 * @package     Temper Module
 * @author      Alex Sancho
 * @copyright (c) 2008 Alex Sancho
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. Neither the name of copyright holders nor the names of its
 *    contributors may be used to endorse or promote products derived
 *    from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED
 * TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL COPYRIGHT HOLDERS OR CONTRIBUTORS
 * BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */
abstract class Temper_Tag implements ArrayAccess {
  
  protected $buffer = '', $prefix = '', $name = '';
  private $attributes = array();
  
  /**
   * __construct
   *
   * @param string $prefix
   * @param string $name
   * @return void
   *
   */
  public function __construct($prefix = '', $name = '')
  {
    $this->prefix = $prefix;
    $this->name = $name;
  }
  
  /**
   * require_attributes
   *
   * @return exception
   *
   */
  public function require_attributes()
  {
    foreach(func_get_args() as $attrib)
    {
      if (!array_key_exists($attrib, $this->attributes))
      {
        throw new Exception("El atributo [".$attrib."] es requerido por la funcion [".$this->name."]");
      }
    }
  }

  /**
   * require_one
   *
   * @return mixed
   *
   */
  public function require_one()
  {
    $ret = FALSE;
    $attribs = func_get_args();
    
    foreach($attribs as $attrib)
    {
      if (array_key_exists($attrib, $this->attributes))
      {
        $ret = $attrib;
        break;
      }
    }
    
    if ($ret === FALSE)
    {
      throw new Exception("Al menos falta uno de los siguientes atributos [".implode(',', $attribs)."] requeridos por la funcion [".$this->name."]");
    }
    
    return $ret;
  }

  /**
   * buffer
   *
   * @param mixed $buffer
   * @return mixed 
   *
   */
  public function buffer($buffer = FALSE)
  {
    if ($buffer !== FALSE)
    {
      $this->buffer .= $buffer;
    }
    else
    {
      return $this->buffer;
    }
  }
  
  /**
   * add_attributes
   *
   * @param array $array
   * @return void
   *
   */
  public function add_attributes(array $array = array())
  {
    $this->attributes = array_merge($this->attributes, $array);
  }
  
  /**
   * get_name
   *
   * @return string
   *
   */
  public function get_name()
  {
    return $this->name;
  }

  /**
   * offsetGet
   *
   * @param int $key
   * @return mixed
   *
   */
  public function offsetGet($key)
  {
    return $this->offsetExists($key) ? $this->attributes[$key] : NULL;
  }

  /**
   * offsetSet
   *
   * @param int $key
   * @param mixed $val
   * @return void
   *
   */
  public function offsetSet($key, $val)
  {
    $this->attributes[$key] = $val;
  }

  /**
   * offsetExists
   *
   * @param int $key
   * @return bool
   *
   */
  public function offsetExists($key)
  {
    return isset($this->attributes[$key]);
  }

  /**
   * offsetUnset
   *
   * @param int $key
   * @return void
   *
   */
  public function offsetUnset($key)
  {
    unset($this->attributes[$key]);
  }
  
  /**
   * parse_buffer
   *
   */
  abstract public function parse_buffer();

} //End Temper Tag Library