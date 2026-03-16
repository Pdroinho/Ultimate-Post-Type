(function($) {
    'use strict';

    if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
        return;
    }

    var originalAttachmentsBrowser = wp.media.view.AttachmentsBrowser;
    var originalAttachmentDetails = wp.media.view.Attachment.Details;

    /**
     * Adiciona o filtro de pastas e o campo de criação de nova pasta na barra de ferramentas principal.
     */
    wp.media.view.AttachmentsBrowser = originalAttachmentsBrowser.extend({
        createToolbar: function() {
            originalAttachmentsBrowser.prototype.createToolbar.apply(this, arguments);
            var toolbar = this.toolbar;

            toolbar.set('media-folder-filter', new wp.media.view.AttachmentFilters['folder']({
                controller: this.controller,
                model: this.collection.props,
                priority: -80
            }));

            toolbar.set('upt-folder-management', {
                template: wp.template('upt-folder-manager'),
                priority: -75
            });

            this.controller.on('content:render', function() {
                var createButton = toolbar.$el.find('.new-folder-button');
                createButton.off('click').on('click', this.createNewFolder.bind(this));
            }, this);
        },

        createNewFolder: function(event) {
            event.preventDefault();
            var toolbar = this.toolbar;
            var input = toolbar.$el.find('.new-folder-name-input');
            var button = $(event.currentTarget);
            var folderName = input.val().trim();
            var filterDropdown = toolbar.get('media-folder-filter').$el.find('select');

            if (!folderName) {
                input.css('border-color', 'red');
                setTimeout(function() { input.css('border-color', ''); }, 2000);
                return;
            }

            button.prop('disabled', true).text('Criando...');

            wp.ajax.post('upt_create_media_folder', {
                nonce: upt_media_folders.nonce,
                folder_name: folderName
            }).done(function(response) {
                var newOption = $('<option>', { value: response.slug, text: response.name });
                filterDropdown.append(newOption);
                upt_media_folders.folders.push({term_id: response.term_id, name: response.name, slug: response.slug});
                filterDropdown.val(response.slug).trigger('change');
                input.val('');
            }).fail(function(response) {
                alert('Erro: ' + response.message);
            }).always(function() {
                button.prop('disabled', false).text('Criar');
            });
        }
    });

    /**
     * Cria a view do nosso dropdown de filtros de pasta.
     */
    wp.media.view.AttachmentFilters['folder'] = wp.media.view.AttachmentFilters.extend({
        id: 'media-attachment-folder-filter',
        className: 'attachment-filters attachment-folder-filter',

        createFilters: function() {
            var filters = {};
            _.each(upt_media_folders.folders || [], function(term) {
                filters[term.slug] = {
                    text: term.name,
                    props: {
                        [upt_media_folders.taxonomy]: term.slug
                    }
                };
            });

            filters.all = {
                text: 'Todas as pastas',
                props: {
                    [upt_media_folders.taxonomy]: false
                },
                priority: 10
            };
            this.filters = filters;
        }
    });

    /**
     * Adiciona o dropdown de pastas na barra lateral de detalhes da imagem.
     */
    wp.media.view.Attachment.Details = originalAttachmentDetails.extend({
        template: function(view) {
            var template = originalAttachmentDetails.prototype.template.apply(this, arguments);
            var customTemplate = wp.template('upt-attachment-details')(view);
            return template.replace(
                '<div class="attachment-compat">',
                customTemplate + '<div class="attachment-compat">'
            );
        },

        events: _.extend({}, originalAttachmentDetails.prototype.events, {
            'change .upt-folder-assign': 'assignToFolder'
        }),

        assignToFolder: function(event) {
            var folderId = $(event.currentTarget).val();
            var selection = this.controller.state().get('selection');
            var attachmentIds = selection.map(function(model) {
                return model.id;
            });
            
            if (attachmentIds.length === 0) {
                attachmentIds.push(this.model.get('id'));
            }
            
            var spinner = this.$el.find('.upt-folder-assign-spinner');
            spinner.css('visibility', 'visible');

            wp.ajax.post('upt_assign_to_folder', {
                nonce: upt_media_folders.nonce,
                attachment_ids: attachmentIds,
                folder_id: folderId
            }).always(function() {
                spinner.css('visibility', 'hidden');
            });
        }
    });

})(jQuery);
