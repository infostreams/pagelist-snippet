<?php
namespace Phile\Plugin\Infostreams\PagelistSnippet;

class Plugin extends \Phile\Plugin\AbstractPlugin implements \Phile\Gateway\EventObserverInterface {
	protected $events = [ // Register events (Phile >= 1.5)
		'request_uri' => 'on',
		'template_engine_registered' => 'on',
		'plugins_loaded' => 'on'
	];
	public $pagelist_snippet = null;

	public function __construct() {
		if (!class_exists('\Phile\Core\Event')) {
			// Phile < 1.5 => register event
			\Phile\Event::registerEvent('request_uri', $this);
			\Phile\Event::registerEvent('template_engine_registered', $this);
			\Phile\Event::registerEvent('plugins_loaded', $this);
		}

		$this->pagelist_snippet = new PagelistSnippet();
	}

	/**
	 * event method
	 *
	 * @param string $eventKey
	 * @param mixed $data
	 *
	 * @return mixed
	 */
	public function on($eventKey, $data = null) {
		switch ($eventKey) {
			case "request_uri":
				// we need to intercept and store the current page
				$p = new \Phile\Repository\Page();
				$this->pagelist_snippet->currentPage = $p->findByPath($data['uri']);
				break;

			case "template_engine_registered":
				// Here we inject the result of the pagelist into the template variables.
				$data['data']['pagelist'] = $this->pagelist_snippet->pagelist;

				if ($data['data']['current_page'] instanceof DummyPage) {
					// we're rendering the pagelist snippet, and the user didn't
					// specify a template
					// -> we force Twig to accept our default template
					if ($this->pagelist_snippet->templateDir) {
						// TODO: Twig only :-(
						$twig = $data['engine'];
						$loader = $twig->getLoader();
						$loader->addPath($this->pagelist_snippet->templateDir);

						$data['data']['theme_dir'] = $this->pagelist_snippet->templateDir;
					}
				}
				break;

			case "plugins_loaded":
				// Here we register the (pagelist) snippet with the Snippets class
				$plugins = \Phile\Bootstrap::getInstance()->getPlugins();
				if (array_key_exists('infostreams\\snippets', $plugins)) {
					$snippets = $plugins['infostreams\\snippets'];
					$snippets->add($this->pagelist_snippet);
				}
				break;
		}
	}

	/**
	 * inject settings
	 *
	 * @param array $settings
	 */
	public function injectSettings(array $settings = null) {
		$this->settings = ($settings === null) ? array() : $settings;
	}
}
