(function () {
    tinymce.PluginManager.add('faq_generator_ai', function (editor, url) {

        // Add button to editor
        editor.addButton('faq_generator_ai', {
            text: 'Generate FAQ',
            icon: 'help',
            onclick: function () {
                // Check if faqGenAI is defined
                if (typeof faqGenAI === 'undefined') {
                    editor.notificationManager.open({
                        text: 'Plugin configuration error. Please refresh the page.',
                        type: 'error',
                        timeout: 3000
                    });
                    return;
                }

                var helpText = 'FAQ will be inserted as shortcode [faq_display] for easy management.';
                if (faqGenAI.seo_integrated) {
                    helpText += ' Schema will be integrated with your SEO plugin.';
                }

                // Open dialog
                var win = editor.windowManager.open({
                    title: 'Generate FAQ with AI',
                    width: 600,
                    height: 450,
                    body: [
                        {
                            type: 'listbox',
                            name: 'faq_count',
                            label: 'Number of FAQs',
                            values: [
                                { text: '3 FAQs', value: '3' },
                                { text: '4 FAQs', value: '4' },
                                { text: '5 FAQs (Recommended)', value: '5' },
                                { text: '6 FAQs', value: '6' },
                                { text: '7 FAQs', value: '7' },
                                { text: '8 FAQs', value: '8' },
                                { text: '9 FAQs', value: '9' },
                                { text: '10 FAQs', value: '10' }
                            ],
                            value: String(faqGenAI.default_count || 5)
                        },
                        {
                            type: 'listbox',
                            name: 'output_format',
                            label: 'Output Format',
                            values: [
                                { text: 'HTML + FAQ Schema (Recommended)', value: 'both' },
                                { text: 'HTML Only', value: 'html' },
                                { text: 'FAQ Schema Only', value: 'schema' }
                            ],
                            value: faqGenAI.output_format || 'both'
                        },
                        {
                            type: 'checkbox',
                            name: 'use_shortcode',
                            label: 'Use Shortcode Mode',
                            text: 'Insert [faq_display] shortcode (recommended for easy editing)',
                            checked: true
                        },
                        {
                            type: 'textbox',
                            name: 'prompt',
                            label: 'Prompt',
                            multiline: true,
                            minHeight: 120,
                            value: faqGenAI.default_prompt || 'Generate FAQs based on: [content]',
                            placeholder: 'Use shortcodes: [content], [title], [excerpt]'
                        },
                        {
                            type: 'container',
                            html: '<div style="padding:10px;background:#e7f5ff;border-radius:4px;margin-top:10px;font-size:12px;"><strong>💡 Tip:</strong> ' + helpText + '</div>'
                        }
                    ],
                    onsubmit: function (e) {
                        // Get form data
                        var formData = e.data;

                        // Close the dialog
                        win.close();

                        // Show loading notification
                        var loadingNotif = editor.notificationManager.open({
                            text: 'Generating FAQs... Please wait.',
                            type: 'info',
                            timeout: 0,
                            closeButton: false
                        });

                        // Get post ID
                        var postId = 0;
                        if (jQuery('#post_ID').length) {
                            postId = jQuery('#post_ID').val();
                        } else if (jQuery('input[name="post_ID"]').length) {
                            postId = jQuery('input[name="post_ID"]').val();
                        }

                        // Make AJAX request
                        jQuery.ajax({
                            url: faqGenAI.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'faq_generate_ai',
                                nonce: faqGenAI.nonce,
                                prompt: formData.prompt,
                                post_id: postId,
                                output_format: formData.output_format,
                                faq_count: parseInt(formData.faq_count),
                                use_shortcode: formData.use_shortcode ? 'true' : 'false'
                            },
                            success: function (response) {
                                // Close loading notification
                                loadingNotif.close();

                                if (response.success && response.data) {

                                    // ✅ Insert content (shortcode or HTML)
                                    if (response.data.content) {
                                        editor.insertContent(response.data.content);
                                    }

                                    // ✅ Update metabox if schema exists
                                    if (response.data.schema && jQuery('#faq-schema-data').length) {
                                        jQuery('#faq-schema-data').val(response.data.schema);

                                        // Reload metabox to show new FAQs
                                        if (typeof window.faqMetaboxReload === 'function') {
                                            window.faqMetaboxReload();
                                        } else {
                                            // Simple page reload notice
                                            jQuery('.faq-status-badge').show().html('<span class="dashicons dashicons-yes-alt"></span> FAQs generated - Save post to see changes in metabox');
                                        }
                                    }

                                    // Show success message
                                    var message = 'FAQ generated successfully! (' + formData.faq_count + ' items)';
                                    if (response.data.use_shortcode) {
                                        message += ' Shortcode [faq_display] inserted.';
                                    }
                                    if (response.data.seo_integrated) {
                                        message += ' Schema integrated with SEO plugin.';
                                    }

                                    editor.notificationManager.open({
                                        text: message,
                                        type: 'success',
                                        timeout: 6000
                                    });

                                    // Highlight save button
                                    jQuery('#publishing-action .button-primary').addClass('button-primary-highlight');
                                    setTimeout(function () {
                                        jQuery('#publishing-action .button-primary').removeClass('button-primary-highlight');
                                    }, 3000);

                                } else {
                                    // Show error message
                                    var errorMsg = 'Failed to generate FAQ.';
                                    if (response.data && typeof response.data === 'string') {
                                        errorMsg = response.data;
                                    } else if (response.data && response.data.message) {
                                        errorMsg = response.data.message;
                                    }

                                    editor.notificationManager.open({
                                        text: 'Error: ' + errorMsg,
                                        type: 'error',
                                        timeout: 8000
                                    });
                                }
                            },
                            error: function (xhr, status, error) {
                                // Close loading notification
                                loadingNotif.close();

                                // Show error
                                var errorMsg = 'Connection error';
                                if (xhr.responseJSON && xhr.responseJSON.data) {
                                    errorMsg = xhr.responseJSON.data;
                                } else if (error) {
                                    errorMsg = error;
                                }

                                editor.notificationManager.open({
                                    text: 'Error: ' + errorMsg,
                                    type: 'error',
                                    timeout: 8000
                                });

                                console.error('FAQ Generation Error:', {
                                    status: status,
                                    error: error,
                                    response: xhr.responseText
                                });
                            },
                            complete: function () {
                                // Ensure loading notification is closed
                                setTimeout(function () {
                                    if (loadingNotif) {
                                        loadingNotif.close();
                                    }
                                }, 100);
                            }
                        });
                    }
                });
            }
        });

        // Add menu item (optional)
        editor.addMenuItem('faq_generator_ai', {
            text: 'Generate FAQ',
            icon: 'help',
            context: 'tools',
            onclick: function () {
                editor.buttons.faq_generator_ai.onclick();
            }
        });
    });
})();
