<?php
namespace Performance;


use PHPUnit\Framework\TestCase;


class WatchTest extends TestCase
{
	public function test_getTime_CurrentTimeReturned()
	{
		$subject = new Watch();
		
		$before = microtime(true);
		$result = $subject->getTime();
		
		self::assertEquals($before, $result, '', 0.001);
	}
	
	public function test_getTime_TimeRounded()
	{
		$subject = new Watch();
		
		$result1 = $subject->getTime(1);
		usleep(250);
		$result2 = $subject->getTime(1);
		
		self::assertEquals(0, ($result1 * 100.0) % 10);
		self::assertEquals(0, ($result2 * 100.0) % 10);
	}
	
	public function test_getTime_GetTimeSince()
	{
		$subject = new Watch();
		
		$before = microtime(true);
		$before -= 10.0;
		
		$result = $subject->getTime(4, $before);
		
		self::assertEquals(10, $result, '', 0.01);
	}
	
	
	public function test_getRuntime_WatchNotInitialized_ReturnUnixTime()
	{
		$subject = new Watch();
		
		$before = microtime(true);
		$result = $subject->getRuntime();
		
		self::assertEquals($before, $result, '', 0.001);
	}
	
	public function test_getRuntime_WatchInitialized_ReturnTimeSinceStart()
	{
		$subject = new Watch();
		
		$subject->init();
		
		usleep(250);
		
		$result = $subject->getRuntime();
		
		self::assertTrue(1.0 > $result && $result > 0.0);
	}
	
	public function test_getRuntime_TimeRounded()
	{
		$subject = new Watch();
		$subject->init();
		
		$result1 = $subject->getRuntime(1);
		usleep(250);
		$result2 = $subject->getRuntime(1);
		
		self::assertEquals(0, ($result1 * 100.0) % 10);
		self::assertEquals(0, ($result2 * 100.0) % 10);
	}
	
	
	public function test_init_Sanity()
	{
		$subject = new Watch();
		
		$now = microtime(true);
		
		$subject->init();
		
		$records = $subject->getRecords()->init;
		
		self::assertEquals(phpversion(), 	$records->version);
		self::assertEquals($now,			$records->start_time, '', 0.01);
		self::assertEquals($now,			strtotime($records->readable_start_time), '', 1);
		
		self::assertTrue(0 <= $records->start_memory);
	}
	
	
	public function test_finalize_InitNotCalled_InitDataCreatedWithEmptyValues()
	{
		$subject = new Watch();
		
		$subject->finalize();
		$result = $subject->getRecords();
		
		self::assertInstanceOf(\stdClass::class, $result->init);
		
	}
	
	public function test_finalize_InitCalled_InitNotReset()
	{
		$subject = new Watch();
		
		$subject->init();
		$original = $subject->getRecords()->init;
		
		$subject->finalize();
		$result = $subject->getRecords()->init;
		
		self::assertSame($original, $result);
	}
	
	public function test_finalize_InitNotCalled_RunTimeNotSet()
	{
		$subject = new Watch();
		
		$subject->finalize();
		$result = $subject->getRecords();
		
		self::assertNull($result->init->run_time ?? null);
	}
	
	public function test_finalize_InitCalled_RunTimeSet()
	{
		$subject = new Watch();
		
		
		$subject->init();
		
		usleep(100);
		
		$subject->finalize();
		$result = $subject->getRecords();
		
		
		self::assertTrue(1.0 > $result->init->run_time && $result->init->run_time > 0.0);
	}
	
	public function test_finalize_ValuesSet()
	{
		$subject = new Watch();
		
		$now = microtime(true);
		
		$subject->finalize();
		
		$records = $subject->getRecords()->init;
		
		self::assertEquals($now, $records->end_time, '', 0.01);
		self::assertEquals($now, strtotime($records->readble_end_time), '', 1);
		
		self::assertTrue(0 <= $records->end_memory);
		self::assertTrue(0 <= $records->max_memory);
	}
	
	
	public function test_tag_AddForFirstTime()
	{
		$subject = new Watch();
		
		$subject->tag('a', 123);
		
		self::assertEquals((object)['a' => 123], $subject->getRecords()->tags);
	}
	
