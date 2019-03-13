// Layouts admin JS
var pm_layout_path = '';
var pn_outfit_names = {};
var xxl_section = 0;
var pn_access_role = '';
var pn_layouts_previews = Array();

jQuery( document ).ready( function( $ ) {
	if ( 0 !== $( '#pn_ticker_list' ).length ) {
		$( '#pn_ticker_list' ).accordion( { collapsible: true, active: false, heightStyle: "content" } );
	}
	if ( 'advertising' != pn_access_role ) {
		if ( 0 !== $( '#pn_outfit_boxes' ).length ) {
			$( '#pn_outfit_boxes' ).accordion( { collapsible: true, heightStyle:"content" } );
			$( '#pn_outfit_boxes' ).sortable( {
				cursor: 'move',
				update: function() {
					var order = jQuery( '#pn_outfit_boxes' ).sortable( 'toArray' );
					cnt_ = 0;
					for ( var x = 0; x < order.length; x ++ ) {
						if ( order[ x ].indexOf( '_outfit_box_' ) > 0 ) {
							var id_ = order[ x ].replace( 'pn_outfit_box_', '' );
							jQuery( '#pmlay_order_' + id_ ).val( cnt_ );
							cnt_ ++;
						}
					}
				}
			} );
		}
	}
	pn_layouts_label_outfits();
	$( '.pmlay_tmpl' ).change( function() { pn_layouts_label_outfits() } );
	$( '.pmlay_tmpl' ).on( 'focus', function( e ) {
		pm_clear_template_classes();
	} );
	$( '.pmlay_sponscat' ).on( 'focus', function( e ) {
		pn_highlight_native_ads( $( this ) );
		pm_clear_template_classes();
	} );
	$( '.pmlay_sponscat' ).on( 'blur', function( e ) {
		pn_unhighlight_native_ads();
		pm_clear_template_classes();
	} );
	$( '.pmlay_sponskw' ).on( 'focus', function( e ) {
		pn_highlight_native_ads( $( this ) );
		pm_clear_template_classes();
	} );
	$( '.pmlay_sponskw' ).on( 'blur', function( e ) {
		pn_unhighlight_native_ads();
	} );
	$( '.pmlay_spons_check' ).on( 'mouseover', function( e ) {
		pn_highlight_native_ads( $( this ) );
		pm_clear_template_classes();
	} );
	$( '.pmlay_spons_check' ).on( 'mouseout', function( e ) {
		pn_unhighlight_native_ads();
	} );
	$( '.pmlay_spons_check' ).on( 'change', function( e ) {
		if ( $( this ).prop( 'checked' ) ) {
			// if trying to check a sponsored box, look to see if there are already two checked (max)
			var ad_count_ = 0;
			$( '.pmlay_spons_check' ).each( function( i, obj ) {
				if ( obj.checked ) {
					ad_count_ ++;
				}
			} );
			if ( 2 < ad_count_ ) {
				$( this ).prop( 'checked', false );
				alert( 'You have reached the maximum of two eligible native ad positions. Please deselect another outfit to make this position eligible.' );
			}
		}
	} );
	$( '.pmlay_sponscat' ).bind( 'change', function( e ) {
		var $obj = $( this );
		var fld_ = $obj.prop( 'id' ).replace( 'pmlay_sponscat_', '' );
		var val_ = $obj.prop( 'value' );
		$( '#pmlay_sponskw_' + fld_ ).val( val_ );
	} );
	$( '.pmlay_sponskw' ).bind( 'keyup', function( e ) {
		var $obj = $( this );
		var fld_ = $obj.prop( 'id' ).replace( 'pmlay_sponskw_', '' );
		$( '#pmlay_sponscat_' + fld_ ).val( '' );
	} );
	if ( 0 !== $( '#pmlay_show_xxl_ad' ).length ) {
		$( '#pmlay_show_xxl_ad' ).bind( 'change', function( e ) {
			var $obj = $( this );
			var fld_ = $obj.prop( 'value' ) * 1;
			if ( ( 1 === fld_ ) || ( 3 === fld_ ) ) {
				$( '#pmlay_tmpl_0' ).val( xxl_section );
				pm_json_show_template( 0 );
			}
		} );
	}
	// BEGIN WIDGET SEARCH HANDLERS
	$( 'select.pmlay_type' ).bind( 'change', function( e ) {
		var $obj = $( this );
		var fld_ = $obj.prop( 'id' ).replace( /\-type$/, '-listid' );
		var base_ = fld_.replace( /[0-9]+[\-\_a-z]+$/, '' );
		var num_ = fld_.replace( base_, '' ).replace( /[\-\_a-z]+$/, '' );
		var kw_ = $( '#' + base_ + num_ + '-listid' ).val();
		var tax_ = $obj.val();
		var fldnum_ = base_.replace( 'pmlay_type_', '' );
		if ( 'rss' === tax_ || 'shar' === tax_ ) {
			$( '#pmlay_thumbs_div_' + fldnum_ ).css( 'display', 'block' );
		} else {
			$( '#pmlay_thumbs_div_' + fldnum_ ).css( 'display', 'none' );
		}
		if ( 3 === tax_.length && tax_.includes( 'ug' ) ) {
			$( '#pmlay_searchbox2_' + fldnum_ ).css( 'display', 'block' );
			$( '#pmlay_show_2_' + fldnum_ ).css( 'display', 'block' );
		} else {
			$( '#pmlay_searchbox2_' + fldnum_ ).css( 'display', 'none' );
			$( '#pmlay_show_2_' + fldnum_ ).css( 'display', 'none' );
		}
		if ( 'cat' === tax_ || 'cax' === tax_ || 'tag' === tax_ ) {
			$( '#pmlay_source_div_' + fldnum_ ).css( 'display', 'block' );
		} else{
			$( '#pmlay_source_div_' + fldnum_ ).css( 'display', 'none' );
		}
		//pmlay_source_0_1
		var mode_ = '';
		if ( $obj.attr('value') != 'cust' ) {
			pm_json_get_terms( fld_, kw_, tax_, mode_ );
		}
	} );
	$( 'input.pmlay_search' ).bind( 'keyup',function( e ){
		var $obj = $( this );
		var fld_ = $obj.prop( 'id' );
		var base_ = fld_.replace( /[0-9]+[\-\_a-z]+$/, '' );
		var num_ = fld_.replace( base_, '' ).replace( /[\-\_a-z]+$/, '' );
		var kw_ = $obj.val();
		var tax_ = $( '#' + base_ + num_ + '-type' ).val();
		var mode_ = '';
		pm_json_get_terms( fld_, kw_, tax_, mode_ );
	} );
	// END WIDGET SEARCH HANDLERS
	// BEGIN INDEX PAGE SEARCH HANDLERS
	$( 'select.pmlay_type_idx' ).bind( 'change', function( e ) {
		var $obj = $( this );
		var fld_ = $obj.prop( 'id' ).replace( 'pmlay_type_', '' );
		var tax_ = $obj.val();
		document.getElementById( 'pmlay_search_' + fld_ ).style.visibility = 'visible';
		var kw_ = document.getElementById( 'pmlay_search_' + fld_ ).value;
		pm_set_term( fld_, '', '' ); // clear the previous selection
		var mode_ = '';
		pm_json_get_terms( fld_, kw_, tax_, mode_ );
	} );
	$( 'input.pmlay_search_idx' ).bind( 'keyup', function( e ) {
		var $obj = $( this );
		$obj.prop( 'id' )
		var fld_ = $obj.prop( 'id' ).replace( 'pmlay_search_', '' );
		var mode_ = '';
		if ( fld_.includes( 'pmlay_search2_' ) ) {
			fld_ = $obj.prop( 'id' ).replace( 'pmlay_search2_', '' );
			mode_ = '2_';
		}
		//pmlay_search2_
		var select_ = document.getElementById( 'pmlay_type_' + fld_ );
		if ( '' === select_.value ) {
			select_.value = 'cat';
		}
		var kw_ = $obj.val();
		var tax_ = select_.value;
		pm_json_get_terms( fld_, kw_, tax_, mode_ );
	} );
	// END INDEX PAGE SEARCH HANDLERS

	$( 'select.pmlay_tmpl' ).bind( 'change', function( e ) {
		// display the matching section template
		var num_ = 0;
		var $obj = $( this );
		id_ = $obj.prop( 'id' );
		num_ = id_.replace( 'pmlay_tmpl_', '' );
		pm_layouts_show_sponsored_form( num_ );
		pm_layouts_show_horizontal_list_settings( num_ );
		pm_json_show_template( num_ );
	} );
	// foreach outfit as num_ '#pn_outfit_boxes .pn_outfit_box'
	if ( 0 !== $( '#pn_outfit_boxes' ).length ) {
		jQuery( '#pn_outfit_boxes .pn_outfit_box' ).each( function() {
		var $obj = $( this );
		id_ = $obj.prop( 'id' );
		num_ = id_.replace( 'pn_outfit_box_', '' );
		pm_layouts_show_sponsored_form( num_ );
		pm_layouts_show_horizontal_list_settings( num_ );
	} );
	}
	//
	// find pmlay_header in section template pmadmin_section#
	$( 'input.pmlay_header' ).bind( 'keyup',function( e ){
		// show the header in the template that this list connects to
		var $obj = $( this );
		var num_ = $obj.prop( 'id' ).replace( 'pmlay_header_', '' );
		num_ = num_.replace( 'pmlay_headurl_', '' );
		pn_set_header_text( num_ );
	} );
	$( 'input.pmlay_header' ).bind( 'focus', function( e ){
		// show the header in the template that this list connects to
		var $obj = $( this );
		var num_ = $obj.prop( 'id' ).replace( 'pmlay_header_', '' );
		num_ = num_.replace( 'pmlay_headurl_','' );
		var ary_ = num_.split( '_' );
		pm_clear_template_classes();
		$( '#pmlay_template_section_' + ary_[0] ).find( '.pmadmin_title_' + ary_[1] ).addClass( 'pmadmin_head_act' );
		$( '#pmlay_phone_section_' + ary_[0] ).find( '.pmadmin_title_' + ary_[1] ).addClass( 'pmadmin_head_act' );
	} );
	$( 'select.pmlay_style' ).bind( 'focus', function( e ){
		// set the header style in the template
		var $obj = $( this );
		var num_ = $obj.prop( 'id' ).replace( 'pmlay_style_', '' );
		var ary_ = num_.split( '_' );
		pm_clear_template_classes();
		$( '#pmlay_template_section_' + ary_[0] ).find( '.pmadmin_title_' + ary_[1] ).addClass( 'pmadmin_head_act' );
		$( '#pmlay_phone_section_' + ary_[0] ).find( '.pmadmin_title_' + ary_[1] ).addClass( 'pmadmin_head_act' );
	} );
	$( 'select.pmlay_type' ).bind( 'focus', function( e ){
		// show the module in the template that this list connects to
		var $obj = $(this);
		var num_ = $obj.prop( 'id' ).replace( 'pmlay_type_','' );
		num_ = num_.replace( 'pmlay_target_','' );
		var ary_ = num_.split( '_' );
		pm_clear_template_classes();
		$( '#pmlay_template_section_' + ary_[0] ).find( '.pmadmin_module_' + ary_[1] ).addClass( 'pmadmin_mod_act' );
		$( '#pmlay_phone_section_' + ary_[0] ).find( '.pmadmin_module_' + ary_[1] ).addClass( 'pmadmin_mod_act' );
	} );
	$( 'input.pmlay_search_idx' ).bind( 'focus', function( e ){
		// show the module in the template that this list connects to
		var $obj = $(this);
		var num_ = $obj.prop( 'id' ).replace( 'pmlay_search_', '' );
		var ary_ = num_.split( '_' );
		pm_clear_template_classes();
		$( '#pmlay_template_section_' + ary_[0] ).find( '.pmadmin_module_' + ary_[1] ).addClass( 'pmadmin_mod_act' );
		$( '#pmlay_phone_section_' + ary_[0] ).find( '.pmadmin_module_' + ary_[1] ).addClass( 'pmadmin_mod_act' );
	} );
	$( 'input.pmlay_video_input' ).bind( 'focus', function( e ){
		// show the video in the template that this video connects to
		var $obj = $( this );
		var num_ = $obj.prop( 'id' ).replace( 'pmlay_videoid_', '' );
		var ary_ = num_.split( '_' );
		pm_clear_template_classes();
		$( '#pmlay_template_section_' + ary_[0] ).find( '.pmadmin_video_' + ary_[1] ).addClass( 'pmadmin_video_act' );
		$( '#pmlay_phone_section_' + ary_[0] ).find( '.pmadmin_video_' + ary_[1] ).addClass( 'pmadmin_video_act' );
	} );
	$( 'input.pmlay_video_input' ).bind( 'keyup', function( e ){
		// show the video in the template that this video connects to
		var $obj = $( this );
		var num_ = $obj.prop( 'id' ).replace( 'pmlay_videoid_', '' );
		pn_set_video_class( num_ );
	} );
	$( 'select.pmlay_widget_input' ).bind( 'focus', function( e ){
		// show the widget in the template that this widget connects to
		var $obj = $( this );
		var num_ = $obj.prop( 'id' ).replace( 'pmlay_widget_id_', '' );
		var ary_ = num_.split( '_' );
		pm_clear_template_classes();
		$( '#pmlay_template_section_' + ary_[0] ).find( '.pmadmin_widget_' + ary_[1] ).addClass( 'pmadmin_widget_act' );
		$( '#pmlay_phone_section_' + ary_[0] ).find( '.pmadmin_widget_' + ary_[1] ).addClass( 'pmadmin_widget_act' );
	} );
	$( 'select.pmlay_widget_input' ).bind( 'change', function( e ){
		// hide the ad in the template that this widget connects to
		var $obj = $( this );
		var num_ = $obj.prop( 'id' ).replace( 'pmlay_widget_id_', '' );
		pn_set_widget_class( num_ );
	} );
	$( 'input.pmlay_labels' ).bind( 'change', function( e ){
		// show the category labels in the template that this list connects to
		var $obj = $( this );
		var num_ = $obj.prop( 'id' ).replace( 'pmlay_labels_', '' );
		num_ = num_.replace( 'pmlay_target_', '' );
		var ary_ = num_.split( '_' );
		pm_clear_template_classes();
		pn_set_category_class( num_ );
	} );

	// Prevent configuring layouts for configurations other than Nexus.
	$( '#edittag' ).on( 'load_configuration', function( e, configurationId ) {
		var box = $( '#pmlay_box' );
		if ( configurationId ) {
			box.hide();
			pm_layouts_deactivate_list( box );
		} else {
			box.show();
			pm_layouts_activate_list( box );
		}
	});

	// When check box on widget in admin to indicate list is an advertorial, unhide those fields
} );


