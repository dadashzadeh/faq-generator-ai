(function($) {
    'use strict';
    
    /**
     * FAQ Accordion Functionality
     */
    var FAQAccordion = {
        
        init: function() {
            this.setupAccordion();
            this.addEventListeners();
            this.addAccessibility();
        },
        
        setupAccordion: function() {
            $('.faq-section .faq-item').each(function(index) {
                var $item = $(this);
                var $question = $item.find('.faq-question');
                var $answer = $item.find('.faq-answer');
                
                // Add data attributes
                $item.attr('data-index', index + 1);
                $question.attr('role', 'button');
                $question.attr('aria-expanded', 'false');
                $question.attr('tabindex', '0');
                $answer.attr('role', 'region');
                
                // Wrap answer content
                if (!$answer.find('.faq-answer-content').length) {
                    $answer.wrapInner('<div class="faq-answer-content"></div>');
                }
                
                // Open first 2 items
                if (index < 2) {
                    FAQAccordion.openItem($item);
                }
            });
        },
        
        addEventListeners: function() {
            // Click event
            $(document).on('click', '.faq-question', function(e) {
                e.preventDefault();
                var $item = $(this).closest('.faq-item');
                FAQAccordion.toggleItem($item);
            });
            
            // Keyboard navigation
            $(document).on('keydown', '.faq-question', function(e) {
                if (e.keyCode === 13 || e.keyCode === 32) { // Enter or Space
                    e.preventDefault();
                    $(this).trigger('click');
                }
            });
        },
        
        toggleItem: function($item) {
            if ($item.hasClass('active')) {
                this.closeItem($item);
            } else {
                this.openItem($item);
            }
        },
        
        openItem: function($item) {
            var $question = $item.find('.faq-question');
            var $answer = $item.find('.faq-answer');
            
            $item.addClass('active');
            $question.attr('aria-expanded', 'true');
            
            // Smooth scroll if needed
            if ($item.offset().top < $(window).scrollTop()) {
                $('html, body').animate({
                    scrollTop: $item.offset().top - 100
                }, 300);
            }
        },
        
        closeItem: function($item) {
            var $question = $item.find('.faq-question');
            
            $item.removeClass('active');
            $question.attr('aria-expanded', 'false');
        },
        
        addAccessibility: function() {
            // Add ARIA labels
            $('.faq-section').attr('role', 'list');
            $('.faq-item').attr('role', 'listitem');
        }
    };
    
    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        if ($('.faq-section').length) {
            FAQAccordion.init();
        }
    });
    
})(jQuery);
