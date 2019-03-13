jQuery( document ).ready( function( $ ) {

	$( document ).on( 'click', '.horizontal-lists .load-more', function( e ) {
		e.preventDefault();
		var $listRow = $( this ).closest( '.list-row' );
		$listRow.find( '.row.hidden:first' ).removeClass( 'hidden' );

		if ( 0 === $listRow.find( '.row.hidden:first' ).length ) {
			$( this ).hide();
		}
	} );
} );