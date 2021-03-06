<?php
/**
 * ImageExtension.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:Images!
 * @subpackage	DI
 * @since		5.0
 *
 * @date		05.04.14
 */

namespace IPub\Images\DI;

use Nette;
use Nette\DI;
use Nette\PhpGenerator as Code;

class ImagesExtension extends DI\CompilerExtension
{
	/**
	 * @var array
	 */
	private $defaults = [
		'routes' => [],
		'prependRoutesToRouter' => TRUE,
		'storage' => [],
		'rules' => [],
		'wwwDir' => NULL,
	];

	public function loadConfiguration()
	{
		$config = $this->getConfig($this->defaults);
		$builder = $this->getContainerBuilder();

		// Extension loader
		$loader = $builder->addDefinition($this->prefix('loader'))
			->setClass('IPub\Images\ImagesLoader');

		// Images presenter
		$builder->addDefinition($this->prefix('presenter'))
			->setClass('IPub\IPubModule\ImagesPresenter', [
				$config['wwwDir'],
			]);

		// Create default storage validator
		$validator = $builder->addDefinition($this->prefix('validator.default'))
			->setClass('IPub\Images\Validators\Validator');

		foreach ($config['rules'] as $rule) {
			$validator->addSetup('$service->addRule(?, ?, ?)', [
					$rule['width'],
					$rule['height'],
					isset($rule['algorithm']) ? $rule['algorithm'] : NULL,
				]);
		}

		if ($config['routes']) {
			$router = $builder->addDefinition($this->prefix('router'))
				->setClass('Nette\Application\Routers\RouteList')
				->addTag($this->prefix('routeList'))
				->setAutowired(FALSE);

			$i = 0;
			foreach ($config['routes'] as $mask => $metadata) {
				if (!is_array($metadata)) {
					$mask = $metadata;
					$metadata = [];
				}

				$builder->addDefinition($this->prefix('route.' . $i))
					->setClass('IPub\Images\Application\Route', [$mask, $metadata])
					->setAutowired(FALSE)
					->setInject(FALSE);

				// Add route to router
				$router->addSetup('$service[] = ?', [
					$this->prefix('@route.' . $i),
				]);

				$i++;
			}
		}

		foreach ($config['storage'] as $name => $provider) {
			$this->compiler->parseServices($builder, [
				'services' => [$this->prefix('storage' . $name) => $provider],
			]);
			$loader->addSetup('registerStorage', [$this->prefix('@storage' . $name)]);
		}

		// Update presenters mapping
		$builder->getDefinition('nette.presenterFactory')
			->addSetup('if (method_exists($service, ?)) { $service->setMapping([? => ?]); } '
				.'elseif (property_exists($service, ?)) { $service->mapping[?] = ?; }',
				['setMapping', 'IPub', 'IPub\IPubModule\*\*Presenter', 'mapping', 'IPub', 'IPub\IPubModule\*\*Presenter']
			);

		// Register template helpers
		$builder->addDefinition($this->prefix('helpers'))
			->setClass('IPub\Images\Templating\Helpers')
			->setFactory($this->prefix('@loader') . '::createTemplateHelpers')
			->setInject(FALSE);
	}

	public function beforeCompile()
	{
		$config = $this->getConfig($this->defaults);
		$builder = $this->getContainerBuilder();

		if ($config['prependRoutesToRouter']) {
			$router = $builder->getByType('Nette\Application\IRouter');

			if ($router) {
				if (!$router instanceof DI\ServiceDefinition) {
					$router = $builder->getDefinition($router);
				}

			} else {
				$router = $builder->getDefinition('router');
			}

			$router->addSetup('IPub\Images\Helpers::prependRoute', [
				'@self',
				$this->prefix('@router'),
			]);
		}
		
		// Install extension latte macros
		$latteFactory = $builder->getDefinition($builder->getByType('\Nette\Bridges\ApplicationLatte\ILatteFactory') ?: 'nette.latteFactory');

		$latteFactory
			->addSetup('IPub\Images\Latte\Macros::install(?->getCompiler())', array('@self'))
			->addSetup('addFilter', array('isSquare', array($this->prefix('@helpers'), 'isSquare')))
			->addSetup('addFilter', array('isHigher', array($this->prefix('@helpers'), 'isHigher')))
			->addSetup('addFilter', array('isWider', array($this->prefix('@helpers'), 'isWider')))
			->addSetup('addFilter', array('fromString', array($this->prefix('@helpers'), 'fromString')))
			->addSetup('addFilter', array('getImagesLoaderService', array($this->prefix('@helpers'), 'getImagesLoaderService')));
	}

	/**
	 * @param Code\ClassType $class
	 */
	public function afterCompile(Code\ClassType $class)
	{
		parent::afterCompile($class);

		$initialize = $class->methods['initialize'];
		$initialize->addBody('IPub\Images\Forms\Controls\ImageUploadControl::register();');
	}

	/**
	 * @param Nette\Configurator $config
	 * @param string $extensionName
	 */
	public static function register(Nette\Configurator $config, $extensionName = 'images')
	{
		$config->onCompile[] = function (Nette\Configurator $config, Nette\DI\Compiler $compiler) use ($extensionName) {
			$compiler->addExtension($extensionName, new ImagesExtension());
		};
	}
}
