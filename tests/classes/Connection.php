<?php

namespace Mesour\Sources\Tests;

use Nette;

class Connection extends \PDO
{

	private $dsn;

	public function __construct($dsn, $username, $passwd, $options = [])
	{
		parent::__construct($dsn, $username, $passwd, $options = []);
		$this->dsn = $dsn;
	}

	public function getDsn()
	{
		return $this->dsn;
	}

	public function disconnect()
	{
		$this->db_conn = null;
	}

	public function __destruct()
	{
		$this->disconnect();
	}

}
