<?php
uses('system.data.database');
uses('system.app.view');

class ModelIndexController extends Controller
{
	/**
	 * Generates a model for a given schema
	 * 
	 * @usage ./metal model/create --database=default --schema=public --table=user --related
	 * @switch database The name of the database in the conf file
	 * @switch schema The name of the schema, default is 'public'
	 * @switch table The name of the table to generate the model for
	 * @switch related Boolean determines if any related models should be generated.
	 */
	public function create()
	{
		$db=Database::Get($this->request->input->database);
		$schemaName=($this->request->input->schema) ? $this->request->input->schema : 'public'; 
		$schema=$db->table($schemaName, $this->request->input->table, $this->request->input->related);

		$rel_classname='';
		$names=explode('_',$this->request->input->table);
		foreach($names as $n)
				$rel_classname.=ucfirst($n);
		
		$view=new View('index.html',$this,PATH_SYS.'shell/view/model/');
		$model=$view->render(array(
			'classname'=>$rel_classname,
			'schema' => $schema,
			'database' => $this->request->input->database
		));

		$path=PATH_APP.'model/'.$schemaName;
		if (!file_exists($path))
			mkdir($path);
		file_put_contents($path.'/'.$schema->tablename.EXT, $model);
		
		foreach($schema->related as $table)
		{
			$rel_classname='';
			$names=explode('_',$table->tablename);
			foreach($names as $n)
				$rel_classname.=ucfirst($n);
				
			$model=$view->render(array(
				'classname'=>$rel_classname,
				'schema' => $table,
				'database' => $this->request->input->database
			));
			
			$path=PATH_APP.'model/'.$table->schema;
			if (!file_exists($path))
				mkdir($path);

			file_put_contents($path.'/'.$table->tablename.EXT, $model);
		}
		die;
	}
}