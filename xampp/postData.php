<?php

    function array_has_only_keys($array,$keys){return array_diff(array_keys($array),$keys)==array_diff($keys,array_keys($array));}
	if(array_has_only_keys($_POST,['module','day','month','year','hour','minute','temperature','humidity','pressure']))
	{
		try
		{
			$db='measurementsdb';
			$host='localhost';
			$user='root';
			$pass='';
			$link=mysqli_connect($host,$user,$pass);
		
			mysqli_select_db($link,$db);
			$query="INSERT INTO {$_POST['module']} VALUES({$_POST['day']},{$_POST['month']},{$_POST['year']},{$_POST['hour']},{$_POST['minute']},{$_POST['temperature']},{$_POST['humidity']},{$_POST['pressure']})";
			mysqli_query($link,$query);
		}
		finally
		{
			if($link) mysqli_close($link);
		}
	}
?>