	public function test_tag_AddMoreThenOnce()
	{
		$subject = new Watch();
		
		$subject->tag('a', 123);
		$subject->tag('b', 'a');
		
		self::assertEquals((object)['a' => 123, 'b' => 'a'], $subject->getRecords()->tags);
	}
	
	public function test_tag_OverrideValue()
	{
		$subject = new Watch();
		
		$subject->tag('a', 123);
		$subject->tag('a', 'a');
		
		self::assertEquals((object)['a' => 'a'], $subject->getRecords()->tags);
	}
	
	
	public function test_tagAppend_AddOnceScalar_ValueSetAsArray()
	{
		$subject = new Watch();
		
		$subject->tagAppend('a', 'a');
		
		self::assertEquals((object)['a' => ['a']], $subject->getRecords()->tags);
	}
	
	public function test_tagAppend_AddOnceArray_ArrayAppended()
	{
		$subject = new Watch();
		
		$subject->tagAppend('a', ['a']);
		
		self::assertEquals((object)['a' => ['a']], $subject->getRecords()->tags);
	}
	
	public function test_tagAppend_AppendToScalar_ValueConvertedToArray()
	{
		$subject = new Watch();
		
		$subject->tag('a', 'a');
		$subject->tagAppend('a', 'b');
		
		self::assertEquals((object)['a' => ['a', 'b']], $subject->getRecords()->tags);
	}
	
	public function test_tagAppend_AppendScalarToArray_ValueAppended()
	{
		$subject = new Watch();
		
		$subject->tag('a', ['a']);
		$subject->tagAppend('a', 'b');
		
		self::assertEquals((object)['a' => ['a', 'b']], $subject->getRecords()->tags);
	}
	
	public function test_tagAppend_AppendArrayToArray_ValueAppended()
	{
		$subject = new Watch();
		
		$subject->tag('a', ['a']);
		$subject->tagAppend('a', ['b']);
		
		self::assertEquals((object)['a' => ['a', 'b']], $subject->getRecords()->tags);
	}
	
	
	public function test_start_CallFirstTime_GroupCreated()
	{
		$subject = new Watch();
		
		$subject->start('a');
		
		self::assertTrue(is_array($subject->getRecords()->a));
		self::assertCount(1, $subject->getRecords()->a);
	}
	
	public function test_start_AddMoreThenOnce_GroupCreated()
	{
		$subject = new Watch();
		
		$subject->start('a');
		$subject->start('a');
		$subject->start('a');
		
		self::assertTrue(is_array($subject->getRecords()->a));
		self::assertCount(3, $subject->getRecords()->a);
	}
	
	public function test_start_AddDifferentGroup_GroupCreated()
	{
		$subject = new Watch();
		
		$subject->start('a');
		$subject->start('b');
		
		self::assertCount(1, $subject->getRecords()->a);
		self::assertCount(1, $subject->getRecords()->b);
	}
	
	public function test_start_KeyNotUsed_KeyNotset()
	{
		$subject = new Watch();
		
		$subject->start('a');
		
		self::assertFalse(isset($subject->getRecords()->a[0]->key));
	}
	
	public function test_start_KeyUsed_GroupCreatedWithKey()
	{
		$subject = new Watch();
		
		$subject->start('a', 'b');
		
		self::assertEquals('b', $subject->getRecords()->a[0]->key);
	}
	
	public function test_start_PassTags_TagsAppended()
	{
		$subject = new Watch();
		
		$subject->start('a', 'b', ['c' => 123]);
		
		self::assertEquals(['c' => 123], $subject->getRecords()->a[0]->tags);
	}
	
