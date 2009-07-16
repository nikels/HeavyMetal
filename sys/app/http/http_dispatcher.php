<?
/**
 * The main request dispatcher.
 * 
 * @copyright     Copyright 2009-2012 Jon Gilkison and Massify LLC
 * @link          http://wiki.getheavy.info/index.php/Dispatcher
 * @package       system.app
 * @subpackage    http
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


uses('system.app.dispatcher');
uses('system.app.http.http_request');

/**
 * HTTP Dispatcher
 */
class HTTPDispatcher extends Dispatcher
{
	/**
	 * Constructor 
	 * 
	 * @param $path
	 * @param $controller_root
	 * @param $view_root
	 * @param $use_routes
	 * @param $force_routes
	 */
	public function __construct($path=null,$controller_root=null,$view_root=null,$use_routes=true,$force_routes=false)
	{
		if ($path==null)
		{
			$path = (isset ($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : @ getenv('PATH_INFO');
			$path = rtrim(strtolower($path), '/');
		}
		
		parent::__construct($path,$controller_root,$view_root,$use_routes,$force_routes);
	}

	/**
	 * @see sys/app/Dispatcher#build_request()
	 */
	public function build_request()
	{
		return new HTTPRequest($this->controller_root,$this->segments);
	}

	/**
	 * @see sys/app/Dispatcher#transform($data, $req_type)
	 */
	public function transform(&$data, $req_type=null)
	{
		$usesview="system.app.view";
		$viewclass='View';
		
		if ($req_type==null)
		{
			$req_type='html';
			
			try
			{
				$clients=Config::Get('clients');
				foreach($clients as $client)
				{
					switch($client->test)
					{
						case 'server':
							$array=&$_SERVER;
							break;
						case 'get':
							$array=&$_GET;
							break;
						case 'post':
							$array=&$_POST;
							break;
						case 'env':
							$array=&$_ENV;
							break;
					}
					
					if (isset($array[$client->key]))
					{
						if ($client->matches)
						{
							if (preg_match("#{$client->matches}#",$array[$client->key]))
							{
								$req_type=$client->type;
								
								if ($client->uses)
									$usesview=$client->uses;
								
								if ($client->class)
									$viewclass=$client->class;
									
								break;
							}
						}
						else
						{
							$req_type=$client->type;
							
							if ($client->uses)
								$usesview=$client->uses;
								
							if ($client->class)
								$viewclass=$client->class;
								
							break;
						}
					}
				}
			}
			catch (ConfigInvalidFormatException $fex)
			{
				throw $fex;
			}
			catch (ConfigException $ex)
			{
				
			}
		}
		
		if ($this->view)
			$view_name=$this->view;
		else
			$view_name=strtolower($this->controller_path.$this->controller.'/'.$this->action);
			
		$view_found=file_exists($this->view_root.$view_name.'.'.$req_type.EXT);
		
		if ((!$view_found) && ($req_type!='html') && (file_exists($this->view_root.$view_name.'.html'.EXT)))
		{
			$req_type='html';
			$view_found=true;
		}
			
		if (($view_found==false) && ($req_type!='ajax'))
		{
			trigger_error("Unable to find view '$view_name'.",E_USER_WARNING);
			return '';
		}
							
		if ($view_found)
		{	
			uses($usesview);
			$view=new $viewclass($this->view_root,$view_name.'.'.$req_type,$data['controller']);
			
			return $view->render($data);
		}		
	}
}