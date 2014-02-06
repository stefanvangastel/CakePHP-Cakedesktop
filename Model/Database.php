<?php
class Database extends AppModel {

	public $useTable = false;

	/* backup the db OR just a table */
	public function createsql()
	{
		
		App::uses('ConnectionManager', 'Model');
		$dataSource = ConnectionManager::getDataSource('default');
		$host = $dataSource->config['host'];
		$user = $dataSource->config['login'];
		$pass = $dataSource->config['password'];
		$name = $dataSource->config['database'];
		$tables = '*';

		$link = mysql_connect($host,$user,$pass);
		mysql_select_db($name,$link);
		
		//get all of the tables
		if($tables == '*')
		{
			$tables = array();
			$result = mysql_query('SHOW TABLES');
			while($row = mysql_fetch_row($result))
			{
				$tables[] = $row[0];
			}
		}
		else
		{
			$tables = is_array($tables) ? $tables : explode(',',$tables);
		}
		
		$return = '';

		//cycle through
		foreach($tables as $table)
		{
			$result = mysql_query('SELECT * FROM '.$table);
			$num_fields = mysql_num_fields($result);
			
			//$return.= 'DROP TABLE '.$table.';';
			$row2 = mysql_fetch_row(mysql_query('SHOW CREATE TABLE '.$table));
			$return.= "\n\n".$row2[1].";\n\n";
			
			for ($i = 0; $i < $num_fields; $i++) 
			{
				while($row = mysql_fetch_row($result))
				{
					$return.= 'INSERT INTO '.$table.' VALUES(';
					for($j=0; $j<$num_fields; $j++) 
					{
						$row[$j] = addslashes($row[$j]);
						$row[$j] = ereg_replace("\n","\\n",$row[$j]);
						if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
						if ($j<($num_fields-1)) { $return.= ','; }
					}
					$return.= ");\n";
				}
			}
			$return.="\n\n\n";
		}
		
		//save file
		/*$handle = fopen('db-backup-'.time().'-'.(md5(implode(',',$tables))).'.sql','w+');
		fwrite($handle,$return);
		fclose($handle);*/

		return $return;
	}

	/**
	 * Function to convert mysql generated SQL to sqlite compatible sql
	 * @return [type] [description]
	 */
	public function converttosqlite($sql = ''){

		//Replace stuff to make it sqlite compatible:
		$sql = preg_replace('/\)\sENGINE.*\;/', ');', $sql);

		$sql = preg_replace('/COLLATE\s[a-zA-Z0-9_]+/', '', $sql);

		$sql = str_replace('int(11) NOT NULL AUTO_INCREMENT', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);

		$sql = preg_replace('/,\n\s+PRIMARY\sKEY\s\(\`id\`\)/s', '', $sql);

		$sql = str_replace("`", "", $sql);

		return $sql;
	}

}
?>