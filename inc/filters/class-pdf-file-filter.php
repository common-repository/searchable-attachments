<?php
defined( 'WPINC' ) OR exit;

/**
 * Created by PhpStorm.
 * User: Dan
 * Date: 12/22/2015
 * Time: 3:58 AM
 */
class SA_PdfFileFilter extends SA_AbstractFileFilter {

	/**
	 * @return string[] The extensions supported by this filter.
	 */
	protected function getExtensions() {
		return array( 'pdf' );
	}

	/**
	 * @return int An integer from 0 to 100. Higher priorities will be attempted before lower priority thumbers.
	 */
	public function getPriority() {
		return 100;
	}

	public function filter( $id ) {
		$ret = null;
		$content = self::getAttacchmentContents( $id );
		if ( $content ) {
			include_once SA_PATH . 'inc/tools/pdfparser/Parser.php';

			try {
				$parser = new Smalot\PdfParser\Parser();
				$doc = @$parser->parseContent( $content );
				$ret = $doc->getText();
			} catch(Exception $e) { }
		}

		return $ret;
	}
}

SA_PdfFileFilter::init();