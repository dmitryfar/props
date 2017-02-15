<?
namespace Properties;

class Properties {
	private static $instances = [];

	public static function read($filename, $key = "default") {
		$properties = array();
		if (file_exists($filename)) {
			$properties = parse_ini_file(/* DOCUMENT_ROOT."/". */$filename, true);
		} else {

		}
		$propertyFile = new PropertyFile($key, $filename, $properties);
		self::$instances[$key] = $propertyFile;
		return self::$instances[$key];
	}

	private static function getProperty($key) {
		$args = func_get_args();
		foreach (self::$instances as $instance) {
			$value = call_user_func_array(array($instance, "getProperty"), $args);
			if ($value != null) {
				return $value;
			}
		}
		return null;
	}

	public static function getInstance($key) {
		if (!array_key_exists($key, self::$instances)) {
			return null; // or throw exception ?
		}
		return self::$instances[$key];
	}

	public static function getDefaultInstance() {
		if (!array_key_exists("default", self::$instances)) {
			$propertyFile = new PropertyFile("default", null, []);
			self::$instances["default"] = $propertyFile;
		}
		return self::$instances["default"];
	}

	public static function get($property = null) {
		$args = func_get_args();
		if (count($args) >= 1){
			$s = call_user_func_array(array(__CLASS__, "getProperty"), $args);
		} else {
			$s = array();
			foreach (self::$instances as $key => $instance) {
				if (count($instance->properties) > 0) {
					$s = array_merge($s, $instance->properties);
				}
			}
		}
		return $s;
	}

	public static function getSection($section, $property = null) {
		$args = func_get_args();
		foreach (self::$instances as $key => $instance) {
			if (count($instance->properties) > 0) {
				// $value = $instance->getSection($section, $property);
				$value = call_user_func_array(array($instance, "getSection"), $args);
				if ($value != null) {
					return $value;
				}
			}
		}
		return null;

		$array = self::getProperty($section);

		if ($property) {
			$s = $array[$property];
			if ($s == null) {
				return null;
			}
			if (is_array($s)) {
				return $s;
			}
			$args = func_get_args();
			$args = array_slice($args, 2);
			$parserArgs = array($s, $args);
			$s = call_user_func_array(__NAMESPACE__ . "\PropertyParser::parse", $parserArgs);
			return $s;
		} else {
			return $array;
		}

	}

	public static function set($key, $value) {
		$propInstance = null;
		$propValue = null;
		foreach (self::$instances as $instance) {
			$propValue = $instance->properties[$key];
			if ($propValue != null) {
				$propInstance = $instance;
				break;
			}
		}

		if ($propInstance == null) {
			$propInstance = self::getDefaultInstance();
		}

		$propInstance->properties[$key] = $value;
	}
}

class PropertyFile {
	public $key;
	public $filename;
	public $properties;

	public function __construct($key, $filename, $properties) {
		$this->key = $key;
		$this->filename = $filename;
		$this->properties = $properties;

		// define uppercased prop keys
		foreach ($this->properties as $key => $val) {
			if (is_array($val)) {
				continue;
			}
			if (strtoupper($key) == $key) {
				define($key, $val);
			}
		}
	}

	public function getProperty($key) {
		$args = func_get_args();

		$s = (array_key_exists($key, $this->properties)) ? $this->properties[$key] : null;
		if ($s == null) {
			return null;
		}
// 		if (is_array($s)) {
// 			return $s;
// 		}

		$vals = array_shift($args);
		//$parserArgs = array_unshift($args, $s);
		$parserArgs = array($s, $args);

		$s = call_user_func_array(__NAMESPACE__ . "\PropertyParser::parse", $parserArgs);
		return $s;


		if ($s == null) {
			return null;
		}

		for($i=1; $i<count($args); $i++) {
			$k = $i-1;
			$s = preg_replace("/\{$k\}/iU", $args[$i], $s);
		}
		if (!is_array($s)) {
			$s = preg_replace("/\{\d+\}/iU", "", $s);
		}

		return $s;
	}

	public function getSection($section, $property = null) {
		$array = self::getProperty($section);

		if ($property && $array) {
			$s = (array_key_exists($property, $array)) ? $array[$property] : null;
			if ($s == null) {
				return null;
			}
			if (is_array($s)) {
				return $s;
			}
			$args = func_get_args();
			$args = array_slice($args, 2);
			$parserArgs = array($s, $args);
			$s = call_user_func_array(__NAMESPACE__ . "\PropertyParser::parse", $parserArgs);
			return $s;
		} else {
			return $array;
		}

	}
}

class PropertyParser {
	public static function parse($haystack, $args) {
		$s = $haystack;
		if ($s == null) {
			return null;
		}

		if (!is_array($s) && $args != null) {
			for($i=0; $i<count($args); $i++) {
				$k = $i;
				$s = preg_replace("/\{$k\}/iU", $args[$i], $s);
			}
			$s = preg_replace("/\{\d+\}/iU", "", $s);
		}

		return $s;
	}
}
?>