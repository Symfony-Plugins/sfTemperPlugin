<?php
/**
 * Tag If
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
class Tag_If extends Temper_Tag
{
  protected $is_if = TRUE;

  public function parse_buffer()
  {
    $main = $this->require_one('var', 'isset');
    
    $operations = array(
      'eq' => '==',
      'eqvar' => '==',
      'neq' => '!=',
      'neqvar' => '!=',
      'gt' => '>',
      'lt' => '<',
      'geq' => '>=',
      'geqvar' => '>=',
      'leq' => '<=',
      'leqvar' => '<=',
    );
    
    if ($main == 'var')
    {
      $op = $this->require_one('eq', 'neq', 'gt', 'lt', 'geq', 'leq', 'eqvar', 'neqvar', 'geqvar', 'leqvar');
      $b = $this[$op];
      $var = "NULL";
      
      if (strpos($op, 'var') !== FALSE)
      {
        $var = '$'. $b .'';
      }
      else
      {
        if(ctype_digit($b))
        {
          $var = (int)$b;
        }
        elseif ((string) (float) $b === (string) $b)
        {
          $var = (float) $b;
        }
        else
        {
          $var = $b == 'NULL' ? $b : '"'. $b .'"';
        }
      }
    }
    else
    {
      $op = 'neq';
      $var = 'NULL';
    }
    
    $val = (strpos($this[$main], '::') === FALSE) ? '$' : '';
    $cmp = $val.$this[$main].' '. $operations[$op] .' '. $var;
    
    if ($main == 'isset')
    {
      $cmp = 'isset('.$val.$this[$main].') && '.$cmp;
    }
    
    /*$buffer[] = '<?php '. ($this->is_if ? 'if' : 'elseif') .'('.$val.$this[$main].' '. $operations[$op] .' '. $var.'):?>';*/
    $buffer[] = '<?php '. ($this->is_if ? 'if' : 'elseif') .'('.$cmp.'):?>';
    $buffer[] = $this->buffer();
    $buffer[] = $this->is_if ? '<?php endif;?>' : '';
    
    return implode("\n", $buffer);
  }
} //End Tag If