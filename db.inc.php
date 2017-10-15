<?php
class database {
	private $link;
	private $query;
	private $result;

	/**
	 * Desc:
	 ** Clear string from special characters
	 * Input:
	 ** [string] $string - input string
	 * Return:
	 ** [string] Clear text
	 */
	private function clearStr($string)
	{
		return mysqli_escape_string(htmlspecialchars($string));
	}

	/**
	 * Desc:
	 ** Convert conditions array to string
	 * Input:
	 ** [array-of-arrays] $conditions - conditions of selection
	 ** [array] $conditions[]:
	 *** [string] 0* - column name
	 *** [string] 1* - condition type
	 *** [string] 2* - condition value
	 * Return:
	 ** [string] converted conditions
	 */
	private function conditionsParser($conditions = [])
	{
		$tmp = [];
		foreach ($conditions as $condition) {
			$tmp[] = '`' . $condition[0] . '`' . $condition[1] . '"' . $condition[2] . '"';
		}


		return implode(", ", $tmp);
	}

	/**
	 * Desc:
	 ** Create database connection
	 * Input:
	 * [string] $dbhost - MySQLi server address
	 * [string] $dbuser - MySQLi username
	 * [string] $dbpass - MySQLi password
	 * [string] $dbname - MySQLi database name
	 * [string] $dbchar - MySQLi character encoding, default UTF-8
	 */
	public function __construct ($dbhost, $dbuser, $dbpass, $dbname, $dbchar = 'utf8')
	{
		$this->link = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
		mysqli_set_charset($this->link, $dbchar);
	}

	/**
	 * Desc:
	 * Execute mysql command
	 * Input:
	 ** [string] $query - mysql command
	 * Return:
	 ** [mysqli_result] Result of command
	 */
	public function query($query)
	{
		$this->result = mysqli_query($this->link, $query);
		return $this;
	}

	/**
	 * Desc:
	 ** Select info from table in database
	 * Input:
	 ** [string] $table - name of database table
	 ** [array] $what - columns to select
	 ** [array-of-arrays] $conditions - conditions of selection
	 ** [array] $conditions[]:
	 *** [string] 0* - column name
	 *** [string] 1* - condition type
	 *** [string] 2* - condition value
	 * Return:
	 ** [mysqli_result] Result of query
	 */
	public function select ($table, $what = [], $conditions = [])
	{
		$what = '`' . implode("`, `", $what) . '`';

		$conditions = $this->conditionsParser($conditions);
		$conditions = empty($conditions) ? "1" : $conditions;

		$query = "SELECT $what FROM `$table` WHERE $conditions";

		return $this->query($query);
	}

	/**
	 * Desc:
	 ** Insert info to table in database
	 * Input:
	 ** [string] $table - name of database table
	 ** [array] $keys - columns to insert
	 ** [array] $values - values of any column for each "line"
	 * Return
	 * [bool] result
	 */
	public function insert ($table, $keys = [], $values = [])
	{
		$keys = '`' . implode("`, `", $keys) . '`';

		$tmp = [];
		foreach($values as $values_line)
		{
			$tmp[] = '("' . implode('","', $values_line) . '")';
		}

		$values = implode(",", $tmp);
		unset($tmp);

		$query = "INSERT INTO `$table` ($keys) VALUES $values";

		$this->query($query);
		$this->result == false ? $this->result = false : $this->result = true;

		return $this;
	}

	/**
	 * Desc:
	 ** Delete from table
	 * Input:
	 ** [string] $table - name of database table
	 ** [array-of-arrays] $conditions - conditions of selection
	 ** [array] $conditions[]:
	 *** [string] 0* - column name
	 *** [string] 1* - condition type
	 *** [string] 2* - condition value
	 * Return
	 ** [bool] result
	 */
	public function delete($table, $conditions)
	{
		$conditions = $this->conditionsParser($conditions);
		$conditions = empty($conditions) ? "1" : $conditions;

		$query = "DELETE FROM $table WHERE $conditions";

		$this->query($query);
		$this->result === "false" ? $this->result = false : $this->result = true;

		return $this;
	}


	/***
	 * Desc:
	 ** create new table
	 * Input:
	 ** [string] $table - name of new database table
	 ** [array-of-array] $opts - columns with params
	 ** [array] $opts[] - one column
	 *** [string] 0* - column name
	 *** [string] 1* - datatype
	 *** [int] 2* - length (if present)
	 *** [bool] 3* - is null
	 *** [bool] 4* - primary key
	 *** [bool] 5* - unique key
	 *** [bool] 6* - index key
	 *** [bool] 7* - auto_increment (if primary key present)
	 ** [array] $additions
	 *** [string] engine* - MySQL engine (InnoDB, MyISAM)
	 *** [string] charset* - MySQL charset (utf8, latin1)
	 *** [string] collate* - MySQL Collation (utf8_bin, utf8_general_ci)
	 *** [string] ai* - Auto_Increment value (if present, default 1)
	 */
	public function createTable ($table, $opts = [], $additions = [])
	{
		$flagAI = false;
		$tmp = [];
		foreach ($opts as $opt) {
			// CREATE COLUMN
			$str = '`' . $opt[0] . '` ';
			$str .= ($opt[2] !== 0) ? "$opt[1] ($opt[2]) " : $opt[1] . ' ';
			$str .= $opt[3] ? '' : 'NOT ' . 'NULL ';
			$str .= (($opt[4] || $opt[5]) && $opt[7])  ? 'AUTO_INCREMENT' : '';
			$tmp[] = $str;

			if ($opt[4])
				$tmp[] = 'PRIMARY KEY (`' . $opt[0] . '`)';
			if ($opt[5])
				$tmp[] = 'UNIQUE KEY (`' . $opt[0] . '`)';
			if ($opt[6])
				$tmp[] = 'KEY (`' . $opt[0] . '`)';
			if (($opt[4] || $opt[5]) && $opt[7])
				$flagAI = true;
		}

		$columns = implode(",", $tmp);
		unset($tmp);

		$tmp = [];
		if(isset($additions['engine']) && !empty($additions['engine']))
			$tmp[] = 'ENGINE=' . $additions['engine'];
		if(isset($additions['collate']) && !empty($additions['collate']))
			$tmp[] = 'COLLATE=' . $additions['collate'];
		if ($flagAI)
			if(isset($additions['ai']) && !empty(intval($additions['ai'])))
				$tmp[] = 'AUTO_INCREMENT=' . intval($additions['ai']);
			else
				$tmp[] = 'AUTO_INCREMENT=1';
		$additions = implode(' ', $tmp);
		unset($tmp);

		$query = "CREATE TABLE `$table` (" . $columns . ") " . $additions . ';';

		return $this->query($query);
	}
	public function dropTable ($table)
	{
		$query = 'DROP TABLE ' . $table . ';';

		return $this->query($query);
	}

	public function changeDb($dbname)
	{
		$query = 'USE `' . $this->clearStr($dbname) . '`;';

		return $this->query($query);
	}

	/**
	 * Desc:
	 ** Parse result and get data..
	 * Input:
	 ** Not specified
	 * Result:
	 ** [array-of-arrays] - mysqli result in array
	 */
	public function all()
	{
		$tmp = $this->result;
		$array = [];
		while($td = mysqli_fetch_assoc($tmp))
		{
			array_push($array, $td);
		}

		return $array;
	}
	/**
	 * Desc:
	 ** Parse result and get data..
	 * Input:
	 ** Not specified
	 * Result:
	 ** [array] - first line of mysql res
	 */
	public function one()
	{
		$tmp = $this->result;
		return mysqli_fetch_assoc($tmp);
	}
}

?>