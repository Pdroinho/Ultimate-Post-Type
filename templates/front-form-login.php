<?php
$upt_widget_settings = get_query_var('widget_settings', []);

// Tipo de cabeçalho: text, image ou none
$upt_login_header_type  = isset($upt_widget_settings['login_header_type']) ? $upt_widget_settings['login_header_type'] : 'text';
$upt_login_header_text  = isset($upt_widget_settings['login_header_text']) && $upt_widget_settings['login_header_text'] !== '' ? $upt_widget_settings['login_header_text'] : 'Entrar';
$upt_login_header_image = isset($upt_widget_settings['login_header_image']) ? $upt_widget_settings['login_header_image'] : [];
?>
<div class="upt-login-form">
    <?php if ($upt_login_header_type === 'image' && ! empty($upt_login_header_image) && ! empty($upt_login_header_image['url'])) : ?>
        <div class="form-logo">
            <img src="<?php echo esc_url($upt_login_header_image['url']); ?>" alt="<?php echo esc_attr($upt_login_header_text); ?>">
        </div>
    <?php elseif ($upt_login_header_type !== 'none') : ?>
        <h2 class="form-title"><?php echo esc_html($upt_login_header_text); ?></h2>
    <?php endif; ?>

    <?php if (isset($_GET['login_error'])) : ?>
        <p class="upt-error" style="background: #ffebe8; border: 1px solid #c00; padding: 10px; margin-bottom: 15px;">
            Usuário ou senha inválidos.
        </p>
    <?php endif; ?>

    <form name="loginform" action="" method="post">
    <form name="loginform" action="" method="post">
        <p>
            <label for="user_login">Usuário</label>
            <input type="text" name="log" id="user_login" class="input" value="" size="20" />
        </p>
        <p>
            <label for="user_pass">Senha</label>
            <input type="password" name="pwd" id="user_pass" class="input" value="" size="20" />
        </p>
        
        <div class="remember-me-toggle">
            <input name="rememberme" type="checkbox" id="rememberme" value="forever" />
            <label for="rememberme" class="toggle-switch"></label>
            <span class="toggle-label">Manter conectado</span>
        </div>

        <p class="submit">
            <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary" value="Acessar" />
        </p>
        
        <p class="restricted-access">Acesso restrito.</p>
        
        <?php // Campo de segurança Nonce e redirecionamento ?>
        <?php wp_nonce_field('upt_login', 'upt_login_nonce'); ?>
        <input type="hidden" name="redirect_to" value="<?php echo esc_attr($_SERVER['REQUEST_URI']); ?>" />
    </form>
</div>
