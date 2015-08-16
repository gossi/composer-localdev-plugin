<?php
namespace gossi\composer\localdev;

use Composer\Repository\InstalledRepositoryInterface;
use Composer\Installer\InstallerInterface;
use Composer\Package\PackageInterface;
use Composer\Composer;

class LocalInstaller implements InstallerInterface {
	
	private $composer;
	private $repo;
	private $installers;
	
	public function __construct(Composer $composer, LocalRepository $repo) {
		$this->composer = $composer;
		$this->repo = $repo;
	}
	
	private function getInstallers() {
		if ($this->installers == null) {
			$this->installers = clone $this->composer->getInstallationManager();
			$this->installers->removeInstaller($this);
		}
		return $this->installers;
	}
	
	/**
	 * 
	 * @param PackageInterface $package
	 * @return InstallerInterface
	 */
	private function getDedicatedInstaller(PackageInterface $package) {
		return $this->installers->getInstaller($package->getType());
	}
	
	private function handlePackage(PackageInterface $package) {
		return $this->repo->hasPackage($package);
	}
	
	public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package) {
		if ($this->handlePackage($package)) {
			
		}
		// TODO Auto-generated method stub
	}

	public function install(InstalledRepositoryInterface $repo, PackageInterface $package) {
		$installer = $this->getDedicatedInstaller($package);
		$installer->install($repo, $package);
		
		if ($this->handlePackage($package)) {
			printf("Install from local repo: %s\n", $package->getName());
		} else {
			printf("Do not install from local repo: %s\n", $package->getName());
		}
	}

	public function isInstalled(InstalledRepositoryInterface $repo, PackageInterface $package) {
		echo "LocalInstaller.isInstalled(): Installed Packages: \n";
		foreach ($repo->getPackages() as $package) {
			printf("Installed Package: %s\n", $package->getName());
		}
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