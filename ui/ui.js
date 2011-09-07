// Prefilter ajax requests in order to add the lang parameter to p2p search requests,
// so that only posts in the correct language are returned

jQuery.ajaxPrefilter(function(options, original) {
	if(icl_this_lang && original.data && original.data.action == 'p2p_box') {
		options.data += '&lang=' + icl_this_lang;
	}
});