<?php
namespace Performance;


interface IWatch
{
	/**
	 * Should be called only once to initialize startup settings. 
	 * However this is not required. Used to get more data about the process in logs.
	 */
	public function init(): void;
	
	/**
	 * Should be called last. Store additional data about the end of the process.
	 */
	public function finalize(): void;
	
	/**
	 * Reset the timer.
	 */
	public function reset(): void;
	
	
	/**
	 * @param string $key
	 * @param mixed $value scalar value
	 */
	public function tag(string $key, $value): void;
	
	/**
	 * @param string $key
	 * @param array $value scalar value
	 */
	public function tagAppend(string $key, ...$value): void;
	
	
	/**
	 * @param int $round
	 * @param int|float|null $since
	 * @return float
	 */
	public function getTime(int $round = 4, $since = null): float;
	
	
	/**
	 * Store the end time for the event identified by group/key or group if key is not set.
	 * @param string $group
	 * @param string|array|null $key If array, $keys will be treated as tags.
	 * @param array|null $tags Optional tags to add to the event. 
	 */
	public function start(string $group, $key = null, ?array $tags = null): void;
	
	/**
	 * Store the end time for the event identified by group/key or group if key is not set.
	 * @param string $group
	 * @param string|array|null $key If array, $keys will be treated as tags.
	 * @param array|null $tags Optional tags to add to the event. 
	 */
	public function stop(string $group, $key = null, ?array $tags = null): void;
	
	/**
	 * End the previous loop (if exists) and start a new one.
	 * @param string $group
	 * @param array|null $tags Optional tags to add the the new iteration.
	 */
	public function loop(string $group, ?array $tags = null): void;
	
	/**
	 * End the last loop iteration (if exists).
	 * @param string $group
	 */
	public function endLoop(string $group): void;
	
	/**
	 * Detect a single event using unix timestamp. If init was called, time since init will be recorded.
	 * @param string $group
	 * @param array|null $tags Optional tags to add to event.
	 */
	public function detect(string $group, ?array $tags = null): void;
	
	/**
	 * @return \stdClass
	 */
	public function getRecords(): \stdClass;
	
	/**
	 * @param bool $prettyPrint
	 * @return string
	 */
	public function getRecordsAsJson(bool $prettyPrint = false): string;
}