	public function test_start_DataSet()
	{
		$subject = new Watch();
		
		$now = microtime(true);
		
		$subject->start('a');
		
		$records = $subject->getRecords()->a[0];
		
		self::assertEquals($now, $records->unix_start_time, '', 0.01);
		self::assertEquals($now, strtotime($records->readable_start_time), '', 1);
		self::assertNull($records->start_time);
	}
	
	public function test_start_ObjectWasInitialized_RelativeStartTimeSet()
	{
		$subject = new Watch();
		
		$subject->init();
		usleep(100);
		
		$subject->start('a');
		
		$records = $subject->getRecords()->a[0];
		
		self::assertTrue(1.0 > $records->start_time && $records->start_time > 0.0);
	}
	
	
	public function test_stop_StartNotCalled_EndTimeNotSet()
	{
		$subject = new Watch();
		
		$subject->stop('a');
		
		$records = $subject->getRecords()->a[0];
		
		self::assertNull($records->end_time);
	}
	
	public function test_stop_StartCalledMoreThenOnce_StopAppliedOnLastObject()
	{
		$subject = new Watch();
		
		$subject->start('a');
		$subject->start('a');
		$subject->stop('a');
		
		self::assertNull($subject->getRecords()->a[0]->unix_end_time);
		self::assertNotNull($subject->getRecords()->a[1]->unix_end_time);
	}
	
	public function test_stop_StartCalledForDifferentGroup_NewObjectUsed()
	{
		$subject = new Watch();
		
		$subject->start('b');
		$subject->stop('a');
		
		self::assertNull($subject->getRecords()->b[0]->unix_end_time);
		self::assertNotNull($subject->getRecords()->a[0]->unix_end_time);
	}
	
	public function test_stop_StartCalled_SameObjectUsed()
	{
		$subject = new Watch();
		
		$subject->start('a');
		$subject->stop('a');
		
		self::assertCount(1, $subject->getRecords()->a);
	}
	
	public function test_stop_StartNotCalled_ObjectCreated()
	{
		$subject = new Watch();
		
		$subject->stop('a');
		
		self::assertCount(1, $subject->getRecords()->a);
	}
	
	public function test_stop_StartCalledWithSameKey_StopAppliedOnObject()
	{
		$subject = new Watch();
		
		$subject->start('a', 'c');
		$subject->stop('a', 'c');
		
		self::assertNotNull($subject->getRecords()->a[0]->unix_end_time);
	}
	
	public function test_stop_StartCalledWithNumberOfKeys_StopAppliedOnSameKey()
	{
		$subject = new Watch();
		
		$subject->start('a', 'b');
		$subject->start('a', 'c');
		$subject->stop('a', 'c');
		
		
		self::assertNull($subject->getRecords()->a[0]->unix_end_time);
		
		self::assertEquals('c', $subject->getRecords()->a[1]->key);
		self::assertNotNull($subject->getRecords()->a[1]->unix_end_time);
	}
	
	public function test_stop_StartCalledWithSameKeyNumberOfTimes_StopAppliedOnLastObject()
	{
		$subject = new Watch();
		
		$subject->start('a', 'b');
		$subject->start('a', 'b');
		$subject->stop('a', 'b');
		
		self::assertNull($subject->getRecords()->a[0]->unix_end_time);
		self::assertNotNull($subject->getRecords()->a[1]->unix_end_time);
	}
	
	public function test_stop_StopCalledTwice_NewObjectCreatedOnSecondTime()
	{
		$subject = new Watch();
		
		$subject->start('a');
		$subject->start('a');
		
		$subject->stop('a');
		$subject->stop('a');
		
		self::assertCount(3, $subject->getRecords()->a);
	}
	
	public function test_stop_DataSet()
	{
		$subject = new Watch();
		
		$now = microtime(true);
		
		$subject->start('a');
		$subject->stop('a');
		
		$records = $subject->getRecords()->a[0];
		
		self::assertEquals($now, $records->unix_end_time, '', 0.01);
		self::assertEquals($now, strtotime($records->readable_end_time), '', 1);
	}
	