function pn_set_header_text( num_ ) {
	var ary_ = num_.split( '_' );
	if ( ( '' === jQuery( '#pmlay_header_' + num_ ).val() ) || ( '' === jQuery( '#pmlay_headurl_' + num_ ).val() ) ) {
		jQuery( 'div#pmlay_template_section_' + ary_[0] ).find( '.pmadmin_title_' + ary_[1] ).html( '' );
		jQuery( 'div#pmlay_phone_section_' + ary_[0] ).find( '.pmadmin_title_' + ary_[1] ).html( '' );
	} else {
		jQuery( 'div#pmlay_template_section_' + ary_[0] ).find( '.pmadmin_title_' + ary_[1] ).html( jQuery( '#pmlay_header_' + num_ ).val() );
		jQuery( 'div#pmlay_phone_section_' + ary_[0] ).find( '.pmadmin_title_' + ary_[1] ).html( jQuery( '#pmlay_header_' + num_ ).val() );
	}
}

function pn_set_category_class( num_ ) {
	var ary_ = num_.split( '_' );
	if ( false === jQuery( '#pmlay_labels_' + num_ ).is( ':checked' ) ) {
		jQuery( '#pmlay_template_section_' + ary_[0] ).find( '.pmadmin_module_' + ary_[1] ).removeClass( 'pmadmin_show_cat' );
		jQuery( '#pmlay_phone_section_' + ary_[0] ).find( '.pmadmin_module_' + ary_[1] ).removeClass( 'pmadmin_show_cat' );
	} else {
		jQuery( '#pmlay_template_section_' + ary_[0] ).find( '.pmadmin_module_' + ary_[1] ).addClass( 'pmadmin_show_cat' );
		jQuery( '#pmlay_phone_section_' + ary_[0] ).find( '.pmadmin_module_' + ary_[1] ).addClass( 'pmadmin_show_cat' );
	}
}

