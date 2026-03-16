<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UPT_Image_WebP {

    public static function init() {
        // Converte imagens para WEBP logo após o upload padrão do WordPress
        add_filter( 'wp_handle_upload', [ self::class, 'convert_to_webp' ] );
        // Permite sobrescrever a qualidade via painel/admin
        add_filter( 'upt_webp_quality', [ self::class, 'filter_webp_quality' ] );
        // Aplica a mesma qualidade aos tamanhos intermediários gerados pelo WP
        add_filter( 'wp_editor_set_quality', [ self::class, 'filter_editor_quality' ], 10, 2 );
    }

    /**
     * Retorna se a conversão está ativa pelo painel.
     *
     * @return bool
     */
    private static function is_enabled() {
        return get_option( 'upt_webp_enabled', '1' ) === '1';
    }

    /**
     * Retorna a qualidade configurada no painel (1-100) ou mantém o valor padrão.
     *
     * @param int $quality Qualidade padrão definida no filtro.
     * @return int
     */
    public static function filter_webp_quality( $quality ) {
        if ( ! self::is_enabled() ) {
            return $quality;
        }

        $saved = get_option( 'upt_webp_quality', '' );

        if ( $saved === '' ) {
            return $quality;
        }

        $saved_int = absint( $saved );

        if ( $saved_int < 1 || $saved_int > 100 ) {
            return $quality;
        }

        return $saved_int;
    }

    /**
     * Força a qualidade dos editores de imagem do WordPress (thumbs e tamanhos intermediários)
     * para alinhar com a configuração do painel.
     *
     * @param int    $quality  Qualidade que o WP usaria (padrão ~82).
     * @param string $mime_type Mime alvo (image/jpeg, image/webp etc.).
     * @return int
     */
    public static function filter_editor_quality( $quality, $mime_type = '' ) {
        if ( ! self::is_enabled() ) {
            return $quality;
        }

        // Se somente JPEG estiver ativo, não altera outros formatos
        $only_jpeg = get_option( 'upt_webp_only_jpeg', '0' ) === '1';
        if ( $only_jpeg && $mime_type && ! in_array( $mime_type, [ 'image/jpeg', 'image/webp' ], true ) ) {
            return $quality;
        }

        $saved = self::filter_webp_quality( $quality );

        // Garante faixa válida de retorno
        if ( $saved < 1 ) {
            $saved = 1;
        } elseif ( $saved > 100 ) {
            $saved = 100;
        }

        return $saved;
    }

    private static function is_animated_gif( $file ) {
        if ( ! is_string( $file ) || $file === '' || ! file_exists( $file ) ) {
            return false;
        }

        $handle = @fopen( $file, 'rb' );
        if ( ! $handle ) {
            return false;
        }

        $frames = 0;
        while ( ! feof( $handle ) && $frames < 2 ) {
            $chunk = @fread( $handle, 1024 * 100 );
            if ( $chunk === false || $chunk === '' ) {
                break;
            }

            $frames += preg_match_all( '#\x00\x21\xF9\x04.{4}\x00\x2C#s', $chunk, $m );
        }

        @fclose( $handle );
        return $frames > 1;
    }

    private static function clamp_quality( $quality ) {
        $q = absint( $quality );
        if ( $q < 1 ) {
            return 1;
        }
        if ( $q > 100 ) {
            return 100;
        }
        return $q;
    }

    private static function should_use_lossless_webp( $mime, $quality ) {
        $q = self::clamp_quality( $quality );
        if ( $mime === 'image/png' ) {
            return $q >= 90;
        }
        return false;
    }

    private static function encode_webp( $file, $webp_path, $mime, $quality ) {
        $q = self::clamp_quality( $quality );
        $use_lossless = self::should_use_lossless_webp( $mime, $q );

        if ( class_exists( 'Imagick' ) ) {
            try {
                $img = new Imagick();
                $img->readImage( $file );

                if ( method_exists( $img, 'setImageFormat' ) ) {
                    $img->setImageFormat( 'webp' );
                }

                if ( method_exists( $img, 'setImageCompressionQuality' ) ) {
                    $img->setImageCompressionQuality( $q );
                }

                if ( method_exists( $img, 'setOption' ) ) {
                    $img->setOption( 'webp:method', '6' );
                    $img->setOption( 'webp:alpha-quality', (string) min( 100, max( 90, $q ) ) );
                    if ( $use_lossless ) {
                        $img->setOption( 'webp:lossless', 'true' );
                    }
                }

                $ok = $img->writeImage( $webp_path );
                $img->clear();
                $img->destroy();

                return (bool) $ok;
            } catch ( Exception $e ) {
            }
        }

        if ( ! function_exists( 'imagewebp' ) ) {
            return false;
        }

        $image = false;

        switch ( $mime ) {
            case 'image/jpeg':
                $image = @imagecreatefromjpeg( $file );
                break;
            case 'image/png':
                $image = @imagecreatefrompng( $file );
                if ( $image ) {
                    if ( function_exists( 'imagepalettetotruecolor' ) ) {
                        @imagepalettetotruecolor( $image );
                    }
                    @imagealphablending( $image, true );
                    @imagesavealpha( $image, true );
                }
                break;
            case 'image/gif':
                $image = @imagecreatefromgif( $file );
                break;
        }

        if ( ! $image ) {
            return false;
        }

        $gd_quality = $q;
        if ( $use_lossless ) {
            $gd_quality = 100;
        }

        $ok = (bool) @imagewebp( $image, $webp_path, $gd_quality );
        imagedestroy( $image );
        return $ok;
    }

    /**
     * Converte a imagem enviada para WEBP tentando reduzir o tamanho do arquivo.
     * Se o WEBP ficar maior que o arquivo original, mantém o original.
     *
     * @param array $upload Resultado do wp_handle_upload.
     * @return array
     */
    public static function convert_to_webp( $upload ) {
        if ( ! self::is_enabled() ) {
            return $upload;
        }

        // Se houve erro no upload, não faz nada
        if ( ! empty( $upload['error'] ) ) {
            return $upload;
        }

        // Garante que seja uma imagem
        if ( empty( $upload['type'] ) || strpos( $upload['type'], 'image/' ) !== 0 ) {
            return $upload;
        }

        $mime = $upload['type'];
        $supported_mimes = [ 'image/jpeg', 'image/png', 'image/gif' ];

        $only_jpeg = get_option( 'upt_webp_only_jpeg', '0' ) === '1';

        // Apenas formatos comuns que o GD costuma suportar
        if ( ! in_array( $mime, $supported_mimes, true ) ) {
            return $upload;
        }

        if ( $only_jpeg && $mime !== 'image/jpeg' ) {
            return $upload;
        }

        $file = isset( $upload['file'] ) ? $upload['file'] : '';

        if ( ! $file || ! file_exists( $file ) ) {
            return $upload;
        }

        // Segurança básica para saber se é imagem válida
        $info = @getimagesize( $file );
        if ( ! $info ) {
            return $upload;
        }

        if ( $mime === 'image/gif' && self::is_animated_gif( $file ) ) {
            return $upload;
        }

        // Caminho do arquivo WEBP
        $webp_path = preg_replace( '/\.[^.]+$/', '.webp', $file );
        if ( ! $webp_path ) {
            return $upload;
        }

        // Qualidade padrão (0–100). 80 geralmente é boa para reduzir bem o tamanho com pouca perda visível.
        $quality = apply_filters( 'upt_webp_quality', 80 );

        $quality = self::clamp_quality( $quality );

        if ( ! self::encode_webp( $file, $webp_path, $mime, $quality ) ) {
            return $upload;
        }

        // Garante que os tamanhos estejam atualizados
        @clearstatcache( true, $file );
        @clearstatcache( true, $webp_path );

        if ( ! file_exists( $webp_path ) ) {
            return $upload;
        }

        $original_size = @filesize( $file );
        $webp_size     = @filesize( $webp_path );

        // Se o WEBP for menor (ou se não conseguirmos ler o tamanho original), usa o WEBP
        if ( $webp_size > 0 && ( $original_size === false || $webp_size <= $original_size ) ) {
            // Remove o arquivo original
            @unlink( $file );

            // Atualiza informações de upload para apontar para o WEBP
            $upload['file'] = $webp_path;

            if ( ! empty( $upload['url'] ) ) {
                $upload['url'] = preg_replace( '/\.[^.]+$/', '.webp', $upload['url'] );
            }

            $upload['type'] = 'image/webp';
        } else {
            // WEBP maior que o original: descarta o WEBP e mantém o arquivo enviado
            @unlink( $webp_path );
        }

        return $upload;
    }
}
