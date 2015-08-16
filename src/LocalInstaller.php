<?php
namespace gossi\composer\localdev;

use Composer\Repository\InstalledRepositoryInterface;
use Composer\Installer\InstallerInterface;
use Composer\Package\PackageInterface;
use Composer\Composer;

class LocalInstaller implements InstallerInterface {
	
	private $composer;
	private $repo;
	
	public function __construct(Composer $composer, LocalRepository $repo) {
		$this->composer = $composer;
		$this->repo = $repo;
	}
	
	private function getDedicatedInstaller() {
		
	}
	
	public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package) {
		// TODO Auto-generated method stub
	}

	public function install(InstalledRepositoryInterface $repo, PackageInterface $package) {
		if ($this->repo->hasPackage($package)) {
			printf("Install from local repo: %s\n", $package->getName());
		} else {
			printf("Do not install from local repo: %s\n", $package->getName());
		}
	}

	public function isInstalled(InstalledRepositoryInterface $repo, PackageInterface $package) {
		return $repo->hasPackage($package);
	}
	
	public function supports($packageType) {
		return true;
	}
	
	public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target) {
		// TODO Auto-generated method stub
	}
	
	public function getInstallPath(PackageInterface $package) {
		// TODO Auto-generated method stub
	}
}