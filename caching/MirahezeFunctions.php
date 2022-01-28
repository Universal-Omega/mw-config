<?php
class MirahezeFunctions {
	public const TAGS = [
		'default' => 'default',
		'beta' => 'betaheze',
	];

	public const REALMS = [
		'betaheze.org' => 'betaheze',
	];

	public const SUFFIXES = [
		'wiki',
		'wikibeta',
	];

	public static function initialise() {
		global $wgConf;
		$wgConf = new SiteConfiguration();

		$wgConf->suffixes = self::SUFFIXES;
		$wgConf->wikis = self::getLocalDatabases()[ self::getRealm() ];
	}

	public function getLocalDatabases() {
		$allDatabases = self::evalDbListExpression( 'all.dblist + beta.dblist - deleted.dblist' );
		$productionDatabases = self::evalDbListExpression( 'all.dblist - deleted.dblist' );

		return [
			self::TAGS['default'] => $productionDatabases,
			self::TAGS['beta'] => $allDatabases,
		]
	}

	public static function evalDbListExpression( $expr ) {
		$expr = trim( strtok( $expr, "#\n" ), "% " );
		$tokens = preg_split( '/ +([-+&]) +/m', $expr, 0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
		$result = self::readDbListFile( basename( $tokens[0], '.dblist' ) );
		while ( ( $op = next( $tokens ) ) && ( $term = next( $tokens ) ) ) {
			$dbs = self::readDbListFile( basename( $term, '.dblist' ) );
			if ( $op === '+' ) {
				$result = array_unique( array_merge( $result, $dbs ) );
			} elseif ( $op === '-' ) {
				$result = array_diff( $result, $dbs );
			} elseif ( $op === '&' ) {
				$result = array_intersect( $result, $dbs );
			}
		}
		sort( $result );
		return $result;
	}

	public static function readDbListFile( $dblist ) {
		$fileName = dirname( __DIR__ ) . '/dblists/' . $dblist . '.dblist';
		$lines = @file( $fileName, FILE_IGNORE_NEW_LINES );
		if ( !$lines ) {
			throw new Exception( __METHOD__ . ": unable to read $dblist." );
		}

		$dbs = [];
		foreach ( $lines as $line ) {
			// Ignore empty lines and lines that are comments
			if ( $line !== '' && $line[0] !== '#' ) {
				$dbs[] = $line;
			}
		}

		return $dbs;
	}

	public static function getRealm() {
		$domain = explode( '.', $_SERVER['SERVER_NAME'], 2 )[1];

		return self::REALMS[$domain] ?? 'default';
	}
}
