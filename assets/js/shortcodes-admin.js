/**
 * Shortcode Admin JavaScript
 */

jQuery(document).ready(function($) {
    
    // Search functionality
    $('#shortcode-search').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        $('.shortcode-card').each(function() {
            const card = $(this);
            const shortcodeName = card.find('h3').text().toLowerCase();
            const description = card.find('.shortcode-description p').text().toLowerCase();
            const shortcodeKey = card.data('shortcode').toLowerCase();
            
            if (shortcodeName.includes(searchTerm) || 
                description.includes(searchTerm) || 
                shortcodeKey.includes(searchTerm)) {
                card.removeClass('hidden');
            } else {
                card.addClass('hidden');
            }
        });
    });
    
    // Copy shortcode functionality
    $('.copy-shortcode-btn').on('click', function() {
        const shortcode = '[' + $(this).data('shortcode') + ']';
        copyToClipboard(shortcode);
        showCopyFeedback('Basic shortcode copied!');
    });
    
    // Copy usage example functionality
    $('.copy-usage-btn').on('click', function() {
        const usage = $(this).data('usage');
        copyToClipboard(usage);
        showCopyFeedback('Usage example copied!');
    });
    
    // Copy generated shortcode functionality
    $(document).on('click', '.copy-generated-btn', function() {
        const generatedCode = $('#generated-code').text();
        copyToClipboard(generatedCode);
        showCopyFeedback('Generated shortcode copied!');
    });
    
    // Preview shortcode functionality
    $('.preview-shortcode').on('click', function() {
        const shortcodeKey = $(this).data('shortcode');
        showPreviewModal(shortcodeKey);
    });
    
    // Generate custom shortcode functionality
    $('.generate-shortcode').on('click', function() {
        const shortcodeKey = $(this).data('shortcode');
        showGeneratorModal(shortcodeKey);
    });
    
    // Modal close functionality
    $('.modal-close').on('click', function() {
        $(this).closest('.zlaark-modal').hide();
    });
    
    // Close modal when clicking outside
    $('.zlaark-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    // Close modal with Escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('.zlaark-modal').hide();
        }
    });
    
    /**
     * Copy text to clipboard
     */
    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text);
        } else {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            document.execCommand('copy');
            textArea.remove();
        }
    }
    
    /**
     * Show copy feedback
     */
    function showCopyFeedback(message) {
        const feedback = $('<div class="copy-feedback">' + message + '</div>');
        $('body').append(feedback);
        
        setTimeout(function() {
            feedback.fadeOut(300, function() {
                feedback.remove();
            });
        }, 2000);
    }
    
    /**
     * Show preview modal
     */
    function showPreviewModal(shortcodeKey) {
        const modal = $('#shortcode-preview-modal');
        const previewContent = $('#preview-content');
        
        // Show loading
        previewContent.html('<p>Loading preview...</p>');
        modal.show();
        
        // Make AJAX request to get preview
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'zlaark_preview_shortcode',
                shortcode: shortcodeKey,
                nonce: zlaarkShortcodesAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    previewContent.html(response.data);
                } else {
                    previewContent.html('<p class="error">Preview not available: ' + response.data + '</p>');
                }
            },
            error: function() {
                previewContent.html('<p class="error">Failed to load preview.</p>');
            }
        });
    }
    
    /**
     * Show generator modal
     */
    function showGeneratorModal(shortcodeKey) {
        const modal = $('#shortcode-generator-modal');
        const generatorForm = $('#generator-form');
        
        // Get shortcode data
        const shortcodeData = getShortcodeData(shortcodeKey);
        
        if (!shortcodeData) {
            alert('Shortcode data not found');
            return;
        }
        
        // Build form
        let formHTML = '<h3>Generate: ' + shortcodeData.name + '</h3>';
        formHTML += '<p>' + shortcodeData.description + '</p>';
        
        if (shortcodeData.parameters && Object.keys(shortcodeData.parameters).length > 0) {
            formHTML += '<div class="generator-form">';
            
            Object.keys(shortcodeData.parameters).forEach(function(paramName) {
                const param = shortcodeData.parameters[paramName];
                formHTML += '<div class="generator-form-group">';
                formHTML += '<label for="param-' + paramName + '">' + paramName;
                if (param.required) {
                    formHTML += ' <span class="required">*</span>';
                }
                formHTML += '</label>';
                
                if (param.type === 'boolean') {
                    formHTML += '<select id="param-' + paramName + '" name="' + paramName + '">';
                    formHTML += '<option value="true">True</option>';
                    formHTML += '<option value="false"' + (param.default === 'false' ? ' selected' : '') + '>False</option>';
                    formHTML += '</select>';
                } else if (param.type === 'integer') {
                    formHTML += '<input type="number" id="param-' + paramName + '" name="' + paramName + '" value="' + (param.default || '') + '">';
                } else {
                    formHTML += '<input type="text" id="param-' + paramName + '" name="' + paramName + '" value="' + (param.default || '') + '" placeholder="' + param.description + '">';
                }
                
                formHTML += '<div class="description">' + param.description + '</div>';
                formHTML += '</div>';
            });
            
            formHTML += '</div>';
        }
        
        generatorForm.html(formHTML);
        
        // Update generated shortcode on form change
        updateGeneratedShortcode(shortcodeKey);
        
        // Bind change events
        generatorForm.on('input change', function() {
            updateGeneratedShortcode(shortcodeKey);
        });
        
        modal.show();
    }
    
    /**
     * Update generated shortcode
     */
    function updateGeneratedShortcode(shortcodeKey) {
        let shortcode = '[' + shortcodeKey;
        
        const formData = {};
        $('#generator-form input, #generator-form select').each(function() {
            const name = $(this).attr('name');
            const value = $(this).val();
            if (name && value) {
                formData[name] = value;
            }
        });
        
        if (Object.keys(formData).length > 0) {
            const params = Object.keys(formData).map(function(key) {
                return key + '="' + formData[key] + '"';
            }).join(' ');
            shortcode += ' ' + params;
        }
        
        shortcode += ']';
        
        $('#generated-code').text(shortcode);
    }
    
    /**
     * Get shortcode data (this would normally come from PHP)
     */
    function getShortcodeData(shortcodeKey) {
        // This is a simplified version - in a real implementation,
        // this data would be passed from PHP via wp_localize_script
        const shortcodes = {
            'trial_button': {
                name: 'Trial Button',
                description: 'Displays a trial subscription button for a specific product',
                parameters: {
                    'product_id': { required: true, type: 'integer', description: 'The ID of the subscription product' },
                    'text': { required: false, type: 'string', default: 'Start Free Trial', description: 'Custom button text' },
                    'class': { required: false, type: 'string', default: 'trial-button zlaark-trial-btn', description: 'Additional CSS classes' },
                    'style': { required: false, type: 'string', default: '', description: 'Inline CSS styles' },
                    'redirect': { required: false, type: 'string', default: '', description: 'URL to redirect after adding to cart' }
                }
            },
            'subscription_button': {
                name: 'Subscription Button',
                description: 'Displays a regular subscription button for a specific product',
                parameters: {
                    'product_id': { required: true, type: 'integer', description: 'The ID of the subscription product' },
                    'text': { required: false, type: 'string', default: 'Subscribe Now', description: 'Custom button text' },
                    'class': { required: false, type: 'string', default: 'subscription-button zlaark-subscription-btn', description: 'Additional CSS classes' },
                    'style': { required: false, type: 'string', default: '', description: 'Inline CSS styles' },
                    'redirect': { required: false, type: 'string', default: '', description: 'URL to redirect after adding to cart' }
                }
            },
            'subscription_pricing': {
                name: 'Subscription Pricing Display',
                description: 'Shows pricing information for a subscription product',
                parameters: {
                    'product_id': { required: true, type: 'integer', description: 'The ID of the subscription product' },
                    'show_trial': { required: false, type: 'boolean', default: 'true', description: 'Whether to show trial pricing' },
                    'show_regular': { required: false, type: 'boolean', default: 'true', description: 'Whether to show regular pricing' },
                    'layout': { required: false, type: 'string', default: 'list', description: 'Display layout: "list", "table", or "cards"' }
                }
            },
            'trial_eligibility': {
                name: 'Trial Eligibility Check',
                description: 'Shows whether the current user is eligible for a trial',
                parameters: {
                    'product_id': { required: true, type: 'integer', description: 'The ID of the subscription product' },
                    'show_reason': { required: false, type: 'boolean', default: 'false', description: 'Whether to show the reason if not eligible' }
                }
            },
            'user_subscription_status': {
                name: 'User Subscription Status',
                description: 'Shows the current user\'s subscription status for a product',
                parameters: {
                    'product_id': { required: false, type: 'integer', description: 'Specific product ID (optional, shows all if omitted)' },
                    'show_details': { required: false, type: 'boolean', default: 'false', description: 'Whether to show detailed subscription information' }
                }
            },
            'subscription_details': {
                name: 'Subscription Details',
                description: 'Displays detailed information about a subscription product',
                parameters: {
                    'product_id': { required: true, type: 'integer', description: 'The ID of the subscription product' },
                    'show_trial': { required: false, type: 'boolean', default: 'true', description: 'Whether to show trial information' },
                    'show_billing': { required: false, type: 'boolean', default: 'true', description: 'Whether to show billing information' }
                }
            }
        };
        
        return shortcodes[shortcodeKey] || null;
    }
});
