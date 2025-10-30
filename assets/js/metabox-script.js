(function($) {
    'use strict';
    
    var FAQMetabox = {
        
        hasChanges: false,
        
        init: function() {
            this.bindEvents();
            this.initSortable();
            this.bindSaveWarning();
        },
        
        bindEvents: function() {
            var self = this;
            
            // Add new FAQ
            $(document).on('click', '.faq-btn-add', function(e) {
                e.preventDefault();
                self.openModal('add');
            });
            
            // Edit FAQ
            $(document).on('click', '.faq-item-edit', function(e) {
                e.preventDefault();
                var $card = $(this).closest('.faq-item-card');
                var index = $card.data('index');
                var question = $card.data('question');
                var answer = $card.data('answer');
                self.openModal('edit', index, question, answer);
            });
            
            // Delete FAQ
            $(document).on('click', '.faq-item-delete', function(e) {
                e.preventDefault();
                if (confirm(faqSchemaMetabox.strings.confirm_delete)) {
                    var $card = $(this).closest('.faq-item-card');
                    self.deleteFAQ($card);
                }
            });
            
            // Delete all
            $(document).on('click', '.faq-btn-delete-all', function(e) {
                e.preventDefault();
                if (confirm(faqSchemaMetabox.strings.confirm_delete_all)) {
                    self.deleteAll();
                }
            });
            
            // Edit JSON
            $(document).on('click', '.faq-btn-edit-json', function(e) {
                e.preventDefault();
                self.openJSONEditor();
            });
            
            // Close JSON editor
            $(document).on('click', '.faq-json-close', function(e) {
                e.preventDefault();
                self.closeJSONEditor();
            });
            
            // Apply JSON changes
            $(document).on('click', '.faq-json-apply', function(e) {
                e.preventDefault();
                self.applyJSONChanges();
            });
            
            // Modal close
            $(document).on('click', '.faq-modal-close, .faq-modal-backdrop', function(e) {
                e.preventDefault();
                self.closeModal();
            });
            
            // Modal save
            $(document).on('click', '.faq-modal-save', function(e) {
                e.preventDefault();
                self.saveModal();
            });
            
            // ESC key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    if ($('#faq-modal').is(':visible')) {
                        self.closeModal();
                    }
                    if ($('.faq-json-editor').is(':visible')) {
                        self.closeJSONEditor();
                    }
                }
            });
        },
        
        initSortable: function() {
            var self = this;
            
            if ($.fn.sortable && $('#faq-sortable-list').length) {
                $('#faq-sortable-list').sortable({
                    handle: '.faq-item-drag',
                    placeholder: 'faq-item-placeholder',
                    tolerance: 'pointer',
                    cursor: 'move',
                    update: function(event, ui) {
                        self.updateIndices();
                        self.updateHiddenField();
                        self.markAsChanged();
                    }
                });
            }
        },
        
        bindSaveWarning: function() {
            var self = this;
            
            // Warn before leaving page
            $(window).on('beforeunload', function() {
                if (self.hasChanges) {
                    return faqSchemaMetabox.strings.unsaved;
                }
            });
            
            // Remove warning when saving
            $('form#post').on('submit', function() {
                self.hasChanges = false;
            });
        },
        
        openModal: function(mode, index, question, answer) {
            var $modal = $('#faq-modal');
            
            if (mode === 'add') {
                $('#faq-modal-title').text('Add New FAQ');
                $('#faq-modal-save-text').text('Add FAQ');
                $('#faq-modal-question').val('');
                $('#faq-modal-answer').val('');
                $('#faq-modal-index').val('');
            } else {
                $('#faq-modal-title').text('Edit FAQ');
                $('#faq-modal-save-text').text('Save Changes');
                $('#faq-modal-question').val(question);
                $('#faq-modal-answer').val(answer);
                $('#faq-modal-index').val(index);
            }
            
            $modal.fadeIn(200);
            $('#faq-modal-question').focus();
        },
        
        closeModal: function() {
            $('#faq-modal').fadeOut(200);
        },
        
        saveModal: function() {
            var self = this;
            var question = $('#faq-modal-question').val().trim();
            var answer = $('#faq-modal-answer').val().trim();
            var index = $('#faq-modal-index').val();
            
            if (!question || !answer) {
                self.showMessage('error', faqSchemaMetabox.strings.required_fields);
                return;
            }
            
            if (index !== '') {
                // Edit existing
                self.updateFAQ(index, question, answer);
            } else {
                // Add new
                self.addNewFAQ(question, answer);
            }
            
            self.closeModal();
            self.showMessage('success', 'FAQ updated. Click "Update" to save.');
        },
        
        updateFAQ: function(index, question, answer) {
            var $card = $('.faq-item-card[data-index="' + index + '"]');
            
            // Update DOM
            $card.data('question', question);
            $card.data('answer', answer);
            $card.find('.faq-item-question strong').text(question);
            $card.find('.faq-item-answer').text(this.truncateText(answer, 20));
            
            // Update hidden field
            this.updateHiddenField();
            this.markAsChanged();
        },
        
        addNewFAQ: function(question, answer) {
            var self = this;
            var index = $('.faq-item-card').length;
            var $newCard = this.createFAQCard(index, question, answer);
            
            // If no items, remove empty state and create structure
            if ($('.faq-empty-state').length) {
                $('.faq-empty-state').remove();
                
                // Create toolbar
                var $toolbar = $(this.createToolbar());
                $('.faq-metabox-wrapper').prepend($toolbar);
                
                // Create items container
                var $container = $('<div class="faq-items-container"><div class="faq-items-list" id="faq-sortable-list"></div></div>');
                $('.faq-metabox-wrapper').append($container);
                
                this.initSortable();
            }
            
            $('#faq-sortable-list').append($newCard);
            this.updateCount();
            this.updateIndices();
            this.updateHiddenField();
            this.markAsChanged();
        },
        
        deleteFAQ: function($card) {
            var self = this;
            
            $card.fadeOut(300, function() {
                $(this).remove();
                
                // Check if all deleted
                if ($('.faq-item-card').length === 0) {
                    // Show empty state instead of reload
                    self.showEmptyState();
                } else {
                    self.updateCount();
                    self.updateIndices();
                    self.updateHiddenField();
                    self.markAsChanged();
                }
                
                self.showMessage('success', 'FAQ deleted. Click "Update" to save.');
            });
        },
        
        deleteAll: function() {
            var self = this;
            
            // Remove all cards
            $('.faq-item-card').fadeOut(300, function() {
                $(this).remove();
            });
            
            // Wait for animation then show empty state
            setTimeout(function() {
                self.showEmptyState();
                self.showMessage('success', 'All FAQs deleted. Click "Update" to save.');
            }, 350);
        },
        
        showEmptyState: function() {
            // Remove toolbar and container
            $('.faq-toolbar').remove();
            $('.faq-items-container').remove();
            $('.faq-json-editor').remove();
            
            // Add empty state
            var $emptyState = $(
                '<div class="faq-empty-state">' +
                    '<div class="faq-empty-icon">' +
                        '<span class="dashicons dashicons-editor-help"></span>' +
                    '</div>' +
                    '<h3>No FAQs Yet</h3>' +
                    '<p>Start by adding your first FAQ or use the "Generate FAQ" button in the editor.</p>' +
                    '<button type="button" class="button button-primary button-hero faq-btn-add">' +
                        '<span class="dashicons dashicons-plus-alt"></span> Add First FAQ' +
                    '</button>' +
                '</div>'
            );
            
            $('.faq-metabox-wrapper').append($emptyState);
            
            // Clear hidden field
            $('#faq-schema-data').val('');
            
            // Mark as changed
            this.hasChanges = true;
            $('.faq-status-badge').hide();
        },
        
        openJSONEditor: function() {
            var currentSchema = this.buildSchemaFromDOM();
            $('#faq-json-textarea').val(JSON.stringify(currentSchema, null, 2));
            
            $('.faq-items-container, .faq-toolbar').hide();
            $('.faq-json-editor').slideDown();
        },
        
        closeJSONEditor: function() {
            $('.faq-json-editor').slideUp();
            $('.faq-toolbar, .faq-items-container').fadeIn();
        },
        
        applyJSONChanges: function() {
            var self = this;
            var json = $('#faq-json-textarea').val().trim();
            
            try {
                var schema = JSON.parse(json);
                
                // Validate
                if (!schema['@type'] || schema['@type'] !== 'FAQPage') {
                    throw new Error('Must be FAQPage type');
                }
                
                if (!schema.mainEntity || !Array.isArray(schema.mainEntity)) {
                    throw new Error('mainEntity must be an array');
                }
                
                // Rebuild DOM
                this.rebuildFromJSON(schema);
                this.closeJSONEditor();
                this.showMessage('success', 'JSON applied. Click "Update" to save.');
                
            } catch(e) {
                this.showMessage('error', 'Invalid JSON: ' + e.message);
            }
        },
        
        rebuildFromJSON: function(schema) {
            var self = this;
            $('#faq-sortable-list').empty();
            
            $.each(schema.mainEntity, function(index, item) {
                var question = item.name || '';
                var answer = item.acceptedAnswer ? item.acceptedAnswer.text : '';
                var $card = self.createFAQCard(index, question, answer);
                $('#faq-sortable-list').append($card);
            });
            
            this.updateCount();
            this.updateIndices();
            this.updateHiddenField();
            this.markAsChanged();
        },
        
        createFAQCard: function(index, question, answer) {
            var truncatedAnswer = this.truncateText(answer, 20);
            
            return $('<div class="faq-item-card" data-index="' + index + '" data-question="' + this.escapeHtml(question) + '" data-answer="' + this.escapeHtml(answer) + '">' +
                '<div class="faq-item-drag"><span class="dashicons dashicons-menu"></span></div>' +
                '<div class="faq-item-number">' + (index + 1) + '</div>' +
                '<div class="faq-item-content">' +
                    '<div class="faq-item-question"><strong>' + this.escapeHtml(question) + '</strong></div>' +
                    '<div class="faq-item-answer">' + this.escapeHtml(truncatedAnswer) + '</div>' +
                '</div>' +
                '<div class="faq-item-actions">' +
                    '<button type="button" class="faq-item-btn faq-item-edit" title="Edit"><span class="dashicons dashicons-edit"></span></button>' +
                    '<button type="button" class="faq-item-btn faq-item-delete" title="Delete"><span class="dashicons dashicons-trash"></span></button>' +
                '</div>' +
            '</div>');
        },
        
        createToolbar: function() {
            return '<div class="faq-toolbar">' +
                '<div class="faq-toolbar-left">' +
                    '<span class="faq-count-badge"><span class="dashicons dashicons-list-view"></span> <strong id="faq-count-number">0</strong> FAQs</span>' +
                    '<span class="faq-status-badge" style="display: none;"><span class="dashicons dashicons-warning"></span> Unsaved changes</span>' +
                '</div>' +
                '<div class="faq-toolbar-right">' +
                    '<button type="button" class="button faq-btn-add"><span class="dashicons dashicons-plus-alt"></span> Add New</button>' +
                    '<button type="button" class="button faq-btn-edit-json"><span class="dashicons dashicons-editor-code"></span> Edit JSON</button>' +
                    '<button type="button" class="button button-link-delete faq-btn-delete-all"><span class="dashicons dashicons-trash"></span> Delete All</button>' +
                '</div>' +
            '</div>';
        },
        
        buildSchemaFromDOM: function() {
            var mainEntity = [];
            
            $('.faq-item-card').each(function() {
                var question = $(this).data('question');
                var answer = $(this).data('answer');
                
                mainEntity.push({
                    '@type': 'Question',
                    'name': question,
                    'acceptedAnswer': {
                        '@type': 'Answer',
                        'text': answer
                    }
                });
            });
            
            return {
                '@context': 'https://schema.org',
                '@type': 'FAQPage',
                'mainEntity': mainEntity
            };
        },
        
        updateHiddenField: function() {
            var schema = this.buildSchemaFromDOM();
            
            // If no items, set empty value
            if (schema.mainEntity.length === 0) {
                $('#faq-schema-data').val('');
            } else {
                var schemaJSON = JSON.stringify(schema);
                $('#faq-schema-data').val(schemaJSON);
            }
        },
        
        updateIndices: function() {
            $('.faq-item-card').each(function(index) {
                $(this).data('index', index);
                $(this).attr('data-index', index);
                $(this).find('.faq-item-number').text(index + 1);
            });
        },
        
        updateCount: function() {
            var count = $('.faq-item-card').length;
            $('#faq-count-number').text(count);
        },
        
        markAsChanged: function() {
            this.hasChanges = true;
            $('.faq-status-badge').fadeIn();
        },
        
        truncateText: function(text, words) {
            if (!text) return '';
            var wordArray = text.split(' ');
            if (wordArray.length > words) {
                return wordArray.slice(0, words).join(' ') + '...';
            }
            return text;
        },
        
        escapeHtml: function(text) {
            if (!text) return '';
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
        },
        
        showMessage: function(type, message) {
            var $msg = $('.faq-message');
            var className = type === 'success' ? 'faq-message-success' : 'faq-message-error';
            
            $msg.removeClass('faq-message-success faq-message-error')
                .addClass(className)
                .html('<p>' + message + '</p>')
                .slideDown();
            
            setTimeout(function() {
                $msg.slideUp();
            }, 4000);
        }
    };
    
    // Initialize
    $(document).ready(function() {
        if ($('.faq-metabox-wrapper').length) {
            FAQMetabox.init();
        }
    });
    
})(jQuery);
