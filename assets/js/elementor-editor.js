/**
 * Elementor Editor JavaScript for Zlaark Subscriptions
 */

jQuery(document).ready(function($) {
    
    // Wait for Elementor to be ready
    $(window).on('elementor:init', function() {
        
        // Add custom widget category icon
        elementor.hooks.addFilter('panel/elements/regionViews', function(regionViews) {
            if (regionViews.elements && regionViews.elements.view) {
                var originalView = regionViews.elements.view;
                regionViews.elements.view = originalView.extend({
                    onRender: function() {
                        originalView.prototype.onRender.apply(this, arguments);
                        this.addZlaarkCategoryIcon();
                    },
                    
                    addZlaarkCategoryIcon: function() {
                        var $zlaarkCategory = this.$el.find('[data-category="zlaark-subscriptions"]');
                        if ($zlaarkCategory.length) {
                            $zlaarkCategory.addClass('zlaark-category');
                        }
                    }
                });
            }
            return regionViews;
        });
        
        // Enhance widget previews
        elementor.hooks.addAction('panel/open_editor/widget', function(panel, model, view) {
            var widgetType = model.get('widgetType');
            
            if (widgetType && widgetType.indexOf('zlaark-') === 0) {
                enhanceZlaarkWidget(panel, model, view);
            }
        });
        
        // Add real-time preview updates
        elementor.channels.editor.on('change', function(controlView, elementView) {
            var widgetType = elementView.model.get('widgetType');
            
            if (widgetType && widgetType.indexOf('zlaark-') === 0) {
                updateZlaarkWidgetPreview(controlView, elementView);
            }
        });
        
        // Add widget insertion animations
        elementor.hooks.addAction('frontend/element_ready/widget', function($scope) {
            var widgetType = $scope.data('widget_type');
            
            if (widgetType && widgetType.indexOf('zlaark-') === 0) {
                $scope.addClass('zlaark-widget-animated');
                
                // Add loading state for AJAX-dependent widgets
                if (widgetType.indexOf('status') !== -1 || widgetType.indexOf('eligibility') !== -1) {
                    addLoadingState($scope);
                }
            }
        });
    });
    
    /**
     * Enhance Zlaark widget editing experience
     */
    function enhanceZlaarkWidget(panel, model, view) {
        var widgetType = model.get('widgetType');
        
        // Add helpful tooltips
        setTimeout(function() {
            addZlaarkTooltips(panel.$el, widgetType);
        }, 100);
        
        // Add product validation
        if (hasProductSelection(widgetType)) {
            validateProductSelection(panel, model);
        }
        
        // Add preview refresh button
        addPreviewRefreshButton(panel, widgetType);
    }
    
    /**
     * Add helpful tooltips to controls
     */
    function addZlaarkTooltips($panel, widgetType) {
        var tooltips = getWidgetTooltips(widgetType);
        
        Object.keys(tooltips).forEach(function(controlName) {
            var $control = $panel.find('[data-setting="' + controlName + '"]').closest('.elementor-control');
            if ($control.length) {
                var $title = $control.find('.elementor-control-title');
                if ($title.length && !$title.find('.zlaark-tooltip').length) {
                    $title.append('<span class="zlaark-tooltip" title="' + tooltips[controlName] + '">?</span>');
                }
            }
        });
        
        // Initialize tooltips
        $panel.find('.zlaark-tooltip').tooltip({
            position: { my: "left+15 center", at: "right center" },
            tooltipClass: "zlaark-tooltip-ui"
        });
    }
    
    /**
     * Get tooltips for widget controls
     */
    function getWidgetTooltips(widgetType) {
        var tooltips = {
            'product_id': 'Select the subscription product to display. Only published subscription products are shown.',
            'button_text': 'Customize the text displayed on the button. Leave empty for default text.',
            'redirect_url': 'Optional URL to redirect users after they click the button.',
            'show_eligibility': 'Display whether the current user is eligible for a trial before showing the button.',
            'show_pricing': 'Show pricing information above the button to help users understand the cost.',
            'layout': 'Choose how to display the pricing information: List (simple), Table (structured), or Cards (visual).',
            'show_trial': 'Include trial pricing information in the display.',
            'show_regular': 'Include regular subscription pricing in the display.',
            'show_reason': 'Show the reason why a user is not eligible for a trial (if applicable).',
            'show_details': 'Display detailed subscription information including dates and payment details.',
            'display_mode': 'Show status for a specific product or all user subscriptions.'
        };
        
        return tooltips;
    }
    
    /**
     * Check if widget has product selection
     */
    function hasProductSelection(widgetType) {
        var productWidgets = [
            'zlaark-trial-button',
            'zlaark-subscription-button', 
            'zlaark-subscription-pricing',
            'zlaark-trial-eligibility',
            'zlaark-subscription-details'
        ];
        
        return productWidgets.indexOf(widgetType) !== -1;
    }
    
    /**
     * Validate product selection
     */
    function validateProductSelection(panel, model) {
        var productId = model.get('settings').get('product_id');
        
        if (!productId) {
            showValidationMessage(panel.$el, 'Please select a subscription product to configure this widget.', 'warning');
        } else {
            // Validate that the product exists and is a subscription
            validateProduct(productId, function(isValid, message) {
                if (!isValid) {
                    showValidationMessage(panel.$el, message, 'error');
                } else {
                    hideValidationMessage(panel.$el);
                }
            });
        }
    }
    
    /**
     * Validate product via AJAX
     */
    function validateProduct(productId, callback) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'zlaark_validate_product',
                product_id: productId,
                nonce: elementorFrontendConfig.nonce || ''
            },
            success: function(response) {
                if (response.success) {
                    callback(true, '');
                } else {
                    callback(false, response.data || 'Invalid product selected.');
                }
            },
            error: function() {
                callback(false, 'Unable to validate product. Please check your selection.');
            }
        });
    }
    
    /**
     * Show validation message
     */
    function showValidationMessage($panel, message, type) {
        hideValidationMessage($panel);
        
        var className = 'zlaark-validation-' + type;
        var icon = type === 'error' ? '⚠️' : 'ℹ️';
        
        var $message = $('<div class="zlaark-validation-message ' + className + '">' +
                        '<span class="icon">' + icon + '</span>' +
                        '<span class="text">' + message + '</span>' +
                        '</div>');
        
        $panel.find('.elementor-panel-content').prepend($message);
    }
    
    /**
     * Hide validation message
     */
    function hideValidationMessage($panel) {
        $panel.find('.zlaark-validation-message').remove();
    }
    
    /**
     * Add preview refresh button
     */
    function addPreviewRefreshButton(panel, widgetType) {
        if (!panel.$el.find('.zlaark-refresh-preview').length) {
            var $refreshBtn = $('<button class="zlaark-refresh-preview elementor-button elementor-button-success">' +
                              '<i class="eicon-refresh"></i> Refresh Preview' +
                              '</button>');
            
            $refreshBtn.on('click', function(e) {
                e.preventDefault();
                refreshWidgetPreview(widgetType);
            });
            
            panel.$el.find('.elementor-panel-content').append($refreshBtn);
        }
    }
    
    /**
     * Refresh widget preview
     */
    function refreshWidgetPreview(widgetType) {
        // Find the widget in the preview
        var $widget = elementor.$previewContents.find('[data-widget_type="' + widgetType + '"]');
        
        if ($widget.length) {
            $widget.addClass('zlaark-widget-loading');
            
            // Simulate refresh (in real implementation, this would trigger a re-render)
            setTimeout(function() {
                $widget.removeClass('zlaark-widget-loading');
                
                // Trigger a re-render of the widget
                var elementView = elementor.getPreviewView().getChildView($widget.data('id'));
                if (elementView) {
                    elementView.renderHTML();
                }
            }, 1000);
        }
    }
    
    /**
     * Update widget preview in real-time
     */
    function updateZlaarkWidgetPreview(controlView, elementView) {
        var controlName = controlView.model.get('name');
        var widgetType = elementView.model.get('widgetType');
        
        // Handle specific control changes
        switch (controlName) {
            case 'product_id':
                handleProductChange(controlView, elementView);
                break;
            case 'button_text':
                handleButtonTextChange(controlView, elementView);
                break;
            case 'layout':
                handleLayoutChange(controlView, elementView);
                break;
        }
    }
    
    /**
     * Handle product selection change
     */
    function handleProductChange(controlView, elementView) {
        var productId = controlView.getControlValue();
        
        if (productId) {
            // Update preview with new product
            elementView.renderHTML();
            
            // Validate the new product
            validateProduct(productId, function(isValid, message) {
                if (!isValid) {
                    console.warn('Zlaark Subscriptions: ' + message);
                }
            });
        }
    }
    
    /**
     * Handle button text change
     */
    function handleButtonTextChange(controlView, elementView) {
        var newText = controlView.getControlValue();
        var $button = elementView.$el.find('.zlaark-trial-btn, .zlaark-subscription-btn');
        
        if ($button.length && newText) {
            $button.text(newText);
        }
    }
    
    /**
     * Handle layout change
     */
    function handleLayoutChange(controlView, elementView) {
        var newLayout = controlView.getControlValue();
        var $container = elementView.$el.find('.zlaark-subscription-pricing');
        
        if ($container.length) {
            // Remove old layout classes
            $container.removeClass('layout-list layout-table layout-cards');
            // Add new layout class
            $container.addClass('layout-' + newLayout);
        }
    }
    
    /**
     * Add loading state to widget
     */
    function addLoadingState($scope) {
        $scope.addClass('zlaark-widget-loading');
        
        // Remove loading state after a delay (simulating AJAX completion)
        setTimeout(function() {
            $scope.removeClass('zlaark-widget-loading');
        }, 1500);
    }
    
    /**
     * Add custom CSS for validation messages
     */
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .zlaark-validation-message {
                display: flex;
                align-items: center;
                padding: 10px 15px;
                margin: 10px 0;
                border-radius: 4px;
                font-size: 13px;
                line-height: 1.4;
            }
            
            .zlaark-validation-warning {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                color: #856404;
            }
            
            .zlaark-validation-error {
                background: #f8d7da;
                border: 1px solid #f5c6cb;
                color: #721c24;
            }
            
            .zlaark-validation-message .icon {
                margin-right: 8px;
                font-size: 16px;
            }
            
            .zlaark-refresh-preview {
                margin: 15px 0;
                width: 100%;
                text-align: center;
            }
            
            .zlaark-tooltip {
                display: inline-block;
                width: 16px;
                height: 16px;
                background: #007cba;
                color: white;
                border-radius: 50%;
                text-align: center;
                line-height: 16px;
                font-size: 10px;
                font-weight: bold;
                margin-left: 5px;
                cursor: help;
            }
            
            .zlaark-tooltip-ui {
                background: #333;
                color: white;
                border: none;
                border-radius: 4px;
                padding: 8px 12px;
                font-size: 12px;
                max-width: 250px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            }
        `)
        .appendTo('head');
});
