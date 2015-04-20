<?php
namespace Phile\Plugin\Infostreams\PagelistSnippet;

class PagelistSnippet extends \Phile\Plugin\Infostreams\Snippets\Snippets {
	public $currentPage;
	public static $renderDepth = 0;
	public $pagelist = array();
	public $templateDir = null;

	public function pagelist($where, $template = null, $order = null, $under = null,
	                         $filter = null, $keyword = null, $param = "q", $inclusive = false) {
		$p = new \Phile\Repository\Page();
		$settings = \Phile\Registry::get('Phile_Settings');
		if ($order) {
			$settings = array_merge($settings, array('pages_order' => $order));
		}

		if (!$template) {
			$template = "pagelist-default";
			$this->templateDir = PLUGINS_DIR . "infostreams/pagelistSnippet/Templates";
		}

		$all = $p->findAll($settings);

		$list = array();

		if (is_array($where)) {
			// user provided a list of pages
			// - we obtain those from '$all' to make sure any provided sorting order is applied
			foreach ($all as $p) {
				if (in_array($p->getUrl(), $where)) {
					$list[] = $p;
				}
			}
		} else {
			// user provided a keyword, such as 'all', 'below' or 'search'

			switch ($where) {
				default:
				case "below":
				case "search":
					$root = null;
					if ($under) {
						$p = new \Phile\Repository\Page();
						$root = $p->findByPath($under);
					}

					// for 'search', we support providing an 'under' argument
					if ($where == "search" && $root == null) {
						// however, if it isn't provided, we default to searching all pages
						$list = $all;
						break;
					}
					if (!$root) {
						$root = $this->currentPage;
					}
					$root_dir = dirname($root->getFilePath());
					$inclusive = $this->isTrue($inclusive);
					foreach ($all as $p) {
						if (strpos($p->getFilePath(), $root_dir) !== false) {
							if ($inclusive || (!$inclusive && ($p->getFilePath() != $root->getFilePath()))) {
								$list[] = $p;
							}
						}
					}
					break;

				case "all":
					$list = $all;
					break;
			}

			// once we have obtained a base list of pages, we can search and filter
			if ($where == "search") {
				if (array_key_exists($param, $_GET)) {
					$keyword = urldecode($_GET[$param]);
					$search = new Search($list);
					$list = $search->query($keyword);
				} else {
					$list = array();
				}
			}
		}

		if (!is_null($filter) && is_array($filter)) {
			// apply filter to filter by meta tag
			$filter_count = count($filter);
			foreach ($list as $i => $p) {
				$match_count = 0;
				foreach ($filter as $k => $v) {
					// Check if each filter matches.
					// Filters can be specified as regular expressions.
					if (preg_match('/' . trim($v, '/') . '/', $p->getMeta()->$k)) {
						$match_count++;
					}
				}

				if ($match_count != $filter_count) {
					// if one or more filters don't match, then remove this page from the list
					unset($list[$i]);
				}
			}

			// renumber filtered list
			$list = array_values($list);
		}

		// if the user provides a keyword to filter/search, do that now
		if (!is_null($keyword)) {
			foreach ($list as $i=>$l) {
				if ($l == $this->currentPage) {
					unset($list[$i]);
					break;
				}
			}
			$search = new Search($list);
			$list = $search->query($keyword);
		}

		// We have now obtained the list of pages to display. Store it in a public
		// place so that we can inject it into the template rendering process by
		// intercepting the 'template_engine_registered' event in the main Plugin class.
		$this->pagelist = $list;

		// We can start rendering now, but only if the recursion protection says we're good
		if (self::$renderDepth < 3) {
			// (The recursion limit is here to make sure we don't accidentally time-out
			//  recursively rendering the same page)
			self::$renderDepth++;

			// get the template engine, and make sure we're working on an independent copy
			$templateEngine = \Phile\ServiceLocator::getService('Phile_Template');
			$clone = clone $templateEngine;

			// The template engine determines which template to use by looking at the
			// page's metadata. We create a dummy page with only one piece of metadata
			// (the name of the template file) to force the engine to use the template
			// we specify.
			$clone->setCurrentPage(new DummyPage($template));

			// now do the actual rendering and return the output
			$output = $clone->render();
			self::$renderDepth--;

			return $output;
		}

		// the recursion limit has been reached - return an empty string
		return "";
	}
}

class DummyPage extends \Phile\Model\Page {
	protected $template;

	public function __construct($template) {
		$this->template = $template;
	}

	public function getMeta() {
		return new DummyMeta($this->template);
	}

	public function getContent() {
		return "";
	}
}

class DummyMeta {
	protected $template;

	public function __construct($template) {
		$this->template = $template;
	}

	public function get($k) {
		return $this->template;
	}
}