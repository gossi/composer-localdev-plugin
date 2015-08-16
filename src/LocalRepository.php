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
	protected $paths;
	
	/**
	 * @param Config $config
	 */
	public function __construct(Config $config) {
		$this->config = $config;
		$this->loader = new ArrayLoader();
		$this->paths = array();
	}
	
	public function getPath($name) {
		if (isset($this->paths[$name])) {
			return $this->paths[$name];
		}
		return null;
	}

	/* (non-PHPdoc)
	 * @see \Composer\Repository\ArrayRepository::initialize()
	 */
	protected function initialize() {
		$this->packages = array();
		
		if ($this->config->has('localdev')) {
			$localdev = $this->config->get('localdev');

			$this->parseGlobal($localdev);
			$this->parseVendors($localdev);
			$this->parsePackages($localdev);
		}
	}
	
	protected function parseGlobal($localdev) {
		if (isset($localdev[''])) {
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
				if ($file->isDir() && !$file->isDot()) {
					$name = str_replace('//', '/', $vendor . '/' . $file->getFilename());
					$this->parsePackage($name, $file->getPathname());
				}
			}
		}
	}
	
	protected function parsePackage($name, $path) {
		if (is_link($path)) {
			return;
		}

		$composer = new JsonFile(str_replace('//', '/', $path . '/composer.json'));
		
		if (!$composer->exists()) {
			return;
		}

		try {
			$json = $composer->read();
			$json['version'] = 'dev-live';
		
			$package = $this->loader->load($json);
			
			if ($package->getName() == strtolower($name)) {
				if (!$this->hasPackage($package)) {
					$this->addPackage($package);
					$this->paths[strtolower($name)] = $path;
				}
			}
		} catch (\Exception $e) {}
	}

}