function pn_set_video_class( num_ ) {
	if ( '' !== num_) {
		var ary_ = num_.split( '_' );
		if ( 'undefined' !== typeof jQuery( '#pmlay_videoid_' + num_ ).val() ) {
			if ( ( '' === jQuery( '#pmlay_videoid_' + num_ ).val() ) || ( '*' === jQuery( '#pmlay_videoid_' + num_ ).val().substring(0,1) ) ) {
				jQuery( '#pmlay_template_section_' + ary_[0] ).find( '.pmadmin_video_' + ary_[1] ).parents( '.box_sizing' ).removeClass( 'pmadmin_show_vid' );
				jQuery( '#pmlay_template_section_' + ary_[0] ).find( '.pmadmin_video_' + ary_[1] ).parents( '.box_sizing' ).removeClass( 'pmadmin_show_vid' );

				jQuery( '#pmlay_template_section_' + ary_[0] ).find( '.pmadmin_video_' + ary_[1] ).parents( '.box_sizing' ).addClass( 'pmadmin_hide_vid' );

				jQuery( '#pmlay_phone_section_' + ary_[0] ).find( '.pmadmin_video_' + ary_[1] ).parents( '.box_sizing' ).removeClass( 'pmadmin_show_vid' );
				jQuery( '#pmlay_phone_section_' + ary_[0] ).find( '.pmadmin_video_' + ary_[1] ).parents( '.box_sizing' ).removeClass( 'pmadmin_show_vid' );

				jQuery( '#pmlay_phone_section_' + ary_[0] ).find( '.pmadmin_video_' + ary_[1] ).parents( '.box_sizing' ).addClass( 'pmadmin_hide_vid' );
			} else {
				jQuery( '#pmlay_template_section_' + ary_[0] ).find( '.pmadmin_video_' + ary_[1] ).parents( '.box_sizing' ).addClass( 'pmadmin_show_vid' );
				jQuery( '#pmlay_template_section_' + ary_[0] ).find( '.pmadmin_video_' + ary_[1] ).parents( '.box_sizing' ).addClass( 'pmadmin_show_vid' );

				jQuery( '#pmlay_template_section_' + ary_[0] ).find( '.pmadmin_video_' + ary_[1] ).parents( '.box_sizing' ).removeClass( 'pmadmin_hide_vid' );

				jQuery( '#pmlay_phone_section_' + ary_[0] ).find( '.pmadmin_video_' + ary_[1] ).parents( '.box_sizing' ).addClass( 'pmadmin_show_vid' );
				jQuery( '#pmlay_phone_section_' + ary_[0] ).find( '.pmadmin_video_' + ary_[1] ).parents( '.box_sizing' ).addClass( 'pmadmin_show_vid' );

				jQuery( '#pmlay_phone_section_' + ary_[0] ).find( '.pmadmin_video_' + ary_[1] ).parents( '.box_sizing' ).removeClass( 'pmadmin_hide_vid' );
			}
		}
	}
}

