jQuery( document ).ready( function( $ ) {
	var player;
	var $videoCenter = $( '.video-center' );

	if ( 'undefined' === typeof VideoCenter || 'undefined' === VideoCenter.videosOnPage || 0 === $videoCenter.length ) {
		return;
	}

	// Every YT URL on page should not link to YT, but use our player at the top.
	// Update video info to the right of video when new videos are played.
	for ( let i = 0; i < VideoCenter.videosOnPage.length; i++ ) {
		$( document ).on( 'click', 'a[href="' + VideoCenter.videosOnPage[ i ]['url'] + '"]', function( e ) {
			let video = VideoCenter.videosOnPage[ i ];
			let nextVideo = VideoCenter.videosOnPage[ i + 1 ];
			let ytID = video['url'].split( '?v=' );
			let relatedLinks = Array();

			if ( 'undefined' !== typeof ytID[1] ) {
				// Scroll to top if not mobile.
				if ( ! $videoCenter.hasClass( 'mobile' ) ) {
					$( 'html, body' ).stop( true, false ).animate( { scrollTop: 0 }, 'slow' );
				}

				removeHighlight();
				player.loadVideoById( ytID[1] );

				// Replace current URL with updated.
				let newURL = '';
				let regex = /\/video\/(.*)/;
				if ( regex.test( location.href ) ) {
					// Has video ID already in URL-- replace it.
					let matches = location.href.match( regex );
					newUrl = location.href.replace( matches[0], '' ) + /video/ + ytID[1];
				} else {
					newUrl = location.href + '/video/' + ytID[1];
				}

				history.pushState( {}, video['post_title'], newUrl );

				$videoCenter.find( '.title a' ).text( video['post_title'] );
				$videoCenter.find( '.title a' ).attr( 'href', video['url'] );
				$videoCenter.find( '.date' ).text( 'Published ' + video['formatted_date'] );
				$videoCenter.find( '.author' ).text( 'By ' + video['post_author'] );
				$videoCenter.find( '.excerpt' ).text( video['post_excerpt'] );

				$videoCenter.find( '.related' ).remove();
				if ( 'undefined' !== typeof video.related_links_string ) {
					$( '.excerpt' ).after(
						$( '<li>', {
							'class': 'related',
							'html': 'Related: ' + video.related_links_string
						} )
					);
				}
			}

			e.preventDefault();
			e.stopPropagation();
		} );
	}

	// Sharing links grab currently playing video.
	$( '.social-bar .icons a' ).on( 'click', function( e ) {
		var ytID = player.getVideoData()['video_id'];
		var url = location.href;
		var platform = $( this ).attr( 'data-url' );

		if ( '' !== platform ) {
			url = platform + encodeURIComponent( url );
		} else {
			url = 'mailto:' + url;
		}

		var win = window.open( url, '_blank' );
		win.focus();

		e.preventDefault();
		e.stopPropagation();
	} );

	// Other plugins/code may enqueue this file, so only grab it as a backup.
	if ( 'undefined' !== typeof YT && 1 === YT.loaded ) {
		setupFeaturedPlayer();
	} else {
		window.onYouTubeIframeAPIReady = function() {
			setupFeaturedPlayer();
		};

		$.getScript( '//youtube.com/iframe_api' );
	}

	// Setup YT player. Read requested video ID from shared URLs.
	function setupFeaturedPlayer() {
		var playerSettings = {
			videoId: $( '#youtube-video' ).attr( 'data-id' ),
			modestbranding: true,
			playerVars: { 
				'enablejsapi': 1,
				'autoplay': 1,
				'playsinline': 1,
				'rel': 0,
				'origin': $( '#youtube-video' ).attr( 'data-origin' )
			},
			events: {
				'onStateChange': function( event ) {
					if ( 0 === event.data ) {
						// Ended, go to next video.
						$( 'article .up-next' ).trigger( 'click' );
					} 
					if ( 1 === event.data ) {
						highlightVideo();
					} 
				}
			}
		};

		player = new YT.Player( 'youtube-video', playerSettings );
	}

	// Sticky video for desktop and mobile.
	var videoTop = $videoCenter.offset().top;
	var adminBarHeight = 0;
	var stickyClosed = false;
	var $stuckElement = $videoCenter;
	var $placeholderElement = $( '.video-center-placeholder' );

	// Admin bar always at top.
	if ( $( '#wpadminbar' ).length ) {
		adminBarHeight = $( '#wpadminbar' ).outerHeight();
	}

	// Mobile sticks just the video.
	if ( $videoCenter.hasClass( 'mobile' ) ) {
		$stuckElement = $videoCenter.find( '.sticky-video-container' );
	}

	$( window ).scroll( function() {
		// Nav height may change on scroll. Grab height each time.
		var navHeight = 0;
		var topPx = 0;
		if ( $( '.header-wrapper' ).length ) {
			navHeight = $( '.header-wrapper' ).outerHeight();
		}

		topPx = adminBarHeight + navHeight;

		// Apply hardcoded px if filter was used in theme.
		if ( 'undefined' !== VideoCenter.pxFromTop ) {
			if ( $videoCenter.hasClass( 'mobile' ) && null !== VideoCenter.pxFromTop.mobile ) {
				topPx = VideoCenter.pxFromTop.mobile;
			} else if ( null !== VideoCenter.pxFromTop.desktop ) {
				topPx = VideoCenter.pxFromTop.desktop;
			}
		}

		if ( $( window ).scrollTop() >= ( videoTop - topPx ) && ! stickyClosed ) {
			$stuckElement.addClass( 'sticky' ).css( 'top', topPx + 'px' );
			$placeholderElement.addClass( 'sticky' );

			if ( $videoCenter.hasClass( 'mobile' ) ) {
				// Video height + space covered by menus at top - height of button.
				var corner = ( $stuckElement.outerHeight() + topPx ) - 50;
				$videoCenter.find( '.close' ).css( 'top', corner + 'px' ).show();
			}
		} else {
			$stuckElement.removeClass( 'sticky' ).css( 'top', '0px' );
			$placeholderElement.removeClass( 'sticky' );
			$stuckElement.css('display','inherit');

			if ( $videoCenter.hasClass( 'mobile' ) ) {
				$videoCenter.find( '.close' ).hide();
			}
		}
	} );

	// Close button on sticky video player.
	$videoCenter.find( '.close' ).on( 'click', function( e ) {
		stickyClosed = true;
		$stuckElement.css('display','none');

		if ( $videoCenter.hasClass( 'mobile' ) ) {
			$( window ).scroll();
		}
		e.preventDefault();
	} );

	// Show/hide details button for tablet and mobile.
	$videoCenter.find( '.show-details' ).on( 'click', function( e ) {
		$( this ).hide();
		$videoCenter.find( '.hide-details' ).show();
		$videoCenter.find( '.excerpt, .author, .date, .related' ).show();
	} );

	$videoCenter.find( '.hide-details' ).on( 'click', function( e ) {
		$( this ).hide();
		$videoCenter.find( '.show-details' ).show();
		$videoCenter.find( '.excerpt, .author, .date, .related' ).hide();
	} );

	// Thumbnail overlays.
	function removeHighlight() {
		var $hl = $( '.horizontal-lists' );

		$hl.find( 'div.now-playing' ).remove();
		$hl.find( 'div.up-next' ).remove();

		$hl.find( '.now-playing' ).removeClass( 'now-playing' );
		$hl.find( '.up-next' ).removeClass( 'now-playing' );
	}

	function highlightVideo() {
		removeHighlight();
		var currentVideoLinkParts = $videoCenter.find( '.title a' ).attr( 'href' ).split( '?v=' );

		var currentVideoImg = $( '.horizontal-lists' ).find( '.thumbnail a[href$="'+ currentVideoLinkParts[1] +'"]' );
		currentVideoImg.append( '<div class="now-playing">Now Playing</div>' );
		currentVideoImg.addClass( 'now-playing' );

		var currentVideoInfo = currentVideoImg.closest( 'article' ).find( '.article-author-time-ago, .article-title' );
		currentVideoInfo.addClass( 'now-playing' );

		var nextVideoImg = currentVideoImg.closest( 'article' ).next().find( '.thumbnail a' );
		nextVideoImg.append( '<div class="up-next"><div class="play"></div></div>' );
		nextVideoImg.addClass( 'up-next' );
	}

	// Mobile has thumbnails showing hover icon by default and on hover for desktop.
	if ( $videoCenter.hasClass( 'mobile' ) ) {
		$( 'article.has-video .thumbnail a' ).append( '<div class="play-overlay"><div class="play"></div></div>' );
	} else {
		$( 'article.has-video' ).hover( function() {
			// Don't add play hover to now playing/up next.
			if ( $( this ).find( '.now-playing, .up-next' ).length ) {
				return;
			}
			$( this ).find( '.thumbnail a' ).append( '<div class="play-overlay"><div class="play"></div></div>' );
		}, function() {
			$( this ).find( '.play-overlay' ).remove();
		} );
	}
} );
