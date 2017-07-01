<?php
namespace gossi\composer\localdev;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Installer\InstallationManager;
use Composer\Installer\InstallerInterface;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Util\Filesystem;

class LocalInstaller implements InstallerInterface {

	private $composer;
	private $io;
	private $repo;
	private $filesystem;
	private $installManager;

	public function __construct(Composer $composer, IOInterface $io, LocalRepository $repo) {
		$this->composer = $composer;
		$this->io = $io;
		$this->repo = $repo;
		$this->filesystem = new Filesystem();
	}

	public function getInstallerManager() {
		return $this->installManager;
	}

	public function setInstallManager(InstallationManager $installManager) {
		$this->installManager = $installManager;
	}

	/**
	 *
	 * @param PackageInterface $package
	 * @return InstallerInterface
	 */
	private function getDedicatedInstaller(PackageInterface $package) {
		return $this->getInstallerManager()->getInstaller($package->getType());
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

		// return if link is already well placed
		if (is_link($originPath) && readlink($originPath) == $installPath) {
			return;
		}

		// remove installation first...
		if (file_exists($installPath)) {
			$this->filesystem->removeDirectory($installPath);
		}

		// ... then create a symlink
		$this->symlink($originPath, $installPath);

		$this->io->write(sprintf('    => Symlinked <info>%s</info> from <fg=magenta>%s</>', $package->getName(), $originPath), true);
		$this->io->write('', true);
	}

	/**
	 * A lightweight method of the symlink method in Symfony\Filesystem
	 *
	 * Creates a symbolic link or copy a directory.
	 *
	 * @param string $originDir The origin directory path
	 * @param string $targetDir The symbolic link name
	 *
	 * @throws \Exception When symlink fails
	 */
	private function symlink($originDir, $targetDir) {
		// Windows logic
		if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
			$output = array();
			$return = 0;
			exec('mklink /J ' . escapeshellarg($targetDir) . ' ' . escapeshellarg($originDir), $output, $return);

			if ($return === 0) {
				return;
			}
		}

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
		$installPath = $this->getInstallPath($package);

		// remove symlink, if package is handled and symlinked
		if ($this->handlePackage($package) && is_link($installPath)) {
			unlink($installPath);
		}

		// anyway, just use the regular installer
		else {
			$installer = $this->getDedicatedInstaller($package);
			$installer->uninstall($repo, $package);
		}
	}

	public function install(InstalledRepositoryInterface $repo, PackageInterface $package) {
// 		printf("Handle Install: %s\n", $package->getName());
		// if this packages is handled but already symlinked, circumvent installation to suppress
		// modified packaged warning (ask for discard)
		if (!($this->handlePackage($package) && is_link($this->getInstallPath($package)))) {
			$installer = $this->getDedicatedInstaller($package);
			$installer->install($repo, $package);
		}

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
		// if package is already symlinked, skip composer update
		if ($this->handlePackage($target) && is_link($this->getInstallPath($target))) {
			$this->ensureSymlink($target);
		} else {
			$this->getDedicatedInstaller($initial)->update($repo, $initial, $target);
		}
	}

	public function getInstallPath(PackageInterface $package) {
		return $this->getDedicatedInstaller($package)->getInstallPath($package);
	}
}
