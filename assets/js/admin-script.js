(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Debug: Check if FAQ Generator AI is loaded
        if (typeof faqGenAI !== 'undefined') {
            console.log('FAQ Generator AI loaded:', faqGenAI);
        } else {
            console.error('FAQ Generator AI not loaded properly');
        }
        
        // Add visual indicator for supported post types
        if (typeof faqGenAI !== 'undefined' && faqGenAI.post_type) {
            var postTypeInfo = $('<div class="notice notice-info inline" style="margin: 10px 0; padding: 10px;"></div>')
                .html('<strong>FAQ Generator:</strong> Active for ' + faqGenAI.post_type.toUpperCase());
            
            $('.wp-heading-inline').after(postTypeInfo);
        }
    });
    
})(jQuery);
