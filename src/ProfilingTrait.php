<?php
namespace NamelessCoder\PhpunitXhprof;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;

/**
 * Profiling Trait
 * ---------------
 *
 * Contains assistant methods for running profilings
 * of code and grabbing specific results. Uses VFS
 * for storing the profiling outputs so that no file
 * system configuration is required whatsoever.
 *
 * Usage example
 * -------------
 *
 * public function testSomething() {
 *     // Profile only a specific scope plus a single
 *     // related function "myfunction". The result
 *     // will return everything that happens inside
 *     // any class of the defined scope, plus every
 *     // call to "myfunction" regardless of where the
 *     // function is called. You can find the syntax
 *     // of profile keys in the xhprof documentation.
 *     $methods = array(
 *         '/^Vendor\\Namespace\\Scope\\.+/',
 *         '/.+myfunction$'
 *     );
 *     $closure = function() use $foo, $bar {
 *         // do something that gets profiled. We have
 *         // already created and prepped our instances
 *         // so we don't profile that part of the code.
 *         $foo->doThatWith($bar);
 *     }
 *     $profile = $this->profileClosure($closure, $methods);
 *     $this->assertLessThan(
 *         10,
 *         $profile['Vendor\\Class::funcA==>Vendor\\Class::funcB']['ct'],
 *         'Method funcB was called from funcA more than expected 10 times'
 *     );
 * }
 *
 * The method then fails if for whatever reason any
 * code involved in your closure caused more than 10
 * calls to "funcB" when called from inside "funcA".
 *
 * Caveat
 * ------
 *
 * Note: you can include both CPU time and memory use
 * in the profiling results, however, when your tests
 * are expected to execute on multiple platforms of
 * varying potency you should probably avoid this and
 * stick to number of methods called (or a relative
 * measure, e.g. percent of total profiled time, but
 * even so platform differences may cause skew/failure).
 */
trait ProfilingTrait {

	/**
	 * Profile the code contained inside the closure, returning
	 * an array of XHProf profiling information. Optionally
	 * filtered by a set of regular expressions which the called
	 * function/class must match for the result to be returned.
	 *
	 * Special note: the $flags default value is hardcoded to
	 * avoid errors when xhprof is not loaded - instead, this
	 * causes a graceful "test skipped".
	 *
	 * @param \Closure $closure A standard Closure that will execute exactly the code that will be profiled, nothing more.
	 * @param array $methodMatchExpressions An array of PERL regular expressions to filter which methods' results are returned.
	 * @param integer $flags Standard XHPROF flags for what gets profiled. Default excludes built-in functions and CPU/memory.
	 * @param array $options Optional further options (second argument for xhprof_enable).
	 * @return array
	 */
	protected function profileClosure(
		\Closure $closure,
		array $methodMatchExpressions = array(),
		$flags = 1,
		$options = array()
	) {
		if (!$this->isProfilingExtensionLoaded()) {
			$this->markTestSkipped('XHProf is not installed; test must be skipped');
		}
		$folder = vfsStream::newDirectory('profiles');
		$backup = ini_set('xhprof.output_dir', vfsStream::url('profiles'));
		xhprof_enable($flags, $options);
		$closure();
		$profile = xhprof_disable();
		ini_set('xhprof.output_dir', $backup);
		if (!empty($methodMatchExpressions)) {
			foreach ($profile as $methodIdentifier => $_) {
				$keep = FALSE;
				foreach ($methodMatchExpressions as $expression) {
					if (preg_match($expression, $methodIdentifier)) {
						$keep = TRUE;
					}
				}
				if (!$keep) {
					unset($profile[$methodIdentifier]);
				}
			}
		}
		return $profile;
	}

	/**
	 * @return boolean
	 */
	protected function isProfilingExtensionLoaded() {
		return extension_loaded('xhprof');
	}

}
