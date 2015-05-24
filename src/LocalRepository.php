<?php
namespace gossi\composer\localdev;

use Composer\Package\PackageInterface;
use Composer\Package\CompletePackage;
use Composer\Repository\ArrayRepository;
use Composer\Config;
use Composer\Package\Loader\ArrayLoader;
use Composer\Json\JsonFile;

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
		
		if ($this->config->has('localdev')) {
			$localdev = $this->config->get('localdev');
			print_r($localdev);

			$this->parseGlobal($localdev);
			$this->parseVendors($localdev);
			$this->parsePackages($localdev);
		}
		
		echo "Packages: " . count($this->packages) . "\n";
		
		foreach ($this->packages as $package) {
			echo $package->getName() . "\n";
		}
	}
	
	protected function parseGlobal($localdev) {
		if (isset($localdev['']) && file_exists($localdev[''])) {
			$roots = is_array($localdev['']) ? $localdev[''] : array($localdev['']);
			
			foreach ($roots as $root) {
				foreach (new \DirectoryIterator($root) as $file) {
					if ($file->isDir() && !$file->isDot()) {
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
		
		echo 'Parsed Vendors: ';
		print_r($keys);

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
		
		echo 'Parsed Packages: ';
		print_r($keys);
		
		foreach ($keys as $name) {
			$this->parsePackage($name, $localdev[$name]);
		}
	}
	
	protected function retrieveVendorPackages($vendor, $path) {
		if (file_exists($path)) {
			foreach (new \DirectoryIterator($path) as $file) {
				if ($file->isDir() && !$file->isDot()) {
					$name = str_replace('//', '/', $vendor . '/' . $file->getFilename());
					$this->parsePackage($name, $file->getPathname());
				}
			}
		}
	}
	
	protected function parsePackage($name, $path) {
		echo 'Parse Package ' . $name . ' at ' . $path . "\n";
		$composer = new JsonFile(str_replace('//', '/', $path . '/composer.json'));
		echo 'Path: '.$composer->getPath()."\n";
		
		if (!$composer->exists()) {
			return;
		}
		
		echo 'Found Package: ' . $name . ' at ' . $path . "\n";

		$json = $composer->read();
		$json['version'] = 'dev-live';
		
		$package = $this->loader->load($json);

		if ($package->getName() == strtolower($name)) {
			echo 'Package and path name match'."\n";
			$this->addPackage($package);
		}
	}

}