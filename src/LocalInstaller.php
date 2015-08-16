<?php
namespace gossi\composer\localdev;

use Composer\Repository\InstalledRepositoryInterface;
use Composer\Installer\InstallerInterface;
use Composer\Package\PackageInterface;
use Composer\Composer;
use Composer\Util\Filesystem;

class LocalInstaller implements InstallerInterface {
	
	private $composer;
	private $repo;
	private $installers;
	private $filesystem;
	
	public function __construct(Composer $composer, LocalRepository $repo) {
		$this->composer = $composer;
		$this->repo = $repo;
		$this->filesystem = new Filesystem();
	}
	
	private function getInstallers() {
		if ($this->installers == null) {
			$installManager = $this->composer->getInstallationManager();
			$this->installers = clone $installManager;
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
		return $this->getInstallers()->getInstaller($package->getType());
	}
	
	private function handlePackage(PackageInterface $package) {
		$packageId = $package->getName();
		
		foreach ($this->repo->getPackages() as $repoPackage) {
			if ($packageId === $repoPackage->getName()) {
				return true;
			}
		}
		
		return false;
	}
	
	private function ensureSymlink(PackageInterface $package) {
		$installPath = $this->getDedicatedInstaller($package)->getInstallPath($package);
		$originPath = $this->repo->getPath($package->getName());
		
		// remove installation first...
		if (file_exists($installPath)) {
			$this->filesystem->removeDirectory($installPath);
		}
		
		// ... then create a symlink
		$this->symlink($originPath, $installPath);
	}
	
	/**
	 * A lightweight method of the symlink method in Symfony\Filesystem
	 *
	 * Creates a symbolic link or copy a directory.
	 *
	 * @param string $originDir The origin directory path
	 * @param string $targetDir The symbolic link name
	 * @param Boolean $copyOnWindows Whether to copy files if on Windows
	 *
	 * @throws \Exception When symlink fails
	 */
	private function symlink($originDir, $targetDir) {
		@mkdir(dirname($targetDir), 0777, true);
	
		$ok = false;
		if (is_link($targetDir)) {
			if (readlink($targetDir) != $originDir) {
				$this->filesystem->remove($targetDir);
			} else {
				$ok = true;
			}
		}
	
		if (!$ok) {
			if (true !== @symlink($originDir, $targetDir)) {
				$report = error_get_last();
				if (is_array($report)) {
					if (defined('PHP_WINDOWS_VERSION_MAJOR') && false !== strpos($report['message'], 'error code(1314)')) {
						throw new \Exception('Unable to create symlink due to error code 1314: \'A required privilege is not held by the client\'. Do you have the required Administrator-rights?');
					}
				}
				throw new \Exception(sprintf('Failed to create symbolic link from %s to %s', $originDir, $targetDir));
			}
		}
	}
	
	public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package) {
		$installer = $this->getDedicatedInstaller($package);
		$installer->uninstall($repo, $package);
	}

	public function install(InstalledRepositoryInterface $repo, PackageInterface $package) {
		$installer = $this->getDedicatedInstaller($package);
		$installer->install($repo, $package);
		
		if ($this->handlePackage($package)) {
			$this->ensureSymlink($package);
		}
	}

	public function isInstalled(InstalledRepositoryInterface $repo, PackageInterface $package) {
		return $repo->hasPackage($package);
	}
	
	public function supports($packageType) {
		return true;
	}
	
	public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target) {
		$this->getDedicatedInstaller($initial)->update($repo, $initial, $target);
		
		if ($this->handlePackage($target)) {
			$this->ensureSymlink($target);
		}
	}
	
	public function getInstallPath(PackageInterface $package) {
		return $this->getDedicatedInstaller($package)->getInstallPath($package);
	}
}