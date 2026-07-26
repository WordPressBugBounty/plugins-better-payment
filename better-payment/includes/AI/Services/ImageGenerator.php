<?php

namespace Better_Payment\Lite\AI\Services;

use Better_Payment\Lite\AI\AIManager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AI image generation → WordPress Media Library.
 *
 * Flow: prompt → active provider->generate_image() → receive URL or base64 →
 * persist as a real WP attachment → return the photo-ready shape the builder's
 * `photo` element and `replace_image` operation expect.
 *
 * The returned { id, url, sizes, alt } maps directly onto the photo element's
 * { src_id, src, src_sizes, alt } settings.
 */
class ImageGenerator {

    /**
     * Generate an image and add it to the media library.
     *
     * @param string $prompt
     * @param int    $attach_to_post Optional parent post id.
     * @return array|\WP_Error { id, url, sizes, alt }
     */
    public static function generate( string $prompt, int $attach_to_post = 0 ) {
        $provider = AIManager::active_provider();
        if ( null === $provider ) {
            return new \WP_Error( 'ai_no_provider', __( 'No AI provider is configured.', 'better-payment' ), [ 'status' => 400 ] );
        }
        if ( ! $provider->supports_images() ) {
            return new \WP_Error( 'ai_no_images', __( 'The selected AI provider cannot generate images. Choose OpenAI or Gemini under Settings → AI.', 'better-payment' ), [ 'status' => 400 ] );
        }
        if ( ! $provider->is_configured() ) {
            return new \WP_Error( 'ai_not_configured', __( 'The selected AI provider is missing its API key.', 'better-payment' ), [ 'status' => 400 ] );
        }

        $image = $provider->generate_image( $prompt );
        if ( ! empty( $image['error'] ) ) {
            return new \WP_Error( 'ai_image_error', (string) $image['error'], [ 'status' => 502 ] );
        }

        $saved = null;
        if ( ! empty( $image['b64'] ) ) {
            $saved = self::sideload_bytes( base64_decode( $image['b64'] ), (string) ( $image['mime'] ?? 'image/png' ), $prompt, $attach_to_post );
        } elseif ( ! empty( $image['url'] ) ) {
            $saved = self::sideload_url( (string) $image['url'], $prompt, $attach_to_post );
        }

        if ( is_wp_error( $saved ) ) {
            return $saved;
        }

        $attachment_id = (int) $saved;
        if ( ! $attachment_id ) {
            return new \WP_Error( 'ai_image_save_failed', __( 'Could not save the generated image.', 'better-payment' ), [ 'status' => 500 ] );
        }

        return self::attachment_payload( $attachment_id, $prompt );
    }

    /**
     * Persist raw image bytes as an attachment.
     *
     * @return int|\WP_Error
     */
    private static function sideload_bytes( string $bytes, string $mime, string $prompt, int $parent ) {
        self::require_media();

        $ext      = 'image/jpeg' === $mime ? 'jpg' : 'png';
        $filename = 'bp-ai-' . gmdate( 'YmdHis' ) . '-' . wp_generate_password( 6, false ) . '.' . $ext;
        $upload   = wp_upload_bits( $filename, null, $bytes );

        if ( ! empty( $upload['error'] ) ) {
            return new \WP_Error( 'ai_image_upload_failed', (string) $upload['error'], [ 'status' => 500 ] );
        }

        $attachment = [
            'post_mime_type' => $mime,
            'post_title'     => self::title_from_prompt( $prompt ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ];
        // wp_insert_attachment() returns 0 on failure (no $wp_error flag passed).
        $attach_id = wp_insert_attachment( $attachment, $upload['file'], $parent );
        if ( ! $attach_id ) {
            return new \WP_Error( 'ai_image_insert_failed', __( 'Could not create the image attachment.', 'better-payment' ), [ 'status' => 500 ] );
        }
        $meta = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
        wp_update_attachment_metadata( $attach_id, $meta );

        return (int) $attach_id;
    }

    /**
     * Download a remote image URL into the media library.
     *
     * @return int|\WP_Error
     */
    private static function sideload_url( string $url, string $prompt, int $parent ) {
        self::require_media();
        $attach_id = media_sideload_image( $url, $parent, self::title_from_prompt( $prompt ), 'id' );
        return is_wp_error( $attach_id ) ? $attach_id : (int) $attach_id;
    }

    /**
     * Build the photo-ready payload from a saved attachment.
     *
     * @return array{ id: int, url: string, sizes: array, alt: string }
     */
    private static function attachment_payload( int $attachment_id, string $prompt ): array {
        $alt = self::title_from_prompt( $prompt );
        update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );

        $meta  = wp_get_attachment_metadata( $attachment_id );
        $sizes = [];
        if ( is_array( $meta ) && ! empty( $meta['sizes'] ) ) {
            foreach ( array_keys( $meta['sizes'] ) as $size ) {
                $src = wp_get_attachment_image_src( $attachment_id, $size );
                if ( $src ) {
                    $sizes[ $size ] = [ 'url' => $src[0], 'width' => $src[1], 'height' => $src[2] ];
                }
            }
        }

        return [
            'id'    => $attachment_id,
            'url'   => (string) wp_get_attachment_url( $attachment_id ),
            'sizes' => $sizes,
            'alt'   => $alt,
        ];
    }

    private static function title_from_prompt( string $prompt ): string {
        $title = wp_trim_words( sanitize_text_field( $prompt ), 10, '' );
        return '' !== $title ? $title : __( 'AI generated image', 'better-payment' );
    }

    private static function require_media(): void {
        if ( ! function_exists( 'media_sideload_image' ) ) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
    }
}
