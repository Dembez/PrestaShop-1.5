<?php
/*
* 2007-2012 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2012 PrestaShop SA
*  @version  Release: $Revision$
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

abstract class Db extends DbCore
{
	/**
	 * Add SQL_NO_CACHE in SELECT queries
	 * 
	 * @var unknown_type
	 */
	public $disableCache = true;

	/**
	 * Total of queries
	 *
	 * @var int
	 */
	public $count = 0;

	/**
	 * List of queries
	 *
	 * @var array
	 */
	public $queries = array();
	
	/**
	 * List of uniq queries (replace numbers by XX)
	 * 
	 * @var array
	 */
	public $uniqQueries = array();
	
	/**
	 * List of tables
	 *
	 * @var array
	 */
	public $tables = array();

	/**
	 * Execute the query and log some informations
	 *
	 * @see DbCore::query()
	 */
	public function query($sql)
	{
		$explain = false;
		if (preg_match('/^\s*explain\s+/i', $sql))
			$explain = true;
			
		if (!$explain)
		{
			$uniqSql = preg_replace('/[0-9]+/', '<span style="color:blue">XX</span>', $sql);
			if (!isset($this->uniqQueries[$uniqSql]))
				$this->uniqQueries[$uniqSql] = 0;
			$this->uniqQueries[$uniqSql]++;

			// No cache for query
			if ($this->disableCache)
				$sql = preg_replace('/^\s*select\s+/i', 'SELECT SQL_NO_CACHE ', trim($sql));

			// Get tables in quer
			preg_match_all('/(from|join)\s+`?'._DB_PREFIX_.'([a-z0-9_-]+)/ui', $sql, $matches);
			foreach ($matches[2] as $table)
			{
				if (!isset($this->tables[$table]))
					$this->tables[$table] = 0;
				$this->tables[$table]++;
			}

			// Execute query
			$start = microtime(true);
		}
		
		$result = parent::query($sql);
		
		if (!$explain)
		{
			$end = microtime(true);
			
			// Save details
			$timeSpent = $end - $start;
			$trace = debug_backtrace(false);
			while (preg_match('@[/\\\\]classes[/\\\\]db[/\\\\]@i', $trace[0]['file']))
				array_shift($trace);
			
			$this->queries[] = array(
				'query' => $sql,
				'time' => $timeSpent,
				'file' => $trace[0]['file'],
				'line' => $trace[0]['line'],
			);
		}
		
		return $result;
	}
}