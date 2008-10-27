<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Dmitry Dulepov <dmitry@typo3.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * $Id: $
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */

require_once(PATH_tslib . 'class.tslib_eidtools.php');

/**
 * This class create frontend page address from the page id value and parameters.
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 * @package	TYPO3
 * @subpackage	tx_pagepath
 */
class tx_pagepath_resolver {

	protected	$pageId;
	protected	$parameters;

	/**
	 * Initializes the instance of this class.
	 *
	 * @return	void
	 */
	public function __construct() {
		$params = unserialize(base64_decode(t3lib_div::GPvar('data')));
		if (is_array($params)) {
			$this->pageId = $params['id'];
			$this->parameters = $params['parameters'];
		}

		tslib_eidtools::connectDB();
	}

	/**
	 * Handles incoming trackback requests
	 *
	 * @return	void
	 */
	public function main() {
		header('Content-type: text/plain; charset=iso-8859-1');
		if ($this->pageId) {
			$this->createTSFE();

			$cObj = t3lib_div::makeInstance('tslib_cObj');
			/* @var $cObj tslib_cObj */
			$typolinkConf = array(
				'parameter' => $this->pageId,
				'useCacheHash' => $this->parameters != '',
				'additionalParams' => $this->parameters,
			);
			echo $cObj->typoLink_URL($typolinkConf);
		}
	}

	/**
	 * Initializes TSFE. This is necessary to have proper environment for typoLink.
	 *
	 * @return	void
	 */
	protected function createTSFE() {
		require_once(PATH_tslib . 'class.tslib_fe.php');
		require_once(PATH_t3lib . 'class.t3lib_page.php');
		require_once(PATH_tslib . 'class.tslib_content.php');
		require_once(PATH_t3lib . 'class.t3lib_userauth.php' );
		require_once(PATH_tslib . 'class.tslib_feuserauth.php');
		require_once(PATH_t3lib . 'class.t3lib_tstemplate.php');
		require_once(PATH_t3lib . 'class.t3lib_cs.php');

		$tsfeClassName = t3lib_div::makeInstanceClassName('tslib_fe');

		$initCache = version_compare(TYPO3_branch, '4.3', '>=');
		if ($initCache) {
			require_once(PATH_t3lib . 'class.t3lib_cache.php');
			require_once(PATH_t3lib . 'cache/class.t3lib_cache_abstractbackend.php');
			require_once(PATH_t3lib . 'cache/class.t3lib_cache_abstractcache.php');
			require_once(PATH_t3lib . 'cache/class.t3lib_cache_exception.php');
			require_once(PATH_t3lib . 'cache/class.t3lib_cache_factory.php');
			require_once(PATH_t3lib . 'cache/class.t3lib_cache_manager.php');
			require_once(PATH_t3lib . 'cache/class.t3lib_cache_variablecache.php');
			require_once(PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_classalreadyloaded.php');
			require_once(PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_duplicateidentifier.php');
			require_once(PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_invalidbackend.php');
			require_once(PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_invalidcache.php');
			require_once(PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_invaliddata.php');
			require_once(PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_nosuchcache.php');
			$GLOBALS['typo3CacheManager'] = t3lib_div::makeInstance('t3lib_cache_Manager');
			$cacheFactoryClass = t3lib_div::makeInstanceClassName('t3lib_cache_Factory');
			$GLOBALS['typo3CacheFactory'] = new $cacheFactoryClass($GLOBALS['typo3CacheManager']);
			unset($cacheFactoryClass);
		}

		$GLOBALS['TSFE'] = new $tsfeClassName($GLOBALS['TYPO3_CONF_VARS'], $this->pageId, '');
		if ($initCache) {
			$GLOBALS['TSFE']->initCaches();
		}
		$GLOBALS['TSFE']->connectToMySQL();
		$GLOBALS['TSFE']->initFEuser();
		$GLOBALS['TSFE']->determineId();
		$GLOBALS['TSFE']->getCompressedTCarray();
		$GLOBALS['TSFE']->initTemplate();
		$GLOBALS['TSFE']->getConfigArray();

		// Set linkVars, absRefPrefix, etc
		require_once(PATH_tslib . 'class.tslib_pagegen.php');
		TSpagegen::pagegenInit();
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pagepath/class.tx_pagepath_resolver.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pagepath/class.tx_pagepath_resolver.php']);
}

if (t3lib_div::getIndpEnv('REMOTE_ADDR') != $_SERVER['SERVER_ADDR']) {
	header('HTTP/1.0 403 Access denied');
	// Empty output!!!
}
else {
	$resolver = t3lib_div::makeInstance('tx_pagepath_resolver');
	/* @var $resolver tx_pagepath_resolver */
	$resolver->main();
}

?>