	public function test_stop_ObjectWasInitialized_RelativeTimesSet()
	{
		$subject = new Watch();
		
		$subject->init();
		
		usleep(100);
		$subject->start('a');
		
		usleep(10000);
		$subject->stop('a');
		
		$records = $subject->getRecords()->a[0];
		
		self::assertTrue(1.0 > $records->start_time && $records->start_time > 0.0);
		self::assertTrue(1.0 > $records->run_time && $records->run_time > 0.0);
		self::assertTrue($records->run_time > $records->start_time);
	}
	
	public function test_stop_TagsPassedOnlyInStop_TagsAppended()
	{
		$subject = new Watch();
		
		$subject->stop('a', null, ['a' => 123]);
		$records = $subject->getRecords()->a[0];
		
		self::assertEquals(['a' => 123], $records->tags);
	}
	
	public function test_stop_TagsPassedInStart_TagsAppended()
	{
		$subject = new Watch();
		
		$subject->start('a', null, ['b' => 'cde']);
		$subject->stop('a', null, ['a' => 123]);
		
		$records = $subject->getRecords()->a[0];
		
		self::assertEquals(['b' => 'cde', 'a' => 123], $records->tags);
	}
	
	public function test_stop_StartForKeyNotCalled_KeySet()
	{
		$subject = new Watch();
		
		$subject->stop('a', 'a');
		$record = $subject->getRecords()->a[0];
		
		self::assertEquals('a', $record->key);
	}
	
	public function test_stop_StartForKeyNotCalledAndKeyNotPassedInStop_KeyIsNull()
	{
		$subject = new Watch();
		
		$subject->stop('a');
		$record = $subject->getRecords()->a[0];
		
		self::assertTrue(!isset($record->key));
	}
	
	
	public function test_loop_TagsNotPassed_TagsNotSet()
	{
		$subject = new Watch();
		
		$subject->loop('a');
		$record = $subject->getRecords()->a[0];
		
		self::assertTrue(!isset($record->key));
	}
	
	public function test_loop_TagsPassed_TagsSet()
	{
		$subject = new Watch();
		
		$subject->loop('a', ['a' => 123]);
		$record = $subject->getRecords()->a[0];
		
		self::assertEquals(['a' => 123], $record->tags);
	}
	
	public function test_loop_CalledMoreThenOnce_LoopsAppended()
	{
		$subject = new Watch();
		
		$subject->loop('a');
		$subject->loop('a');
		$subject->loop('a');
		
		self::assertCount(3, $subject->getRecords()->a);
	}
	
	public function test_loop_DataSet()
	{
		$subject = new Watch();
		
		$now = microtime(true);
		
		$subject->loop('a');
		
		$records = $subject->getRecords()->a[0];
		
		self::assertEquals($now, $records->unix_start_time, '', 0.01);
		self::assertEquals($now, strtotime($records->readable_start_time), '', 1);
		self::assertNull($records->start_time);
	}
	
	public function test_loop_WatchInitialized_StartTimeSet()
	{
		$subject = new Watch();
		
		$subject->init();
		$subject->loop('a');
		
		$records = $subject->getRecords()->a[0];
		
		self::assertNotNull($records->start_time);
	}
	
	public function test_loop_LoopCalledMoreThenOnce_LastInstanceComplete()
	{
		$subject = new Watch();
		
		
		$subject->loop('a');
		
		usleep(100);
		$now = microtime(true);
		$subject->loop('a');
		
		$records = $subject->getRecords()->a[0];
		
		self::assertEquals($now, $records->unix_end_time, '', 0.01);
	}
	
	public function test_endLoop_LoopNotStarted_NoError()
	{
		$subject = new Watch();
		
		$subject->endLoop('a');
		
		self::assertTrue(!isset($subject->getRecords()->a));
	}
	
