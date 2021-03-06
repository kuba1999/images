<?php
/**
 * Test: IPub\Flickr\TestCase
 * @testCase
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:Images!
 * @subpackage	Tests
 * @since		5.0
 *
 * @date		28.02.15
 */

namespace IPubTests\Images;

use Nette;
use Nette\Application;
use Nette\Application\UI;

use Tester;

use IPub;
use IPub\Images;

require_once __DIR__ . '/../bootstrap.php';

class TestCase extends Tester\TestCase
{
	/**
	 * @var Nette\Application\IPresenterFactory
	 */
	protected $presenterFactory;

	/**
	 * @var \SystemContainer|\Nette\DI\Container
	 */
	protected $container;

	/**
	 * Set up
	 */
	public function setUp()
	{
		parent::setUp();

		$this->container = $this->createContainer();

		// Get presenter factory from container
		$this->presenterFactory = $this->container->getByType('Nette\Application\IPresenterFactory');
	}

	/**
	 * @return Application\IPresenter
	 */
	protected function createPresenter()
	{
		// Create test presenter
		$presenter = $this->presenterFactory->createPresenter('Test');
		// Disable auto canonicalize to prevent redirection
		$presenter->autoCanonicalize = FALSE;

		return $presenter;
	}

	/**
	 * @return \SystemContainer|\Nette\DI\Container
	 */
	protected function createContainer()
	{
		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);

		$config->addParameters([
			"wwwDir" => realpath(__DIR__ . DIRECTORY_SEPARATOR .'web'),
			"uploadDir" => realpath(__DIR__ . DIRECTORY_SEPARATOR .'upload'),
		]);

		Images\DI\ImagesExtension::register($config);

		$config->addConfig(__DIR__ . '/files/config.neon', $config::NONE);
		$config->addConfig(__DIR__ . '/files/presenters.neon', $config::NONE);

		return $config->createContainer();
	}
}

class TestPresenter extends UI\Presenter
{
	use Images\TImages;

	public function renderDefault()
	{
		// Set template for component testing
		$this->template->setFile(__DIR__ . DIRECTORY_SEPARATOR .'templates'. DIRECTORY_SEPARATOR .'default.latte');
	}
}

class DefaultImagesStorage extends Images\Storage\FileStorage
{

}