function pn_set_widget_class( num_ ) {
	var ary_ = num_.split( '_' );
	var select_value = jQuery( '#pmlay_widget_id_' + num_ ).val();
	if ( 'none' === select_value ) {
		jQuery( '#pmlay_template_section_' + ary_[0] ).find( '.pmadmin_widget_' + ary_[1] ).parents( '.box_sizing' ).addClass( 'pmadmin_hide_ad' );
		jQuery( '#pmlay_template_section_' + ary_[0] ).find( '.pmadmin_widget_' + ary_[1] ).parents( '.box_sizing' ).addClass( 'pmadmin_hide_ad' );

		jQuery( '#pmlay_template_section_' + ary_[0] ).find( '.pmadmin_widget_' + ary_[1] ).parents( '.box_sizing' ).removeClass( 'active_widget_'+ ary_[1] );

		jQuery( '#pmlay_phone_section_' + ary_[0] ).find( '.pmadmin_widget_' + ary_[1] ).parents( '.box_sizing' ).addClass( 'pmadmin_hide_ad' );
		jQuery( '#pmlay_phone_section_' + ary_[0] ).find( '.pmadmin_widget_' + ary_[1] ).parents( '.box_sizing' ).addClass( 'pmadmin_hide_ad' );

		jQuery( '#pmlay_phone_section_' + ary_[0] ).find( '.pmadmin_widget_' + ary_[1] ).parents( '.box_sizing' ).removeClass( 'active_widget_'+ ary_[1] );
	} else {
		jQuery( '#pmlay_template_section_' + ary_[0] ).find( '.pmadmin_widget_' + ary_[1] ).parents( '.box_sizing' ).removeClass( 'pmadmin_hide_ad' );
		jQuery( '#pmlay_template_section_' + ary_[0] ).find( '.pmadmin_widget_' + ary_[1] ).parents( '.box_sizing' ).removeClass( 'pmadmin_hide_ad' );

		jQuery( '#pmlay_template_section_' + ary_[0] ).find( '.pmadmin_widget_' + ary_[1] ).parents( '.box_sizing' ).addClass( 'active_widget_'+ ary_[1] );

		jQuery( '#pmlay_phone_section_' + ary_[0] ).find( '.pmadmin_widget_' + ary_[1] ).parents( '.box_sizing' ).removeClass( 'pmadmin_hide_ad' );
		jQuery( '#pmlay_phone_section_' + ary_[0] ).find( '.pmadmin_widget_' + ary_[1] ).parents( '.box_sizing' ).removeClass( 'pmadmin_hide_ad' );

		jQuery( '#pmlay_phone_section_' + ary_[0] ).find( '.pmadmin_widget_' + ary_[1] ).parents( '.box_sizing' ).addClass( 'active_widget_'+ ary_[1] );
	}
}