	public function test_endLoop_LoopStarted_DataSet()
	{
		$subject = new Watch();
		
		$subject->loop('a');
		
		usleep(100);
		$now = microtime(true);
		$subject->endLoop('a');
		
		$records = $subject->getRecords()->a[0];
		
		self::assertEquals($now, $records->unix_end_time, '', 0.01);
	}
	
	public function test_endLoop_EndLoopCalledMorThenOnce_consecutiveCallsIgnored()
	{
		$subject = new Watch();
		
		$subject->loop('a');
		$subject->endLoop('a');
		
		$endTime = $subject->getRecords()->a[0]->unix_end_time;
		
		usleep(1000);
		$subject->endLoop('a');
		
		self::assertCount(1, $subject->getRecords()->a);
		self::assertEquals($endTime, $subject->getRecords()->a[0]->unix_end_time);
	}
	
	
	public function test_detect_WatchNotInitialized_StartTimeNotSet()
	{
		$subject = new Watch();
		
		$subject->detect('a');
		
		$records = $subject->getRecords()->a[0];
		
		self::assertNull($records->start_time ?? null);
	}
	
	public function test_detect_WatchInitialized_StartTimeSet()
	{
		$subject = new Watch();
		
		$subject->init();
		
		usleep(100);
		
		$subject->detect('a');
		$records = $subject->getRecords()->a[0];
		
		self::assertTrue($records->start_time > 0);
	}
	
	public function test_detect_DataSet()
	{
		$subject = new Watch();
		
		$subject->init();
		
		usleep(100);
		
		$now = microtime(true);
		$subject->detect('a');
		
		$records = $subject->getRecords()->a[0];
		
		self::assertEquals($now, $records->unix_start_time, '', 0.01);
		self::assertEquals($now, strtotime($records->readable_start_time), '', 1);
	}
	
	public function test_detect_GroupCalledMoreThenOnce_NewRecordAppended()
	{
		$subject = new Watch();
		
		$subject->detect('a');
		$subject->detect('a');
		$subject->detect('a');
		
		self::assertCount(3, $subject->getRecords()->a);
	}
	
	public function test_detect_TagsNotPassed_TagsNotSet()
	{
		$subject = new Watch();
		
		$subject->detect('a');
		
		self::assertNull($subject->getRecords()->a[0]->tags ?? null);
	}
	
	public function test_detect_TagsPassed_TagsSet()
	{
		$subject = new Watch();
		
		$subject->detect('a', ['a' => 123]);
		
		self::assertEquals(['a' => 123], $subject->getRecords()->a[0]->tags);
	}
	
	
	public function test_getRecordsAsJson()
	{
		$subject = new Watch();
		
		$subject->start('a', null, ['b' => 'cde']);
		$subject->stop('a', null, ['a' => 123]);
		
		self::assertEquals(json_encode($subject->getRecords()), $subject->getRecordsAsJson());
		self::assertEquals(json_encode($subject->getRecords(), JSON_PRETTY_PRINT), $subject->getRecordsAsJson(true));
	}
	
	
	public function test_reset_DataReset()
	{
		$subject = new Watch();
		
		$subject->loop('a');
		$subject->reset();
		
		self::assertEquals((object)[], $subject->getRecords());
	}
	
	public function test_reset_LoopCacheReset()
	{
		$subject = new Watch();
		
		$subject->loop('a');
		$subject->reset();
		
		$subject->endLoop('a');
		
		self::assertEquals((object)[], $subject->getRecords());
	}
	
	public function test_reset_DataCacheReset()
	{
		$subject = new Watch();
		
		$subject->start('a');
		$subject->reset();
		
		$subject->stop('a');
		
		self::assertNull($subject->getRecords()->a[0]->unix_start_time);
	}
	
	public function test_reset_StartTimeReset()
	{
		$subject = new Watch();
		
		$subject->init();
		$subject->reset();
		
		$subject->start('a');
		
		self::assertNull($subject->getRecords()->a[0]->start_time);
	}
}