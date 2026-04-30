(function ($) {
  "use strict";

  function injectFields() {
    var $adder = $("#catalog_category-adder");
    if (!$adder.length) return;

    // Evita injeção duplicada
    if ($adder.find(".upt-subcats-metabox").length) return;

    // O WP usa um form interno para o adder. Precisamos colocar os campos dentro dele
    // para que o AJAX de criação de termo envie no POST.
    var $wrap = $(
      '<div class="upt-subcats-metabox" style="margin-top:8px;">' +
        '<label style="display:block; margin:6px 0;">' +
          '<input type="checkbox" id="upt_create_subcategories_mb" name="upt_create_subcategories" value="1" /> ' +
          'Criar subcategorias' +
        '</label>' +
        '<div class="upt-subcats-list" style="display:none;">' +
          '<label for="upt_subcategories_list" style="display:block; margin-bottom:4px;">Subcategorias (uma por linha)</label>' +
          '<textarea id="upt_subcategories_list" name="upt_subcategories_list" rows="4" style="width:100%;" placeholder="Ex:\nTráfego Pago\nSEO\nSocial Media"></textarea>' +
        '</div>' +
      '</div>'
    );

    // Inserir após o campo de nome da categoria (input#newcatalog_category)
    var $name = $adder.find("#newcatalog_category");
    if ($name.length) {
      $name.closest("p").after($wrap);
    } else {
      $adder.append($wrap);
    }

    // Nonce usado no handler PHP (mesmo da tela edit-tags)
    // Importante: precisa estar dentro do adder para ir no POST do AJAX.
    if (!$adder.find("input[name='upt_create_subcategories_nonce']").length) {
      // window.uptTaxSubcatsNonce pode existir se algum dia localizarmos; por enquanto, cria input vazio e
      // o PHP também aceita o nonce de wp_nonce_field da tela edit-tags. Aqui geramos via campo hidden a partir do DOM.
      // Melhor estratégia: tentar ler um nonce já impresso em edit-tags (não existe aqui). Então imprimimos o nonce via PHP?
      // Como não temos localize aqui, vamos aproveitar que o WP já inclui o nonce do adder (add-tag) e no PHP
      // não exigimos esse nonce? (Exigimos). Então precisamos enviar.
      // Solução: reutilizar o nonce do upt_ajax (já existe no front-end), mas não existe no admin.
      // Portanto, aqui criamos um hidden e o PHP fará fallback para o nonce do add-tag.
      // -> Ajuste no PHP (class-admin.php): aceitar _ajax_nonce-add-tag quando estiver no metabox.
      $adder.append('<input type="hidden" name="upt_create_subcategories_nonce" value="" />');
    }

    $adder.on("change", "#upt_create_subcategories_mb", function () {
      $adder.find(".upt-subcats-list").toggle(this.checked);
    });
  }

  $(document).ready(function () {
    injectFields();
  });
})(jQuery);
