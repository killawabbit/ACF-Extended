(function($){
    
    if(typeof acf === 'undefined')
        return;
    
    /*
     * Init
     */
    var flexible = acf.getFieldType('flexible_content');
    var model = flexible.prototype;
    
    /*
     * Actions
     */
    model.acfeOneClick = function(e, $el){
        
        // Get Flexible
        var flexible = this;
        
        // Vars
        var $clones = flexible.$clones();
        var $layout_name = $($clones[0]).data('layout');
        
        // Source
        var $layout_source = null;
        if($el.hasClass('acf-icon'))
            $layout_source = $el.closest('.layout');
        
        // Add
        var $layout_added = flexible.add({
            layout: $layout_name,
            before: $layout_source
        });
        
        // Hide native tooltip
        if($('.acf-fc-popup').length)
            $('.acf-fc-popup').hide();
        
    };
    
    model.acfeLayoutInit = function($layout){
        
        // Get Flexible
        var flexible = this;
        
        // Vars
        var $controls = $layout.find('> .acf-fc-layout-controls');
        var $handle = $layout.find('> .acf-fc-layout-handle');
        
        // Placeholder
        var $placeholder = $layout.find('> .acfe-fc-placeholder');
        
        // Placeholder: Show
        $placeholder.removeClass('acf-hidden');
        
        // If no modal edition & opened: Hide Placeholder
        if(!flexible.has('acfeFlexibleModalEdition') && !flexible.isLayoutClosed($layout)){
            
            $placeholder.addClass('acf-hidden');
        
        }
        
        // Flexible has Preview
        if(flexible.isLayoutClosed($layout) && flexible.has('acfeFlexiblePreview') && !$placeholder.hasClass('-loading')){
            
            $placeholder.addClass('acfe-fc-preview -loading').find('> .acfe-flexible-placeholder').prepend('<span class="spinner"></span>');
            $placeholder.find('> .acfe-fc-overlay').addClass('-hover');
            
            // vars
			var $input = $layout.children('input');
			var prefix = $input.attr('name').replace('[acf_fc_layout]', '');
			
			// ajax data
			var ajaxData = {
				action: 	'acfe/flexible/layout_preview',
				field_key: 	flexible.get('key'),
				i: 			$layout.index(),
				layout:		$layout.data('layout'),
				value:		acf.serialize($layout, prefix)
			};
            
            acf.doAction('acfe/fields/flexible_content/before_preview',                                                                     flexible.$el, $layout, ajaxData);
            acf.doAction('acfe/fields/flexible_content/before_preview/name=' + flexible.get('name'),                                        flexible.$el, $layout, ajaxData);
            acf.doAction('acfe/fields/flexible_content/before_preview/key=' + flexible.get('key'),                                          flexible.$el, $layout, ajaxData);
            acf.doAction('acfe/fields/flexible_content/before_preview/name=' + flexible.get('name') + '&layout=' + $layout.data('layout'),  flexible.$el, $layout, ajaxData);
            acf.doAction('acfe/fields/flexible_content/before_preview/key=' + flexible.get('key') + '&layout=' + $layout.data('layout'),    flexible.$el, $layout, ajaxData);
			
			// ajax
			$.ajax({
		    	url: acf.get('ajaxurl'),
		    	data: acf.prepareForAjax(ajaxData),
				dataType: 'html',
				type: 'post',
				success: function(response){
                    
					if(response){
                        
						$placeholder.find('> .acfe-flexible-placeholder').html(response);
                        
					}else{
                        
                        $placeholder.removeClass('acfe-fc-preview');
                        
                    }
                    
                    acf.doAction('acfe/fields/flexible_content/preview',                                                                     response, flexible.$el, $layout, ajaxData);
                    acf.doAction('acfe/fields/flexible_content/preview/name=' + flexible.get('name'),                                        response, flexible.$el, $layout, ajaxData);
                    acf.doAction('acfe/fields/flexible_content/preview/key=' + flexible.get('key'),                                          response, flexible.$el, $layout, ajaxData);
                    acf.doAction('acfe/fields/flexible_content/preview/name=' + flexible.get('name') + '&layout=' + $layout.data('layout'),  response, flexible.$el, $layout, ajaxData);
                    acf.doAction('acfe/fields/flexible_content/preview/key=' + flexible.get('key') + '&layout=' + $layout.data('layout'),    response, flexible.$el, $layout, ajaxData);
                    
				},
                complete: function(){
                    
                    $placeholder.find('> .acfe-fc-overlay').removeClass('-hover');
                    $placeholder.removeClass('-loading').find('> .acfe-flexible-placeholder > .spinner').remove();
                    
                }
			});
            
        }
        
    };
    
    model.acfeEditorsInit = function($layout){
        
        var flexible = this;
        
        // Closed
        if(flexible.isLayoutClosed($layout))
            return;
        
        // Try to find delayed WYSIWYG
        var editors = acf.getFields({
            'type': 'wysiwyg',
            'parent': $layout
        });
        
        if(!editors.length)
            return;
        
        $.each(editors, function(){
            
            var editor = this;
            var $wrap = editor.$control();
            
            if($wrap.hasClass('delay')){
                
                $wrap.removeClass('delay');
                $wrap.find('.acf-editor-toolbar').remove();
                
                // initialize
                editor.initializeEditor();
                
            }
            
        });
        
    };
    
    /*
     * Spawn
     */
    acf.addAction('new_field/type=flexible_content', function(flexible){
        
        // Vars
        var $clones = flexible.$clones();
        var $layouts = flexible.$layouts();
        
        // Merge
        var $all_layouts = $.merge($layouts, $clones);
        
        // Do Actions
        $all_layouts.each(function(){
            
            var $layout = $(this);
            var $name = $layout.data('layout');
            
            acf.doAction('acfe/flexible/layouts', $layout, flexible);
            acf.doAction('acfe/flexible/layout/name=' + $name, $layout, flexible);
            
        });
        
        // ACFE: 1 layout available - OneClick
        if($clones.length === 1){
            
            // Remove native ACF Tooltip action
            flexible.removeEvents({'click [data-name="add-layout"]': 'onClickAdd'});
            
            // Add ACF Extended Modal action
            flexible.addEvents({'click [data-name="add-layout"]': 'acfeOneClick'});
        
        }
        
        flexible.addEvents({'click .acfe-fc-placeholder': 'onClickCollapse'});
        
        flexible.addEvents({'click .acfe-flexible-opened-actions > a': 'onClickCollapse'});
        
        // Flexible: Ajax
        if(flexible.has('acfeFlexibleAjax')){
            
            flexible.add = function(args){
                
                // Get Flexible
                var flexible = this;
                
                // defaults
                args = acf.parseArgs(args, {
                    layout: '',
                    before: false
                });
                
                // validate
                if( !this.allowAdd() ) {
                    return false;
                }

                // ajax
                $.ajax({
                    url: acf.get('ajaxurl'),
                    data: acf.prepareForAjax({
                        action: 	'acfe/advanced_flexible_content/models',
                        field_key: 	this.get('key'),
                        layout:		args.layout,
                    }),
                    dataType: 'html',
                    type: 'post',
                    beforeSend: function(){
                        $('body').addClass('-loading');
                    },
                    success: function(html){
                        if(html){
                            
                            var $layout = $(html);
                            var uniqid = acf.uniqid();
                            
                            var search = 'acf[' + flexible.get('key') + '][acfcloneindex]';
                            var replace = flexible.$control().find('> input[type=hidden]').attr('name') + '[' + uniqid + ']';
                            
                            // add row
                            var $el = acf.duplicate({
                                target: $layout,
                                search: search,
                                replace: replace,
                                append: flexible.proxy(function( $el, $el2 ){
                                    
                                    // append
                                    if( args.before ) {
                                        args.before.before( $el2 );
                                    } else {
                                        flexible.$layoutsWrap().append( $el2 );
                                    }
                                    
                                    // enable 
                                    acf.enable( $el2, flexible.cid );
                                    
                                    // render
                                    flexible.render();
                                })
                            });
                            
                            // Fix data-id
                            $el.attr('data-id', uniqid);
                            
                            // trigger change for validation errors
                            flexible.$input().trigger('change');
                            
                            // return
                            return $el;
                            
                        }
                    },
                    'complete': function(){
                        $('body').removeClass('-loading');
                    }
                });
                
            };
            
        }
        
        // Flexible: Grid
        if(flexible.has('acfeFlexibleGrid')){
            
            flexible.getPopupHTML = function(){
                
                // vars
                var html = this.$popup().html();
                var $html = $(html);
                
                // count layouts
                var $layouts = this.$layouts();
                var countLayouts = function( name ){
                    return $layouts.filter(function(){
                        return $(this).data('layout') === name;
                    }).length;
                };
                
                var model = this;
                            
                // modify popup
                $html.find('[data-layout]').each(function(){
                    
                    // vars
                    var $a = $(this);
                    var min = $a.data('min') || 0;
                    var max = $a.data('max') || 0;
                    var name = $a.data('layout') || '';
                    var count = countLayouts( name );
                    
                    // max
                    if( max && count >= max) {
                        $a.addClass('disabled');
                        return;
                    }
                    
                    if(model.get('acfeFlexibleGridWrap')){
                        
                        var col = $a.data('col');
                        
                        if(col === 'auto')
                            col = 1;
                        
                        if(model.countCols() + parseInt(col) > 12){
                            
                            $a.addClass('disabled');
                            return;
                            
                        }
                        
                    }
                    
                    // min
                    if( min && count < min ) {
                        
                        // vars
                        var required = min - count;
                        var title = acf.__('{required} {label} {identifier} required (min {min})');
                        var identifier = acf._n('layout', 'layouts', required);
                                            
                        // translate
                        title = title.replace('{required}', required);
                        title = title.replace('{label}', name); // 5.5.0
                        title = title.replace('{identifier}', identifier);
                        title = title.replace('{min}', min);
                        
                        // badge
                        $a.append('<span class="badge" title="' + title + '">' + required + '</span>');
                    }
                });
                
                // update
                html = $html.outerHTML();
                
                // return
                return html;
            };
            
            flexible.countCols = function(){
                
                var count = 0;
                
                this.$layouts().each(function(){
                    
                    var col = $(this).data('col');

                    if(col === 'auto')
                        col = 1;
                    
                    count = count + col;
                    
                });
                
                return count;
                
            };
            
            flexible.isFull = function(){
                
                if(this.get('acfeFlexibleGridWrap') && this.countCols() >= 12)
                    return true;
                
                var max = parseInt( this.get('max') );
                return ( max && this.val() >= max );
            };
            
            flexible.render = function(){
                
                // update order number
                this.$layouts().each(function( i ){
                    $(this).find('.acf-fc-layout-order:first').html( i+1 );
                });
                
                // empty
                if( this.val() == 0 ) {
                    this.$control().addClass('-empty');
                } else {
                    this.$control().removeClass('-empty');
                }
                
                // max
                if( this.isFull() ) {
                    this.$button().addClass('disabled');
                    this.$control().find('> .acfe-flexible-stylised-button:first').addClass('disabled');
                } else {
                    this.$button().removeClass('disabled');
                    this.$control().find('> .acfe-flexible-stylised-button:first').removeClass('disabled');
                }
            };
            
            flexible.addSortable = function( self ){
                
                // bail early if max 1 row
                if( this.get('max') == 1 ) {
                    return;
                }
                
                // add sortable
                this.$layoutsWrap().sortable({
                    items: ' > .layout',
                    handle: '> .acf-fc-layout-handle',
                    forceHelperSize: false,
                    tolerance: "pointer",
                    forcePlaceholderSize: true,
                    scroll: false,
                    stop: function(event, ui) {
                        self.render();
                    },
                    update: function(event, ui) {
                        self.$input().trigger('change');
                    }
                });
                
            };
            
        }
        
        

    });
    
    acf.addAction('acfe/flexible/layouts', function($layout, flexible){
        
        // TinyMCE Init
        flexible.acfeEditorsInit($layout);
        
        // Closed
        if(flexible.isLayoutClosed($layout)){
        
            // Placeholder
            $layout.find('> .acfe-fc-placeholder').removeClass('acf-hidden');
        
        }
        
    });
    
    acf.addAction('show', function($layout, type){
        
        if(type !== 'collapse' || !$layout.is('.layout'))
            return;
        
        var flexible = acf.getInstance($layout.closest('.acf-field-flexible-content'));
        
        // TinyMCE Init
        flexible.acfeEditorsInit($layout);
        
        // Hide Placeholder
        if(!flexible.has('acfeFlexibleModalEdition')){
    
            // Placeholder
            $layout.find('> .acfe-fc-placeholder').addClass('acf-hidden');
        
        }
        
    });
    
    acf.addAction('hide', function($layout, type){
        
        if(type !== 'collapse' || !$layout.is('.layout') || $layout.is('.acf-clone'))
            return;
        
        // Get Flexible
        var flexible = acf.getInstance($layout.closest('.acf-field-flexible-content'));
        
        // Remove Ajax Title
        if(flexible.has('acfeFlexibleRemoveAjaxTitle')){

            flexible.renderLayout = function($layout){};

        }
        
        // Preview Ajax
        flexible.acfeLayoutInit($layout);
        
    });
    
    acf.addAction('append', function($el){
        
        // Bail early if layout is not layout
        if(!$el.is('.layout'))
            return;
        
        // Get Flexible
        var flexible = acf.getInstance($el.closest('.acf-field-flexible-content'));
        
        // Open Layout
        if(!$el.is('.acfe-layout-duplicated')){
            
            // Modal Edition: Open
            if(flexible.has('acfeFlexibleModalEdition')){
                
                $el.find('> [data-action="acfe-flexible-modal-edit"]:first').trigger('click');
                
            }
            
            // Normal Edition: Open
            else{
                
                flexible.openLayout($el);
                
            }
            
        }
        
        flexible.acfeLayoutInit($el);
        
        var $modal = flexible.$el.closest('.acfe-modal.-open');
        
        if($modal.length){
        
            // Scroll to new layout
            $modal.find('> .acfe-modal-wrapper > .acfe-modal-content').animate({
                scrollTop: parseInt($el.offset().top) - 200
            }, 200);
        
        }else{
            
            // Scroll to new layout
            $('html, body').animate({
                scrollTop: parseInt($el.offset().top) - 200
            }, 200);
            
        }
        
    });
    
    /*
     * Field Error
     */
    acf.addAction('invalid_field', function(field){
        
        field.$el.parents('.layout').addClass('acfe-flexible-modal-edit-error');
        
    });
    
    /*
     * Field Valid
     */
    acf.addAction('valid_field', function(field){
        
        field.$el.parents('.layout').each(function(){
            
            var $layout = $(this);
            
            if(!$layout.find('.acf-error').length)
                $layout.removeClass('acfe-flexible-modal-edit-error');
            
        });
        
    });
    
})(jQuery);