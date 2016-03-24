<?php

namespace Mesour\Sources\Tests;

use Nette;

class DatabaseFactory extends Nette\Object
{

	private $host;

	private $username;

	private $password;

	private $prefix;

	private $databaseName;

	public function __construct($host, $username, $password, $prefix)
	{
		$this->host = $host;
		$this->username = $username;
		$this->password = $password;
		$this->prefix = $prefix;
	}

	/**
	 * @return Connection
	 */
	public function create()
	{
		$connection = new Connection($this->getDsn(), $this->username, $this->password);

		$this->databaseName = $this->getRandomDbName();

		$connection->query('CREATE DATABASE ' . $this->databaseName . ' DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci');

		$connection->disconnect();

		$dbConnection = new Connection($this->getDsnWithDatabase($this->databaseName), $this->username, $this->password);

		$dbConnection->query(file_get_contents(__DIR__ . '/../data/scheme.sql'));

		return $dbConnection;
	}

	public function destroy(Connection $connection)
	{
		$exploded = explode('dbname=', $connection->getDsn());
		$dbName = end($exploded);

		$connection->query('DROP DATABASE ' . $dbName);
		$connection->disconnect();
	}

	private function getDsn()
	{
		return 'mysql:host=' . $this->host;
	}

	public function getUserName()
	{
		return $this->username;
	}

	public function getPassword()
	{
		return $this->password;
	}

	public function getDatabaseName()
	{
		return $this->databaseName;
	}

	private function getDsnWithDatabase($dbName)
	{
		return $this->getDsn() . ';dbname=' . $dbName;
	}

	private function getRandomDbName()
	{
		return $this->prefix . Nette\Utils\Random::generate();
	}

}