function pn_layouts_label_outfits() {
	for ( var x = 0; x < jQuery( '#sections_max' ).val(); x ++ ) {
		var outfit_num_ = jQuery( '#pmlay_tmpl_' + x ).val();
		var outfit_ = '';
		switch ( outfit_num_ ) {
			case -1:
			case '-1':
				outfit_ = '[ None ]';
				break;
			default:
				outfit_ = pn_outfit_names[ outfit_num_ ];
				break;
		}
		jQuery( '#pn_outfit_title_' + x ).text( ': ' + outfit_ );
	}
}

function pn_highlight_native_ads( obj_ ) {
	var id_ = obj_.prop( 'id' ).replace( 'pmlay_sponscat_', '' );
	jQuery( '.native_ad' ).css( 'border', '1px solid red' );
}

function pn_unhighlight_native_ads() {
	jQuery( '.native_ad' ).css( 'border', '0px' );
}

function pm_lookup_terms( obj_, mod_ ) {
	if ( 'kw' == mod_ ) {
		var fld_ = obj_.id.replace( 'pmlay_search_', '' );
		var sel_ = fld_.replace( '-listid', '-type' );
		var kw_ = obj_.value;
	} else {
		var fld_ = obj_.id.replace( 'pmlay_search_', '' );
		var sel_ = fld_.replace( '-listid', '-type' );
		var kw_ = document.getElementById( fld_ ).value;
	}
	var tax_ = document.getElementById( sel_ ).value;
	var mode_ = '';
	pm_json_get_terms( fld_, kw_, tax_, mode_ );
}

