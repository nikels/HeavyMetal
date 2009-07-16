<?
/**
 * Ajax View
 * 
 * @copyright     Copyright 2009-2012 Jon Gilkison and Massify LLC
 * @link          http://wiki.getheavy.info/index.php/Ajax_Views
 * @package       system.app.http
 * @subpackage    view
 *
 * Copyright (c) 2009, Jon Gilkison and Massify LLC.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice,
 *   this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright
 *   notice, this list of conditions and the following disclaimer in the
 *   documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * This is a modified BSD license (the third clause has been removed).
 * The BSD license may be found here:
 * 
 * http://www.opensource.org/licenses/bsd-license.php
 */

uses('system.app.view');

/**
 * Ajax View
 */
 class AjaxView extends View
 {
 	/**
 	 * Parses out ajax tags 
 	 * 
 	 * @param string $rendered The view's rendered output that may contain layout tags
 	 */
 	protected function parse_other_tags(&$rendered)
 	{
 		// extract layout tags from rendered source
 		$tags=array();
		$ajax_tags='@<[\s]*ajax[\s]*:[\s]*(\w+)([^>]*?)>(.*?)<[\s]*/[\s]*ajax:(?:\w+)[\s]*>@is';
		
 		while(preg_match($ajax_tags,$rendered,$tags,PREG_OFFSET_CAPTURE)==1)
 		{
 			$tag=$tags[1][0];
 			$full_tag=$tags[0][0];
 			$attributes=$tags[2][0];
 			$content=trim(str_replace("\r",' ',str_replace("\n",' ',str_replace('"','\"',$tags[3][0]))));
 			
			$rawattrs=array();
			$attrs=array();
			preg_match_all(View::REGEX_ATTRIBUTE,trim($attributes),$rawattrs,PREG_SET_ORDER);
			foreach($rawattrs as $attr)
			{
				if (preg_match('#{[^}]*}#is',$attr[2]))
				{
					$key=trim(trim($attr[2],'{'),'}');
					if (isset($this->data[$key]))
						$attrs[$attr[1]]=$this->data[$key];
					else
						user_error("Cannot bind to variable '$key'.",E_USER_WARNING);
				}
				else
					$attrs[$attr[1]]=$attr[2];
			}
							
			$result='';
			
			switch($tag)
 			{
 				case 'update':
					$result="Element.update(\"".$attrs['id']."\",\"".$content."\");";
 	 				break;
 				case 'replace':
					$result="Element.replace(\"".$attrs['id']."\",\"".$content."\").remove();";
 	 				break;
 	 			case 'insert':
 					$where=(isset($attrs['where'])) ? $attrs['where'] : 'before';
					$result="new Insertion.".ucfirst(strtolower($where))."(\"".$attrs['id']."\",\"".$content."\");";
	 				break;
 				case 'remove':
 					$fade=(isset($attrs['fade'])) ? $attrs['fade'] : false;
					if ($fade)
						$result='Effect.Fade("'.$attrs['id'].'",{ afterFinish:function(effect) { $(effect.element).remove(); }});';
					else
						$result='$("'.$attrs['id'].'").remove();'; 
 	 				break;
 				case 'hide':
					$result='$("'.$attrs['id'].'").hide();'; 
 	 				break;
 				case 'show':
					$result='$("'.$attrs['id'].'").show();'; 
	 				break;
 			}
 			
 			$rendered=str_replace($full_tag,$result,$rendered)."\n";
 		}
  	}
 }