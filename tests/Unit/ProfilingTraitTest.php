<?php
namespace NamelessCoder\PhpunitXhprof\Tests\Unit;

/**
 * Class ProfilingTraitTest
 */
class ProfilingTraitTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @test
	 */
	public function testProfileClosureMarksSkippedIfExtensionNotInstalled() {
		$mock = $this->getMockBuilder('NamelessCoder\\PhpunitXhprof\\ProfilingTrait')
			->setMethods(array('isProfilingExtensionLoaded', 'markTestSkipped'))
			->getMockForTrait();
		$mock->expects($this->once())->method('isProfilingExtensionLoaded')->willReturn(FALSE);
		$mock->expects($this->once())->method('markTestSkipped');
		$method = new \ReflectionMethod($mock, 'profileClosure');
		$method->setAccessible(TRUE);
		$result = $method->invokeArgs($mock, array(function() {}));
	}

	/**
	 * @param \Closure $closure
	 * @param array $methodMatchExpressions
	 * @param array $expected
	 * @param array $notExpected
	 * @dataProvider getProfileClosureData
	 * @test
	 */
	public function testProfileClosure(\Closure $closure, array $methodMatchExpressions, array $expected, array $notExpected) {
		$mock = $this->getMockBuilder('NamelessCoder\\PhpunitXhprof\\ProfilingTrait')->getMockForTrait();
		$method = new \ReflectionMethod($mock, 'profileClosure');
		$method->setAccessible(TRUE);
		$result = $method->invokeArgs($mock, array($closure, $methodMatchExpressions));
		foreach ($expected as $methodIdentifier => $expectedCount) {
			$this->assertEquals($expectedCount, $result[$methodIdentifier]['ct']);
		}
		foreach ($notExpected as $notExpectedMethod) {
			$this->assertArrayNotHasKey($notExpectedMethod, $result);
		}
	}

	/**
	 * @return array
	 */
	public function getProfileClosureData() {
		$self = $this;
		return array(
			array(
				function() use ($self) {
					$self->sleepyTime();
					$self->sleepyTime();
					$self->wakeyTime();
				},
				array(),
				array(
					sprintf('%s::%s\\{closure}==>%s::sleepyTime', __CLASS__, __NAMESPACE__, __CLASS__) => 2,
					sprintf('%s::%s\\{closure}==>%s::wakeyTime', __CLASS__, __NAMESPACE__, __CLASS__) => 1,
				),
				array()
			),
			array(
				function() use ($self) {
					$self->sleepyTime();
					$self->wakeyTime();
				},
				array(
					'/.+sleepyTime$/'
				),
				array(
					sprintf('%s::%s\\{closure}==>%s::sleepyTime', __CLASS__, __NAMESPACE__, __CLASS__) => 1
				),
				array(
					sprintf('%s::%s\\{closure}==>%s::wakeyTime', __CLASS__, __NAMESPACE__, __CLASS__)
				)
			),
		);
	}

	/**
	 * @return void
	 */
	public function sleepyTime() {
		usleep(1);
	}

	/**
	 * @return void
	 */
	public function wakeyTime() {
		usleep(1);
	}

}
