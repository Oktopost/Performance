<?php
namespace Performance;


use Traitor\TSingletonLike;


class Watch implements IWatch
{
	use TSingletonLike;
	
	
	private $data;
	private $loops		= [];
	private $groupsMap	= [];
	private $startTime	= null;
	
	
	private function getMySQLDateTime(float $u): string 
	{
		return date('Y-m-d H:i:s.', $u) . round(($u - floor($u)) * 10000);
	}
	
	private function appendRecord(string $group, \stdClass $object): void
	{
		if (isset($this->data->{$group}))
			$this->data->{$group}[] = $object;
		else
			$this->data->{$group} = [$object];
	}
	
	private function getStartObject(?string $key): \stdClass
	{
		$object = new \stdClass();
		$startTime = $this->getTime();
		
		if ($key)
			$object->key = $key;
		
		$object->readable_start_time	= $this->getMySQLDateTime($startTime);
		$object->unix_start_time		= $startTime;
		$object->start_time				= $this->startTime ? round($startTime - $this->startTime, 4) : null;
		$object->readable_end_time		= null;
		$object->unix_end_time			= null;
		$object->end_time				= null;
		$object->run_time				= null;
		
		return $object;
	}
	
	private function setEndTime(\stdClass $object): void
	{
		$endTime = $this->getTime();
		
		$object->readable_end_time	= $this->getMySQLDateTime($endTime);
		$object->unix_end_time		= $endTime;
		$object->end_time			= $this->startTime ? round($endTime - $this->startTime, 4) : null;
		$object->run_time			= $endTime - $object->unix_start_time;
	}
	
	private function addTags(\stdClass $object, ?array $tags = null): void
	{
		if (!$tags) return;
		
		$object->tags = isset($object->tags) ?
			array_merge($object->tags, $tags) : 
			$tags;
	}
	
	
	public function __construct()
	{
		$this->data = new \stdClass();
	}
	
	
	public function reset(): void
	{
		$this->data			= new \stdClass();
		$this->loops		= [];
		$this->groupsMap	= [];
		$this->startTime	= null;
	}
	
	/**
	 * @param int $round
	 * @return float
	 */
	public function getRuntime(int $round = 4): float
	{
		return $this->getTime($round, $this->startTime);
	}
	
	/**
	 * @param int $round
	 * @param int|float|null $since
	 * @return float
	 */
	public function getTime(int $round = 4, $since = null): float
	{
		list($ms, $sec) = explode(' ', microtime());
		
		if ($since)
		{
			$sec = (float)$sec - $since;
		}
		
		return round($sec + $ms, $round);
	}
	
	/**
	 * Should be called only once to initialize startup settings.
	 * However this is not required. Used to get more data about the process in logs.
	 */
	public function init(): void
	{
		$this->startTime = $this->getTime();
		
		$this->data->init = (object)[
			'version'				=> phpversion(),
			'readable_start_time'	=> $this->getMySQLDateTime($this->startTime),
			'start_time'			=> $this->startTime,
			'start_memory'			=> memory_get_usage()
		];
	}
	
	/**
	 * Should be called last. Store additional data about the end of the process.
	 */
	public function finalize(): void
	{
		$endTime = $this->getTime();
		$this->data->init = $this->data->init ?? new \stdClass();
		
		$object = $this->data->init;
		
		$object->readble_end_time	= $this->getMySQLDateTime($endTime);
		$object->end_time			= $endTime;
		
		if (isset($object->start_time))
		{
			$object->run_time = round($endTime - $object->start_time, 4);
		}
		
		$object->end_memory	= memory_get_usage();
		$object->max_memory	= memory_get_peak_usage();
	}
	
	/**
	 * @param string $key
	 * @param mixed $value scalar value
	 */
	public function tag(string $key, $value): void
	{
		$this->data->tags = $this->data->tags ?? (object)[];
		$this->data->tags->$key = $value;
	}
	
