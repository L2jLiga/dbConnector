<?php
require "db.inc.php";

$db = new database("localhost", "root", "123123", "testdb", "utf8");

$res = $db->
		dropTable("kurve")->
		createTable("kurve",
		[
			['id', 'int', 0, false, true, false, false, true],
			['username', 'varchar', 30, false, false, true, false, false],
			['name', 'varchar',60, false,false,false,false,false],
		],
		[
			'collate' => "utf8_general_ci",
			'ai' => 3
		])->
		insert("kurve", ['username', 'name'], [
			['L2jLiga', "АВЧалкин"],
			['AAZubkova', "ААЗубкова"],
		])->
		select("kurve", ['id','username','name'])->
		one();

var_dump($res);
?>