function pm_clear_template_classes() {
	jQuery( '#pm_layouts_form' ).find( '.pmadmin_title' ).removeClass( 'pmadmin_head_act' );
	jQuery( '#pm_layouts_form' ).find( '.pmadmin_module' ).removeClass( 'pmadmin_mod_act' );
	jQuery( '#pm_layouts_form' ).find( '.pmadmin_video' ).removeClass( 'pmadmin_video_act' );
	jQuery( '#pm_layouts_form' ).find( '.pmadmin_widget' ).removeClass( 'pmadmin_widget_act' );
}

function pm_set_term( fld_, id_, term_, mode_ ) {
	var display_ = '';
	var name_ = '';
	var css_ = 'none';
	var typ_ = jQuery( '#pmlay_type_' + fld_ ).val();
	if ( '' !== term_ ) {
		display_ = ( '' != term_ ) ? '<span>' + term_ + '</span>' : '';
		css_ = 'block';
		if ( 'cat' === typ_ || 'cax' === typ_ || 'tag' === typ_ ) {
			name_ = term_;
		}
	}
	jQuery( '#pmlay_id_' + mode_ + fld_ ).val( id_ );
	jQuery( '#pmlay_name_' + mode_ + fld_ ).val( name_ );
	jQuery( '#pmlay_show_' + mode_ + fld_ ).html( display_ );
	jQuery( '#pmlay_show_' + mode_ + fld_ ).css( 'display', css_ );
	jQuery( '#pmlay_opts_' + fld_ ).html( '' );
}

function pm_json_show_template( section_ ) {
	var id_ = jQuery( '#pmlay_tmpl_' + section_ ).val();
	jQuery( '#pmlay_template_section_' + section_ ).html( '' ); // hide the preview on desktop
	jQuery( '#pmlay_phone_section_' + section_ ).html( '' ); // hide the preview on mobile
	pm_layouts_deactivate_list( jQuery( '#pmlay_wrap_' + section_ ).find( '.pmlay_head' ) ); // hide all headings
	pm_layouts_deactivate_list( jQuery( '#pmlay_wrap_' + section_ ).find( '.pmlay_list' ) ); // hide all lists
	pm_layouts_deactivate_list( jQuery( '#pmlay_wrap_' + section_ ).find( '.pmlay_video' ) ); // hide all video
	pm_layouts_deactivate_list( jQuery( '#pmlay_wrap_' + section_ ).find( '.pmlay_widget' ) ); // hide all video
	pm_layouts_deactivate_list( jQuery( '#pmlay_wrap_' + section_ ).find( '.pmlay_labels' ) ); // hide all cat lablels
	if ( 'undefined' !== typeof id_ ) {
		if ( 'undefined' !== typeof( pn_layouts_previews[ id_ ] ) ) {
			pm_layouts_display_preview( section_, pn_layouts_previews[ id_ ] );
		} else {
			var data = {
				action: 'json_pmlay_showtemplate',
				nonce: jQuery( '#pm_layout_noncename' ).val(),
				num: id_,
			};
			jQuery.post( ajaxurl, data, function( response ) {
				data_ = JSON.parse( response );
				pn_layouts_previews[ id_ ] = data_;
				pm_layouts_display_preview( section_, data_ );
			} );
		}
	}
}

