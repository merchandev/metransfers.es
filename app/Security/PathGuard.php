<?php
namespace MeTransfers\Security;

/**
 * Resolve both paths before allowing a destructive filesystem operation.
 */
final class PathGuard {
    public static function containsFile( $root, $candidate ) {
        $root = realpath( (string) $root );
        $candidate = realpath( (string) $candidate );

        if ( false === $root || false === $candidate || ! is_dir( $root ) || ! is_file( $candidate ) ) {
            return false;
        }

        $root = self::normalize( $root );
        $candidate = self::normalize( $candidate );
        $prefix = rtrim( $root, '/' ) . '/';

        return 0 === strpos( $candidate, $prefix );
    }

    private static function normalize( $path ) {
        $path = str_replace( '\\', '/', (string) $path );
        return '\\' === DIRECTORY_SEPARATOR ? strtolower( $path ) : $path;
    }
}
