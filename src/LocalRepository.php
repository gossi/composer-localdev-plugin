<?php
namespace gossi\composer\localdev;

use Composer\Package\PackageInterface;
use Composer\Repository\ArrayRepository;
use Composer\Config;
use Composer\Package\Loader\ArrayLoader;

class LocalRepository extends ArrayRepository {
	
	protected $config;
	protected $loader;
	
	/**
	 * @param Config $config
	 */
	public function __construct(Config $config) {
		$this->config = $config;
		$this->loader = new ArrayLoader();
	}
	
	/* (non-PHPdoc)
	 * @see \Composer\Repository\ArrayRepository::initialize()
	 */
	protected function initialize() {
		$this->packages = array();
	
		var_dump($this->config->has('localdev'));
		if ($this->config->has('localdev')) {
			$localdev = $this->config->get('localdev');
			var_dump($localdev);

			$this->parseGlobal($localdev);
			$this->parseVendors($localdev);
			$this->parsePackages($localdev);
		}
	}
	
	protected function parseGlobal($localdev) {
		if (isset($localdev['']) && file_exists($localdev[''])) {
			$roots = is_array($localdev['']) ? $localdev[''] : array($localdev['']);
			
			foreach ($roots as $root) {
				foreach (new \DirectoryIterator($root) as $file) {
					if ($file->isDir()) {
						$dir = str_replace('//', '/', $root . '/' . $file->getFilename());
						$this->retrieveVendorPackages($file->getFilename(), $dir);
					}
				}
			}
		}
	}
	
	protected function parseVendors($localdev) {
		unset($localdev['']);
		$keys = array_filter(array_keys($localdev), function ($key) {
			return strpos($key, '/') === false;
		});
		
		foreach ($keys as $vendor) {
			$locations = $localdev[$vendor];
			$roots = is_array($locations) ? $locations: array($locations);
			foreach ($roots as $root) {
				$this->retrieveVendorPackages($vendor, $root);
			}
		}
	}
	
	protected function parsePackages($localdev) {
		$keys = array_filter(array_keys($localdev), function ($key) {
			return strpos($key, '/') !== false;
		});
		
		foreach ($keys as $name) {
			$this->parsePackage($name, $localdev[$name]);
		}
	}
	
	protected function retrieveVendorPackages($vendor, $path) {
		if (file_exists($path)) {
			foreach (new \DirectoryIterator($path) as $file) {
				if ($file->isDir()) {
					$name = str_replace('//', '/', $vendor . '/' . $file->getFilename());
					$this->parsePackage($name, $file->getPathname());
				}
			}
		}
	}
	
	protected function parsePackage($name, $path) {
		$composer = str_replace('//', '/', $path . '/composer.json');
		
		if (!file_exists($composer)) {
			return;
		}
		
		$json = json_decode(file_get_contents($composer), true);
		$package = $this->loader->load($json);
		
		if ($package->getName() == strtolower($name)) {
			$this->addPackage($package);
		}
	}

}