function pm_layouts_display_preview( section_, data_ ) {
	if ( 'undefined' !== typeof data_.html ) {
		jQuery( '#pmlay_template_section_' + section_ ).html( data_.html );
		jQuery( '#pmlay_phone_section_' + section_ ).html( data_.phone );
		jQuery( '#list_count_' + section_ ).val( data_.modules );
		for ( var x = 0; x < data_.modules; x ++ ) {
			var num_ = section_ + '_' + x;
			pm_layouts_activate_list( jQuery( '#pmlay_wrap_' + section_ ).find( '#pmlay_listbox_' + section_ + '_' + x ) );
			pn_set_category_class( num_ );
		}
		for ( var x = 0; x < data_.headers; x ++ ) {
			var num_ = section_ + '_' + x;
			var header_ = jQuery( '#pmlay_header_' + num_ ).val();
			jQuery( '#pmlay_template_section_' + section_ ).find( '.pmadmin_title_' + x ).html( header_ );
			jQuery( '#pmlay_phone_section_' + section_ ).find( '.pmadmin_title_' + x ).html( header_ );
			pm_layouts_activate_list( jQuery( '#pmlay_wrap_' + section_ ).find( '#pmlay_headbox_' + num_ ) );
			pn_set_header_text( num_ );
		}
		for ( var x = 0; x < data_.videos; x ++ ) {
			var num_ = section_ + '_' + x;
			pm_layouts_activate_list( jQuery( '#pmlay_wrap_' + section_ ).find( '#pmlay_videobox_' + num_ ) );
			pn_set_video_class( num_ );
		}
		for ( var x = 0; x < data_.widgets; x ++ ) {
			var num_ = section_ + '_' + x;
			pm_layouts_activate_list( jQuery( '#pmlay_wrap_' + section_ ).find( '#pmlay_widgetbox_' + num_ ) );
			pn_set_widget_class( num_ );
		}
		if ( 0 === data_.videos * 1 ) {
			jQuery( '#pmlay_videobox_' + section_ ).css( 'display', 'none' );
		} else {
			jQuery( '#pmlay_videobox_' + section_ ).css( 'display', 'table-row' );
		}
		if ( 0 < data_.labels * 1 ) {
			pm_layouts_activate_list( jQuery( '#pmlay_wrap_' + section_ ).find( '.pmlay_labels' ) ); // display all cat lablels
		}
	}
}
function pm_set_term_with_event( event ) {
	pm_set_term( event.data.fld_, event.data.id_, event.data.term_, event.data.mode_ );
}
function pm_json_get_terms( fld_, kw_, tax_, mode_ ) {
	var unsearchableTerms = ['rss','shar','you'];
	if ( unsearchableTerms.indexOf(tax_) === -1 ) {
		var data = {
			action: 'json_pmlay_termsearch',
			nonce: jQuery( '#pm_layout_noncename' ).val(),
			fld: fld_,
			kw: kw_,
			taxonomy: tax_,
			mode: mode_
		};
		jQuery.post( ajaxurl, data, function( response ) {
			data_ = JSON.parse( response );
			if ( 'undefined' !== typeof data_.terms ) {
				var count_ = data_.terms.length;
				var fld_ = data_.fld;
				var opt_container = jQuery( '#pmlay_opts_' + fld_ );

				if ( 'undefined' !== opt_container ) {
					var opt;
					var opt_text;

					opt_container.empty();

					for ( var cnt_ = 0; cnt_ < count_; cnt_++ ) {
						opt_text = data_.terms[cnt_][1];
						if ( 0 <= data_.terms[cnt_][2] * 1 ) {
							opt_text += ' (' + data_.terms[cnt_][2] + ')';
						}

						opt = jQuery( '<a>' );
						opt.click( {'fld_': fld_, 'id_': data_.terms[cnt_][0], 'term_': data_.terms[cnt_][1].replace( "'", "\\'" ), 'mode_': mode_}, pm_set_term_with_event );
						opt.text( opt_text );

						opt_container.append( opt );
					}
				}
			}
		} );
	} else {
		jQuery( '#pmlay_id_' + fld_ ).val( kw_ );
	}
}

