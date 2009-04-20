<?php
/**
 * Temper (Template Parser) Library
 * based on http://ioreader.com/2007/05/08/using-a-stack-to-parse-html/
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
class Temper {
  
  const TEMPER_TAGS            = 'if,else,elseif,for,foreach,switch,case,print,block,import';
  const TEMPER_PREFIX          = 't';
  const TEMPER_ALLOW_PHP       = false;
  const TEMPER_ALLOW_HELPERS   = false;
  const TEMPER_REMOVE_COMMENTS = true;
  const TEMPER_TEMPLATE_DIR    = 'templates';
  
  protected $buffer = NULL;
  protected $tag_prefix;
  protected $tag_handlers = array();

  /**
   * factory
   *
   * @param string $template template name
   * @param string $buffer buffer data
   * @return object
   * @access public
   * 
   */
  public static function factory($template = FALSE, $buffer = NULL)
  {
      return new Temper($template, $buffer);
  }

  /**
   * __construct
   *
   * @param string $template template name
   * @param string $buffer buffer data
   * @return void
   * @access public
   *
   */
  public function __construct($template = false, $buffer = NULL)
  {
    foreach(sfConfig::get('app_sfTemperPlugin_tags', explode(',', self::TEMPER_TAGS)) as $tag)
    {
      $this->add_tag($tag);
    }
    
    $this->tag_prefix = sfConfig::get('app_sfTemperPlugin_prefix', self::TEMPER_PREFIX);
    
    $this->buffer = ($template && is_file($template)) ? file_get_contents($template) : $buffer;
  }
  
  /**
   * __toString
   *
   * @return string buffer data
   * @access public
   *
   */
  public function __toString()
  {
    return (string) $this->buffer;
  }
  
  /**
   * add_tag
   *
   * @param string $tag_name tag name
   * @return void
   * @access public
   *
   */
  public function add_tag($tag_name)
  {
    $class_name = 'Tag_'.ucfirst($tag_name);
    
    if (!class_exists($class_name))
    {
      throw new Exception("Selected tag [$class_name] does not exists or is not allowed.");
    }
    
    $this->tag_handlers[$tag_name] = $class_name;
  }
  
  /**
   * get_tag_handler
   *
   * @param string $tag_name tag name
   * @return string tag handler class name
   * @access public
   *
   */
  public function get_tag_handler($prefix, $tag_name)
  {
    $ret = 'Tag_Unknown';
    if (isset($this->tag_handlers[$tag_name]))
    {
      $ret = $this->tag_handlers[$tag_name];
    }
    return new $ret($prefix, $tag_name);
  }
  
  /**
   * parse
   *
   * @param string $file output file name
   * @return object
   * @access public
   *
   */
  public function parse($file = null)
  {
    // Si PHP NO está permitido en la configuración:
    if (!((int) sfConfig::get('app_sfTemperPlugin_allow_php', self::TEMPER_ALLOW_PHP)))
    {
      $this->buffer = preg_replace('/<\?(?=php|=|\s).*?\?>/ms', '<!-- REMOVED -->', $this->buffer);
    }
    
    $this->parse_callback()->parse_tags();
    
    // Si en la configuración se especifica borrar comentarios:
    if ((int) sfConfig::get('app_sfTemperPlugin_remove_comments', self::TEMPER_REMOVE_COMMENTS))
    {
      $this->buffer = preg_replace('/(^\s*)?<!-- #(.*?)-->/ms', '', $this->buffer);
    }
    
    if (count(Temper_Tag::$helpers))
    {
      $this->buffer = Temper_Tag::get_used_helpers()."\n".$this->buffer;
    }
    
    // Si hay que escribir el fichero...
    if ($file)
    {
      if (!$fp = fopen($file, 'w'))
      {
        throw new Exception("Template output file [$file] could not be opened to write data.");
      }
      
      if (fwrite($fp, $this->buffer) === FALSE)
      {
        throw new Exception("You have not enough permissions to write into the template file/directory.");
      }
      
      fclose($fp);
      
      @chmod($file, 0666);
    }
    
    return $this;
  }
  
  /**
   * parse_callback
   *
   * @return object
   * @access protected
   *
   */
  protected function parse_callback()
  {
    $this->buffer = preg_replace_callback('~{(\%|\/|\=)([^}]*)?}~', array($this, 'parse_vars'), $this->buffer);
    $this->buffer = preg_replace_callback('~{{.*?}}~', array($this, 'parse_funcs'), $this->buffer);
    return $this;
  }
  
  /**
   * parse_vars
   *
   * @param array $matches array containing variable matches
   * @return string parsed string
   * @access protected
   *
   */
  protected function parse_vars($matches)
  {
    if ($matches[1] == '/')
    {
      //sfLoader::loadHelpers('Url');
      
      if ($matches[0] == '{/}')
      {
        // Site root
        $matches[2] = '@homepage';
      }
      
      // TODO: How to parse links through Symfony's routing system
      
      // ORIGINAL // $matches[2] = preg_replace_callback("~(\\=)([^/]+)~", array($this, 'parse_vars'), url::site($matches[2]));
      // ORIGINAL // return $matches[2]
      return $matches[0]; // temporal
    }
    else
    {
      $matches[2] = trim($matches[2]);
      
      if (strpos($matches[2], '|') !== FALSE)
      {
        $temp = explode('|', $matches[2]);
        $matches[2] = $temp[0];
        $filter = $temp[1];
      }
      
      if (strpos($matches[2], '.') !== FALSE)
      {
        //ORIGINAL//$var = explode('.', $matches[2], 2);
        //ORIGINAL//$matches[2] = 'template::get_var(\''.$var[1].'\', $'.$var[0].')';
        
        $tmp = '$';
        foreach(explode('.', $matches[2]) as $i => $key)
        {
          if ($i == 0)
          {
            $tmp .= $key;
          }
          else
          {
            $tmp .= "->".'get'.sfInflector::camelize($key).'()';
          }
        }
        
        $matches[2] = $tmp;
      }
      else
      {
        $matches[2] = '$'.$matches[2];
      }
      
      $matches[2] = (isset($filter) AND function_exists($filter)) ? ''.$filter.'('.$matches[2].')' : $matches[2];
      
      if($matches[1] == '=')
      {
        $matches[2] = '<?php echo '. $matches[2] .' ?>';
      }
    }
    
    return $matches[2];
  }
  
  /**
   * parse_funcs
   *
   * @param string $matches
   * @return string
   *
   */
  protected function parse_funcs($matches)
  {
    if (preg_match('/^\{\{([a-zA-Z_0-9]+)\(+([^*]+)\)\}\}$/', $matches[0], $helpers)) 
    {
      if (is_callable($helpers[1], true)) 
      {
        $function = strtolower($helpers[1]) == 'echo' ? $helpers[1] : 'echo '.$helpers[1];
        $function = '<?php '.$function.'('.$helpers[2].');?>';
      }
    }
    elseif (preg_match('/^\{\{([a-zA-Z_0-9]+)::([a-zA-Z_0-9]+)\(+([^*]+)\)\}\}$/', $matches[0], $helpers)) 
    {
      if (method_exists($helpers[1], $helpers[2])) 
      {
        $function = '<?php echo call_user_func_array(array(\''.$helpers[1].'\', \''.$helpers[2].'\'), array('.$helpers[3].')); ?>';
      }
    }
    
    if ((int) sfConfig::get('app_sfTemperPlugin_allow_helpers', self::TEMPER_ALLOW_HELPERS) AND isset($function))
    {
      $matches[0] = $function;
    }
    elseif (isset($function))
    {
      $matches[0] = '<!-- REMOVED -->';
    }
    
    return $matches[0];
  }
  
  /**
   * parse_tags
   *
   * @return void
   * @access protected
   *
   */
  protected function parse_tags()
  {
    $parts = preg_split("~<(/?)([".preg_quote($this->tag_prefix)."]+)\:([a-z0-9_]+)((?: [^>]*)?)>~i", $this->buffer, -1, PREG_SPLIT_DELIM_CAPTURE);
    $stack = array();
    $stack[] = new Tag_Base;
    $i = -1;
    
    while(isset($parts[++$i]))
    {
      $parent = end($stack);
      $key = $i % 5;
      
      if ($key == 0)
      {
        $parent->buffer($parts[$i]);
      }
      
      if (isset($parts[$i+4]))
      {
        $closing = trim($parts[$i+1] == '/');
        $tag_name = trim($parts[$i+3]);
        $attribs = trim($parts[$i+4]);
        $non_closing = FALSE;
        
        if ($attribs != '' AND $attribs{strlen($attribs)-1} == '/')
        {
          $non_closing = TRUE;
          $attribs = trim(substr($attribs, 0, -1));
        }
        
        if ($closing)
        {
          $tag = array_pop($stack);
          $parent = end($stack);
          
          if ($tag_name == $tag->get_name())
          {
            $parent->buffer($tag->parse_buffer());
          }
          else
          {
            $parent->buffer('<!-- BAD CLOSING TAG FOR ['. $this->tag_prefix .':'. $tag_name .'] -->');
          }
        }
        else
        {
          $tag = $this->get_tag_handler($this->tag_prefix, $tag_name);
          
          if ($attribs != '')
          {
            $this->parse_tag_attributes($tag, $attribs);
          }
          
          if ($non_closing)
          {
            $parent->buffer($tag->parse_buffer());
          }
          else
          {
            $stack[] = $tag;
          }
        }
      }
      
      $i += 4;
    }
    
    $temp_buffer = '';
    
    while($node = array_pop($stack))
    {
      if ($node instanceof Tag_Base)
      {
        $node->buffer($temp_buffer);
        $this->buffer = $node->parse_buffer();
      }
      elseif ($node instanceof Temper_Tag)
      {
        $temp_buffer .= $node->buffer();
      }
      else
      {
        throw new Exception("Selected tag [".get_class($node)."] is not an instance of Temper_Tag.");
      }
    }
    
    return $this;
  }
  
  /**
   * parse_tag_attributes
   *
   * @param object $tag
   * @param string $attrs
   * @return void
   * @access protected
   *
   */
  private function parse_tag_attributes(Temper_Tag $tag, $attrs = '')
  {
    $attributes = array();
    preg_match_all('~(?P<attr>[a-z]+)="(?P<val>[^"]*)"~i', $attrs, $parts);
    
    foreach($parts['attr'] as $i => $attr)
    {
      $attributes[strtolower($attr)] = $parts['val'][$i];
    }
    
    if (!empty($attributes))
    {
      $tag->add_attributes($attributes);
    }
  }
  
  public function getBuffer()
  {
    return $this->buffer;
  }
  
  public static function getTemplateDirPath()
  {
    $path = sfConfig::get('sf_upload_dir').DIRECTORY_SEPARATOR.sfConfig::get('app_sfTemperPlugin_template_dir', self::TEMPER_TEMPLATE_DIR);
    
    if (!is_dir($path))
    {
      @mkdir($path);
      
      if (!is_dir($path))
      {
        return sfConfig::get('sf_upload_dir');
      }
    }
    
    return $path;
  }
}
