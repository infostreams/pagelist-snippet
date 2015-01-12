<?php
namespace Phile\Plugin\Infostreams\PagelistSnippet;

class Search {
	protected $list = array();
	protected $index = null;

	public function __construct($list) {
		$this->list = $list;

		// extract document contents and create index
		$documents = array();
		foreach ($this->list as $i => $p) {
			$content = $p->getContent();
			$metadata = implode(" ", $p->getMeta()->getAll());
			$documents[$i] = $content . " " . $metadata;
		}

		$this->index = $this->createIndex($documents);
	}

	public function query($q) {
		$search_result = $this->runQuery(strtolower($q));

		$pages = array();
		foreach ($search_result as $i=>$score) {
			$pages[] = $this->list[$i];
		}

		return $pages;
	}

	// adapted from http://stackoverflow.com/a/26390637/426224
	protected function runQuery($query = '') {
		$search_result = array();
		$query         = $this->prepare($query);

		if ($query != '') {
			$words          = explode(" ", $query);
			$dictionary     = $this->index['dictionary'];
			$document_count = $this->index['document_count'];

			foreach ($words as $word) {
				if (array_key_exists($word, $dictionary)) {
					$stats = $dictionary[$word];

					foreach ($stats['tf'] as $i => $tf) {
						// calculate term frequency–inverse document frequency
						$score = $tf * log($document_count + 1 / $stats['df'] + 1, 2);

						if (isset($search_result[$i])) {
							$search_result[$i] += $score;
						} else {
							$search_result[$i]  = $score;
						}
					}
				}
			}

			// length normalise
			foreach ($search_result as $i => $score) {
				$search_result[$i] = $score / $this->index['term_count'][$i];
			}

			// sort from high to low
			arsort($search_result);
		}

		return $search_result;
	}

	protected function createIndex($documents = array()) {
		$dictionary = array();
		$term_count = array();

		foreach ($documents as $i => $document) {
			// prepare document - strip HTML, remove punctuation, etc
			$document = $this->prepare($document);

			// extract words
			$terms = explode(" ", $document);

			// store number of words
			$term_count[$i] = count($terms);

			// from http://phpir.com/simple-search-the-vector-space-model/
			//
			// tf–idf, short for term frequency–inverse document frequency, is a numerical
			// statistic that is intended to reflect how important a word is to a document
			// in a list of documents
			foreach ($terms as $term) {
				if (!isset($dictionary[$term])) {
					// keep track of in how many documents this word appears (document frequency,
					// or 'df'), and how many times it appears in each individual document (term
					// frequency, 'tf')
					$dictionary[$term] = array('df' => 0, 'tf' => array());
				}
				if (!isset($dictionary[$term]['tf'][$i])) {
					$dictionary[$term]['df']++;
					$dictionary[$term]['tf'][$i] = 0;
				}

				$dictionary[$term]['tf'][$i]++;
			}
		}

		return array(
			'dictionary'     => $dictionary,
			'term_count'     => $term_count,
			'document_count' => count($documents),
		);
	}

	protected function prepare($string) {
		// strip tags and lowercase
		$string = strtolower(strip_tags($string));
		// remove everything non-alphanumeric
		$string = preg_replace('#[^[:alnum]]#', ' ', $string);
		// remove punctuation and symbols
		$string = preg_replace('#[\.,\-\!\?\"\:\;\&\%\^\#\$\\\/]#', ' ', $string);
		// make sure all text is separated by one space at most
		$string = preg_replace('#\\s+#', ' ', $string);
		// trim whitespace from beginning and end of string
		$string = trim($string);

		return $string;
	}
}
