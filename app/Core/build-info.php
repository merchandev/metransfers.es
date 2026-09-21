<?php
/**
 * Filled in by `git archive` via the export-subst attribute (see
 * .gitattributes) when tools/build-release.ps1 builds a release zip. A
 * plain checkout (git clone/pull, not archive) leaves the placeholder
 * literal, so MT_BUILD_COMMIT falls back to '' in that case rather than
 * showing the un-substituted string.
 */
$mt_build_commit = '$Format:%h$';
if ( ! defined( 'MT_BUILD_COMMIT' ) ) {
	define( 'MT_BUILD_COMMIT', 0 === strpos( $mt_build_commit, '$Format' ) ? '' : $mt_build_commit );
}