function pn_set_layouts_preview( mode_ ) {
	if ( 0 === mode_ ) {
		jQuery( '.desktop_template' ).css( 'display', 'none' );
		jQuery( '.mobile_template' ).css( 'display', 'block' );
		jQuery( '.set_desktop_preview' ).removeClass( 'layouts_preview_active' );
		jQuery( '.set_mobile_preview' ).addClass( 'layouts_preview_active' );
	} else {
		jQuery( '.desktop_template' ).css( 'display', 'block' );
		jQuery( '.mobile_template' ).css( 'display', 'none' );
		jQuery( '.set_desktop_preview' ).addClass( 'layouts_preview_active' );
		jQuery( '.set_mobile_preview' ).removeClass( 'layouts_preview_active' );
	}
}

function pm_layouts_show_sponsored_form( num_ ) {
	text_ = jQuery( '#pmlay_tmpl_' + num_ ).find( 'option:selected' ).text();
	spons_ = text_.indexOf( '** ' );
	if ( 0 === spons_) {
		jQuery( '#pmlay_sponsored_form_' + num_ ).css( 'display', 'table-row' );
	} else {
		jQuery( '#pmlay_sponsored_form_' + num_ ).css( 'display', 'none' );
	}
}

function pm_layouts_show_horizontal_list_settings( num_ ) {
	selected = jQuery( '#pmlay_tmpl_' + num_ ).find( 'option:selected' ).val();

	if ( 'hl' === selected ) {
		jQuery( '#pn_outfit_box_' + num_ + ' .horizontal-lists-only' ).css( 'display', 'table-row' );
	} else {
		jQuery( '#pn_outfit_box_' + num_ + ' .horizontal-lists-only' ).css( 'display', 'none' );
	}
}

/**
	* Advertorial widget functions in admin
	*/
function pm_layouts_get_advertorial_image( id_ ) {
	// Upload Logo image and remove from Advertorial Listings.
	// problem - jquery walks the DOM on page load but not again, so adding a new widget to a sidebar does not expose it's html elements to jquery - use vanilla js instead
	var adv_image_banner_frame;
	//this.preventDefault();
	// If frame already exists
	if ( adv_image_banner_frame ) {
		adv_image_banner_frame.open();
		return;
	}
	// Create media frame
	adv_image_banner_frame = wp.media.frames.adv_image_banner_frame = wp.media( {
		title: 'Logo Image',
		button: { text: 'Insert Logo' },
		multiple: false
	} ).on('select', function() {
		// When image is selected get the selection
		var selection = adv_image_banner_frame.state().get('selection');
		// Get all attachement properties
		var attachment =  selection._single.attributes;
		// Change this jquery selectior to the image
		document.getElementById( 'imginp_' + id_ ).value = attachment.url;
		document.getElementById( 'imgdiv_' + id_ ).innerHTML = '<img src="' + attachment.url + '" width="200px" height="150px">';
	} ).open();
}

function pm_layouts_rem_advertorial_image( id_ ) {
	// problem - jquery walks the DOM on page load but not again, so adding a new widget to a sidebar does not expose it's html elements to jquery - use vanilla js instead
	document.getElementById( 'imginp_' + id_ ).value = '';
	document.getElementById( 'imgdiv_' + id_ ).innerHTML = '';
}

function pm_layouts_display_advertorial_form( id_ ) {
	// problem - jquery walks the DOM on page load but not again, so adding a new widget to a sidebar does not expose it's html elements to jquery - use vanilla js instead
	var chk_ = document.getElementById( id_ );
	var div_ = document.getElementById( 'div_' + id_ );
	if ( true === chk_.checked ){
		div_.style.display = 'block';
	} else {
		div_.style.display = 'none';
	}
}

function pm_layouts_deactivate_list( list ) {
	list.addClass( 'pmadmin_list_inact' );
	list.find( '[name]' ).addClass( 'configuration-ignore' );
}

function pm_layouts_activate_list( list ) {
	list.removeClass( 'pmadmin_list_inact' );
	list.find( '[name]' ).removeClass( 'configuration-ignore' );
}
