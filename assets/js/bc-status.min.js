( function( $ ){
/**
 * Used to provide status warnings from the Brightcove servers.
 */
jQuery(document).ready(function(a){jQuery(".brightcove-service-error .notice-dismiss").click(function(){var b={action:"bc_status_dismiss",nonce:bcStatus.nonce};
// Send dismissal to WordPress.
a.ajax({url:ajaxurl,type:"POST",data:b,complete:function(a){if(!0===a.responseJSON.success){var b=new Date;b.setTime(b.getTime()+3e5);var c="; expires="+b.toGMTString();document.cookie="bc-status-dismissed="+!0+c+"; path=/"}}})})});
} )( jQuery );