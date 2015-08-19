<?php
namespace gossi\composer\localdev;

use Composer\Plugin\PluginInterface;
use Composer\IO\IOInterface;
use Composer\Composer;
use Composer\Script\Event;
use Composer\Installer\InstallationManager;
use Composer\Installer\PackageEvent;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\InstallerEvent;

class ComposerLocaldevPlugin implements PluginInterface {
	
	private static $instance;
	private $composer;
	private $repo;
	private $installer;
	private $installManager;
	
	public function activate(Composer $composer, IOInterface $io) {
		$this->composer = $composer;

		$this->repo = new LocalRepository($composer->getConfig());
		$composer->getRepositoryManager()->addRepository($this->repo);

		$this->installer = new LocalInstaller($composer, $io, $this->repo);
		$this->installer->setInstallManager($composer->getInstallationManager());
// 		$composer->getInstallationManager()->addInstaller($this->installer);
		
		$this->installManager = new InstallationManager();
		$this->installManager->addInstaller($this->installer);
		$composer->setInstallationManager($this->installManager);
		
		// install scripts to hook into composer
		$root = $composer->getPackage();
		$scripts = $root->getScripts();
		
		$preInstall = isset($scripts['pre-install-cmd']) ? $scripts['pre-install-cmd'] : array();
		$preUpdate = isset($scripts['pre-update-cmd']) ? $scripts['pre-update-cmd'] : array();
		$prePackageInstall = isset($scripts['pre-package-install']) ? $scripts['pre-package-install'] : array();
		$prePackageUpdate = isset($scripts['pre-package-update']) ? $scripts['pre-package-update'] : array();
		$preDependencySolving = isset($scripts['pre-dependencies-solving']) ? $scripts['pre-dependencies-solving'] : array();
		
		$preInstall[] = 'gossi\\composer\\localdev\\ComposerLocaldevPlugin::preInstall';
		$preUpdate[] = 'gossi\\composer\\localdev\\ComposerLocaldevPlugin::preInstall';
		$prePackageInstall[] = 'gossi\\composer\\localdev\\ComposerLocaldevPlugin::prePackageInstall';
		$prePackageUpdate[] = 'gossi\\composer\\localdev\\ComposerLocaldevPlugin::prePackageUpdate';
		$preDependencySolving[] = 'gossi\\composer\\localdev\\ComposerLocaldevPlugin::preDependencySolving';
		
		$scripts['pre-install-cmd'] = $preInstall;
		$scripts['pre-update-cmd'] = $preUpdate;
		$scripts['pre-package-install'] = $prePackageInstall;
		$scripts['pre-package-update'] = $prePackageUpdate;
		$scripts['pre-dependencies-solving'] = $preDependencySolving;
		
		$root->setScripts($scripts);

		self::$instance = $this;
	}
	
	public static function preDependencySolving(InstallerEvent $event) {
		// add repo for resolving dependencies - hum, will it work?
		$that = self::$instance;
		$event->getPool()->addRepository($that->repo);
	}
	
	public static function preInstall(Event $event) {
		$that = self::$instance;
		$that->updateInstallManager();
	}
	
	public static function prePackageInstall(PackageEvent $event) {
		$that = self::$instance;
		$that->updateInstallManager();
// 		$operation = $event->getOperation();
// 		if ($operation instanceof InstallOperation) {
// 			/* @var $operation InstallOperation */
// 			$package = $operation->getPackage();
			
// 			if ($package->getType() == 'composer-plugin') {
// 				$that->updateInstallManager();
// 			}
// 		}
	}
	
	public static function prePackageUpdate(PackageEvent $event) {
		$that = self::$instance;
		$operation = $event->getOperation();
		if ($operation instanceof UpdateOperation) {
			/* @var $operation UpdateOperation */
			$package = $operation->getTargetPackage();
				
			if ($package->getType() == 'composer-plugin') {
				$that->updateInstallManager();
			}
		}
	}
	
	private function updateInstallManager() {
		$installManager = $this->composer->getInstallationManager();
		
		// getting the installers is so creepy
		$r = new \ReflectionClass($installManager);
		$p = $r->getProperty('installers');
		$p->setAccessible(true);
		
		$manager = $this->installer->getInstallerManager();
		$installers = $p->getValue($installManager);
		foreach ($installers as $installer) {
// 			printf("Add Installer: %s\n", get_class($installer));
			if ($installer != $this->installer) {
				$manager->addInstaller($installer);
			}
		}
		
		$this->installer->setInstallManager($manager);
		$this->composer->setInstallationManager($this->installManager);
	}
}