	/**
	 * @param string $key
	 * @param array $value scalar value
	 */
	public function tagAppend(string $key, ...$value): void
	{
		$this->data->tags = $this->data->tags ?? (object)[];
		
		if (count($value) == 1 && is_array($value[0]))
		{
			$value = $value[0];
		}
		
		if (isset($this->data->tags->$key))
		{
			if (is_array($this->data->tags->$key))
			{
				$this->data->tags->$key = array_merge($this->data->tags->$key, $value);
			}
			else
			{
				$this->data->tags->$key = array_merge([$this->data->tags->$key], $value);
			}
		}
		else
		{
			$this->data->tags->$key = $value;
		}
	}
	
	/**
	 * Store the start time for the event identified by group/key or group if key is not set.
	 * @param string $group
	 * @param string|array|null $key If array, $keys will be treated as tags.
	 * @param array|null $tags Optional tags to add to the event.
	 */
	public function start(string $group, $key = null, ?array $tags = null): void
	{
		$object = $this->getStartObject($key);
		$this->appendRecord($group, $object);
		
		if (!$key)
			$key = $group;
		
		$this->addTags($object, $tags);
		
		if (isset($this->groupsMap[$group]))
		{
			$this->groupsMap[$group][$key] = $object;
		}
		else
		{
			$this->groupsMap[$group] = [ $key => $object ];
		}
	}
	
	/**
	 * Store the end time for the event identified by group/key or group if key is not set.
	 * @param string $group
	 * @param string|array|null $key If array, $keys will be treated as tags.
	 * @param array|null $tags Optional tags to add to the event.
	 */
	public function stop(string $group, $key = null, ?array $tags = null): void
	{ 
		$hasKey	= $key !== null;
		$key	= $key ?: $group;
		
		if (isset($this->groupsMap[$group][$key]))
		{
			$data = $this->groupsMap[$group][$key];
			$this->setEndTime($data);
			
			unset($this->groupsMap[$group][$key]);
		}
		else
		{
			$endTime = $this->getTime();
			
			$data = new \stdClass();
			
			if ($hasKey)
				$data->key = $key;
			
			$data->readable_start_time	= null;
			$data->unix_start_time		= null;
			$data->start_time			= null;
			$data->readable_end_time	= $this->getMySQLDateTime($endTime);
			$data->unix_end_time		= $endTime;
			$data->end_time				= $this->startTime ? round($endTime - $this->startTime, 4) : null;
			$data->run_time				= null;
			
			$this->data->{$group}[] = $data;
		}
		
		$this->addTags($data, $tags);
	}
	
	/**
	 * End the previous loop (if exists) and start a new one.
	 * @param string $group
	 * @param array|null $tags Optional tags to add the the new iteration.
	 */
	public function loop(string $group, ?array $tags = null): void
	{
		$this->endLoop($group);
		
		$object = $this->getStartObject(null);
		
		$this->addTags($object, $tags);
		$this->appendRecord($group, $object);
		
		$this->loops[$group] = $object;
	}
	
	/**
	 * End the last loop iteration (if exists).
	 * @param string $group
	 */
	public function endLoop(string $group): void
	{
		if (!isset($this->loops[$group]))
			return;
		
		$this->setEndTime($this->loops[$group]);
		unset($this->loops[$group]);
	}
	
	/**
	 * Detect a single event using unix timestamp. If init was called, time since init will be recorded.
	 * @param string $group
	 * @param array|null $tags Optional tags to add to event.
	 */
	public function detect(string $group, ?array $tags = null): void
	{
		$time = $this->getTime();
		$data = new \stdClass();
		
		$data->readable_start_time	= $this->getMySQLDateTime($time);
		$data->unix_start_time		= $time;
		
		if ($this->startTime)
		{
			$data->start_time = $time - $this->startTime;
		}
		
		$this->addTags($data, $tags);
		$this->appendRecord($group, $data);
	}
	
	/**
	 * @return \stdClass
	 */
	public function getRecords(): \stdClass
	{
		return $this->data;
	}
	
	/**
	 * @param bool $prettyPrint
	 * @return string
	 */
	public function getRecordsAsJson(bool $prettyPrint = false): string
	{
		return json_encode($this->data, $prettyPrint ? JSON_PRETTY_PRINT : 0);
	}
}