/**
 * Plugin Name: Co-op Media Link
 * Description: Options to allow libraries to customize their digital media link in searchform. Installed as MUST USE.
 * Author: Jonathan Schatz, BC Libraries Cooperative
 * Author URI: https://bc.libraries.coop
 * Version: 0.1.0
 **/
 
 ;(function($,window){
	 
	 var self;
	 
	 var CoopMediaLinkAdmin = function(opts){
		self = this;
		self.init(opts);
	 }
	 
	 CoopMediaLinkAdmin.prototype = {
		 
		 init: function(opts) {
		 	$('#coop-media-link-submit').click( self.submit_form );
		 	return this;			 
		 },
		 
		 submit_form: function() {
			 
			 var data = {
				 action: 'coop-media-link-save-change',
				 "coop-media-link-uri":  $('#coop-media-link-uri').val(),
				 "coop-media-link-label-text": $('#coop-media-link-label-text').val()
			 }
			 
			 $.post( ajaxurl, data ).complete(function(r){
			 	var res = JSON.parse(r.responseText);
			 		alert( res.feedback );
			 });
		 }
	 }	 
	 
	 $.fn.coopmedialink = function(opts) {
		 return new CoopMediaLinkAdmin(opts);
	 }
	 
 }(jQuery,window))
 
 jQuery().ready(function($){
	 window.coopmedialink = $().coopmedialink(); 
 });