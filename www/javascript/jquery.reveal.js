/*
 * jQuery Reveal Plugin 1.0
 * www.ZURB.com
 * Copyright 2010, ZURB
 * Free to use under the MIT license.
 * http://www.opensource.org/licenses/mit-license.php

 * modified by Sean Connelley...
*/


(function($) {
    $.fn.reveal = function(options) {
        
        var defaults = {  
	    	animation: 'fadeAndPop', //fade, fadeAndPop, none
		    animationspeed: 300, //how fast animtions are
		    closeonbackgroundclick: true, //if you click background will modal close?
		    dismissmodalclass: 'close-reveal-modal' //the class of a button or element that will close an open modal
    	}; 
    	
        //Extend dem' options
        var options = $.extend({}, defaults, options); 

		
		var theModal = $.data(this, 'modal_window');
		
		if(!theModal){
			theModal = new Modal(this,options);
		}

		return theModal;
		
	}
	
	function Modal(el,options){
		this.modal = $(el);
    	this.topMeasure  = parseInt(this.modal.css('top'));
		this.topOffset = this.modal.height() + this.topMeasure;
      	this.locked = false;
		this.modalBG = $('.reveal-modal-bg');
		this.options = options;
			
		if(this.modalBG.length == 0) {
			this.modalBG = $('<div class="reveal-modal-bg" />').insertAfter(this.modal);
		}
	}
	
	Modal.prototype = {
		show: function(){
			this.modalBG.unbind('click.modalEvent');
			if(!this.locked) {
				this.lockModal();
				if(this.options.animation == "fadeAndPop") {
					this.modal.css({'top': $(document).scrollTop()-this.topOffset, 'opacity' : 0, 'visibility' : 'visible'});
					this.modalBG.fadeIn(this.options.animationspeed/2);
					this.modal.delay(this.options.animationspeed/2).animate({
						"top": $(document).scrollTop()+this.topMeasure,
						"opacity" : 1
					}, this.options.animationspeed,this.unlockModal());					
				}
				if(this.options.animation == "fade") {
					this.modal.css({'opacity' : 0, 'visibility' : 'visible', 'top': $(document).scrollTop()+this.topMeasure});
					this.modalBG.fadeIn(this.options.animationspeed/2);
					this.modal.delay(this.options.animationspeed/2).animate({
						"opacity" : 1
					}, this.options.animationspeed,this.unlockModal());					
				} 
				if(this.options.animation == "none") {
					this.modal.css({'visibility' : 'visible', 'top':$(document).scrollTop()+this.topMeasure});
					this.modalBG.css({"display":"block"});	
					this.unlockModal()				
				}   
			}
		},
		
		close: function(){
			if(!this.locked) {
				this.lockModal();
				if(this.options.animation == "fadeAndPop") {
					this.modalBG.delay(this.options.animationspeed).fadeOut(this.options.animationspeed);
					var modalRef = this;
					this.modal.animate({
						"top":  $(document).scrollTop()-this.topOffset,
						"opacity" : 0
					}, this.options.animationspeed/2, function() {
						modalRef.modal.css({'top':this.topMeasure, 'opacity' : 1, 'visibility' : 'hidden'});
						modalRef.unlockModal();
					});					
				}  	
				if(this.options.animation == "fade") {
					this.modalBG.delay(this.options.animationspeed).fadeOut(this.options.animationspeed);
					var modalRef = this;
					this.modal.animate({
						"opacity" : 0
					}, this.options.animationspeed, function() {
						modalRef.modal.css({'opacity' : 1, 'visibility' : 'hidden', 'top' : this.topMeasure});
						modalRef.unlockModal();
					});					
				}  	
				if(this.options.animation == "none") {
					this.modal.css({'visibility' : 'hidden', 'top' : this.topMeasure});
					this.modalBG.css({'display' : 'none'});	
				}   			
			}
		},
		unlockModal: function(){
			this.locked = false;
		},
		lockModal: function(){
			this.locked = true;
		}
	}
})(jQuery);
        