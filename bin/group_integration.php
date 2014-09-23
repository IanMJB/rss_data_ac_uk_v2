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
				echo 'Meta-data unchanged/institution not in database, ignored: ', $inst['inst_pdomain'], "\n";
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
		#Column ordering changes, so built up on the assumption that the titles do not.
		$inst_name_key          = 'PROVIDER_NAME';
		$inst_groups_key        = 'GROUPS';
		$inst_url_key           = 'WEBSITE_URL';

		$source_data            = file_get_contents($data_url);
		$lines                  = explode(PHP_EOL, $source_data);

		$column_titles          = array_shift($lines);
		$column_titles          = str_getcsv($column_titles);

		$column_locations       = array_flip($column_titles);

		$group_data             = array();
		foreach($lines as $line)
		{
			$line           = str_getcsv($line);

			#Only populate if the URL is set.
			if(isset($line[$column_locations[$inst_url_key]]))
			{
				#Empty groups results in '' being inserted into the database rather than NULL, this fixes that.
				if($line[$column_locations[$inst_name_key]] === '')
				{
					$line[$column_locations[$inst_name_key]]        = NULL;
				}
				if($line[$column_locations[$inst_groups_key]] === '')
				{
					$line[$column_locations[$inst_groups_key]]      = NULL;
				}
				#Name/Groups/URL.
				$group_data[]   = array(
							'inst_name'     => $line[$column_locations[$inst_name_key]],
							'inst_groups'   => $line[$column_locations[$inst_groups_key]],
							'inst_pdomain'  => $line[$column_locations[$inst_url_key]]
						       );
			}
		}
		return $group_data;
	}
}
