<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function upt_render_premium_card( $post_id, $schema_slug, $featured_id, $gallery_ids_list, $single_pdf_id, $single_video_id, $unique_image_id, $schema_has_image_field, $category_label, $category_class, $post_status, $status_label, $is_form_submission_listing ) {
    $fav_key = 'upt_favorites';
    $user_favs = get_user_meta( get_current_user_id(), $fav_key, true );
    if ( ! is_array( $user_favs ) ) { $user_favs = []; }
    $is_favorited = in_array( $post_id, $user_favs, true );
    $post_date = get_the_date( 'U', $post_id );
    $is_new = ( time() - $post_date ) < 7 * DAY_IN_SECONDS;

    $price_html = '';
    $preco_venda = get_post_meta( $post_id, $schema_slug . '_preco-de-venda', true );
    if ( $preco_venda !== '' && floatval( $preco_venda ) > 0 ) {
        $price_html = '<span class="upt-premium-card__price upt-premium-card__price--sale">R$ ' . number_format( floatval( $preco_venda ), 0, ',', '.' ) . '</span>';
    }
    if ( $price_html === '' ) {
        $preco_loc = get_post_meta( $post_id, $schema_slug . '_preco-de-aluguel', true );
        if ( $preco_loc !== '' && floatval( $preco_loc ) > 0 ) {
            $price_html = '<span class="upt-premium-card__price upt-premium-card__price--rent">R$ ' . number_format( floatval( $preco_loc ), 0, ',', '.' ) . '<span class="upt-premium-card__price-suffix">/mês</span></span>';
        }
    }

    $premium_meta = [];
    $premium_meta_fields = [
        'area-util'  => [ 'id' => $schema_slug . '_area-util',  'icon' => '📐', 'suffix' => ' m²' ],
        'quartos'    => [ 'id' => $schema_slug . '_quartos',   'icon' => '🛏️', 'suffix' => '' ],
        'suites'     => [ 'id' => $schema_slug . '_suites',    'icon' => '🚿', 'suffix' => '' ],
        'vagas'      => [ 'id' => $schema_slug . '_vagas',     'icon' => '🚗', 'suffix' => '' ],
    ];
    foreach ( $premium_meta_fields as $fkey => $fdef ) {
        $fval = get_post_meta( $post_id, $fdef['id'], true );
        if ( $fval !== '' && floatval( $fval ) > 0 ) {
            $premium_meta[] = '<span class="upt-premium-card__meta-item">' . $fdef['icon'] . ' ' . esc_html( $fval ) . $fdef['suffix'] . '</span>';
        }
    }

    $cidade = get_post_meta( $post_id, $schema_slug . '_cidade', true );
    $bairro = get_post_meta( $post_id, $schema_slug . '_bairro', true );
    $location_parts = array_filter( [ $bairro, $cidade ] );
    $location_html = ! empty( $location_parts ) ? '<div class="upt-premium-card__location">📍 ' . esc_html( implode( ' - ', $location_parts ) ) . '</div>' : '';

    $edit_url = admin_url( 'admin.php?page=upt-edit-item&action=edit&post=' . $post_id . '&schema=' . $schema_slug );
    ?>
    <div class="upt-premium-card<?php echo $is_favorited ? ' is-favorited' : ''; ?>" data-item-id="<?php echo esc_attr( $post_id ); ?>">
        <div class="upt-premium-card__media">
            <?php
            $rendered_media = false;
            if ( $featured_id && has_post_thumbnail() ) {
                the_post_thumbnail( 'medium_large' );
                $rendered_media = true;
            }
            if ( ! $rendered_media && $unique_image_id ) {
                $src = wp_get_attachment_image_url( $unique_image_id, 'medium_large' );
                if ( $src ) {
                    echo '<img src="' . esc_url( $src ) . '" alt="' . esc_attr( get_the_title() ) . '" loading="lazy" />';
                    $rendered_media = true;
                }
            }
            if ( ! $rendered_media && ! empty( $gallery_ids_list ) ) {
                $first_gid = is_array( $gallery_ids_list ) ? $gallery_ids_list[0] : $gallery_ids_list;
                $src = wp_get_attachment_image_url( $first_gid, 'medium_large' );
                if ( $src ) {
                    echo '<img src="' . esc_url( $src ) . '" alt="' . esc_attr( get_the_title() ) . '" loading="lazy" />';
                    $rendered_media = true;
                }
            }
            if ( ! $rendered_media && $single_pdf_id ) {
                $thumb = wp_get_attachment_image_url( $single_pdf_id, 'medium' );
                if ( $thumb ) {
                    echo '<img src="' . esc_url( $thumb ) . '" alt="PDF" loading="lazy" />';
                    $rendered_media = true;
                }
            }
            if ( ! $rendered_media ) {
                echo '<div class="upt-premium-card__no-image"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg></div>';
            }
            ?>
            <div class="upt-premium-card__overlay"></div>
            <button type="button" class="upt-premium-card__fav-btn<?php echo $is_favorited ? ' is-active' : ''; ?>" data-item-id="<?php echo esc_attr( $post_id ); ?>" title="<?php echo $is_favorited ? 'Desfavoritar' : 'Favoritar'; ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="<?php echo $is_favorited ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            </button>
            <button type="button" class="upt-premium-card__menu-btn" data-item-id="<?php echo esc_attr( $post_id ); ?>" title="Mais ações">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>
            </button>
            <?php if ( $is_new ) : ?>
                <span class="upt-premium-card__badge upt-premium-card__badge--new">Novo</span>
            <?php endif; ?>
            <?php
            $status_imovel_val = get_post_meta( $post_id, $schema_slug . '_status-do-imovel', true );
            if ( $status_imovel_val && $status_imovel_val !== 'Venda' ) :
            ?>
                <span class="upt-premium-card__badge upt-premium-card__badge--status"><?php echo esc_html( $status_imovel_val ); ?></span>
            <?php endif; ?>
        </div>
        <div class="upt-premium-card__body">
            <div class="upt-premium-card__header">
                <?php if ( $category_label ) : ?>
                    <span class="upt-premium-card__category"><?php echo esc_html( $category_label ); ?></span>
                <?php endif; ?>
                <span class="upt-premium-card__status upt-premium-card__status--<?php echo esc_attr( $post_status ); ?>"><?php echo esc_html( $status_label ); ?></span>
            </div>
            <h4 class="upt-premium-card__title" title="<?php echo esc_attr( get_the_title() ); ?>"><?php the_title(); ?></h4>
            <?php if ( $price_html ) : ?>
                <div class="upt-premium-card__price-row"><?php echo $price_html; ?></div>
            <?php endif; ?>
            <?php echo $location_html; ?>
            <?php if ( ! empty( $premium_meta ) ) : ?>
                <div class="upt-premium-card__meta"><?php echo implode( '', $premium_meta ); ?></div>
            <?php endif; ?>
        </div>
        <div class="upt-premium-card__context-menu" data-menu-for="<?php echo esc_attr( $post_id ); ?>">
            <a href="<?php echo esc_url( $edit_url ); ?>" class="upt-premium-card__menu-item open-edit-modal" data-item-id="<?php echo esc_attr( $post_id ); ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Editar
            </a>
            <a href="#" class="upt-premium-card__menu-item upt-duplicate-item" data-item-id="<?php echo esc_attr( $post_id ); ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                Duplicar
            </a>
            <?php if ( $post_status === 'publish' ) : ?>
            <a href="#" class="upt-premium-card__menu-item upt-toggle-status" data-item-id="<?php echo esc_attr( $post_id ); ?>" data-new-status="draft">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                Desativar
            </a>
            <?php else : ?>
            <a href="#" class="upt-premium-card__menu-item upt-toggle-status" data-item-id="<?php echo esc_attr( $post_id ); ?>" data-new-status="publish">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                Publicar
            </a>
            <?php endif; ?>
            <a href="#" class="upt-premium-card__menu-item upt-premium-card__menu-item--danger delete-item-ajax" data-item-id="<?php echo esc_attr( $post_id ); ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                Excluir
            </a>
        </div>
    </div>
    <?php
}
