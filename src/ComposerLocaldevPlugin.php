<?php
namespace gossi\composer\localdev;

use Composer\Plugin\PluginInterface;
use Composer\IO\IOInterface;
use Composer\Composer;

class ComposerLocaldevPlugin implements PluginInterface {
	
	public function activate(Composer $composer, IOInterface $io) {
		$repo = new LocalRepository($composer->getConfig());
		$composer->getRepositoryManager()->addRepository($repo);
	}

}