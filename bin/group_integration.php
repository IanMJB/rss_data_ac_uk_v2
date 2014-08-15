<?php

class group_integration
{

	/********************************************************
	* Called on a database with institutions already	*
	* populated. Fed in some group data (array with tuples	*
	* Name/Groups/URL) it will update any elements which	*
	* match the URL provided - inserting their name and	*
	* groups they are a member of.				*
	********************************************************/
	function integrate_data($db, $group_data)
	{
		$update_stmt	= NULL;
		foreach($group_data as $inst)
		{
			$update_array	= array_slice($inst, 0, 2);
			$where_column	= 'inst_pdomain';

			if(!$update_stmt)
			{
				$update_stmt	= db_utilities::create_update($db, 'institutions', $update_array, $where_column);
			}
			$update_stmt->execute(array_values($inst));

			$affected_rows	= $update_stmt->rowCount();
			if($affected_rows === 0)
			{
				echo 'Meta-data unchanged, ignored: ', $inst['inst_pdomain'], "\n";
			}
			elseif($affected_rows === 1)
			{
				echo 'Meta-data changed/new, updated: ', $inst['inst_pdomain'], "\n";
			}
			else
			{
				echo 'ERROR ADDING META-DATA: $inst_pdomain = ', $inst['inst_pdomain'], ', $affected_rows = ', $affected_rows, "\n";
			}
		}
	}

	/********************************************************
	* Retrieves tuples of Name/Groups/URL of universities	*
	* from data.ac.uk (as default).				*
	* Can accept another URL, though this is not		*
	* recommended without modifying the function as it is	*
	* built around the expected download structure.		*
	********************************************************/
	function get_university_groups($data_url = 'http://learning-provider.data.ac.uk/data/learning-providers-plus.csv')
	{
		$source_data	= file_get_contents($data_url);
		$lines		= explode(PHP_EOL, $source_data);

		#Remove first line (column headers).
		array_shift($lines);

		$group_data	= array();
		foreach($lines as $line)
		{
			#If the URL is set.
			if(isset($line[9]))
			{
				$line	= str_getcsv($line);
				#Empty groups results in '' being inserted into the database rather than left as NULL.
				if($line[12] === '')
				{
					$line[12] = NULL;
				}
				#Name/Groups/URL.
				$group_data[]	= array(
							'inst_name'	=> $line[1],
							'inst_groups'	=> $line[12],
							'inst_pdomain'	=> $line[9]
						       );
			}
		}
		return $group_data;
	}

}
