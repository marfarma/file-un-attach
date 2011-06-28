// Object Copied from media-dev.js and renamed
var funFindPosts;
(function($){
	funFindPosts = {
		open : function(af_name, af_val) {
			var st = document.documentElement.scrollTop || $(document).scrollTop();

			if ( af_name && af_val ) {
				$('#fun-affected').attr('name', af_name).val(af_val);
			}
			$('#fun-find-posts').show().draggable({
				handle: '#fun-find-posts-head'
			}).css({'top':st + 50 + 'px','left':'50%','marginLeft':'-250px'});
			
			$('#fun-find-posts-input').val('');
			$('#fun-find-posts-input').focus().keyup(function(e){
				if (e.which == 27) { funFindPosts.close(); } // close on Escape
			});

			return false;
		},

		close : function() {
			$('#fun-find-posts-response').html('');
			$('#fun-find-posts').draggable('destroy').hide();
		},

		send : function() {
			var post = {
				action: 'find_posts',
				ps: $('#fun-find-posts-input').val(),
				_wpnonce : funlocal.nonceajax
			};

			var selectedItem;
			$("input[type='radio']:checked").each(function() { selectedItem = $(this).val() });
			post['post_type'] = selectedItem;
			
			var excludeIds = '';
			$("#fun-find-posts-response input[type='checkbox']")
			.each(function(){ excludeIds += $(this).val()+',';});
			post['exclude'] = excludeIds; excludeIds= '';
			
			$.ajax({
				type : 'GET',
				data : post,
				url : funlocal.adminurl+"ajax.php",
				success : function(x) { funFindPosts.show(x); },
				error : function(r) { funFindPosts.error(r); }
			});
		},
		
		attchd: function(imgid) {
			var post = {
				img: imgid,
				action: 'find_attached',
				ps: $('#fun-find-posts-input').val(),
				_wpnonce : funlocal.nonceajax
			};
			
			$.ajax({
				data : post,
				type : 'GET',
				url  : funlocal.adminurl+"ajax.php",
				success : function(x) { funFindPosts.show(x); },
				error : function(r) { funFindPosts.error(r); }
			});
		},

		show : function(x) {
			$('.fun-search-results').remove(); 
			if ( typeof(x) == 'string' ) {
				this.error({'responseText': x});
				return;
			}

			var r = wpAjax.parseAjaxResponse(x);

			if ( r.errors ) {
				this.error({'responseText': wpAjax.broken});
			}
			r = r.responses[0];
			$('#fun-find-posts-response').append(r.data);
		},

		error : function(r) {
			$('.fun-search-results').remove(); 
			var er = r.statusText;
			if ( r.responseText ) {
				er = r.responseText;
			}
			if ( er ) {
				$('#fun-find-posts-response').append(er);
			}
		}
	};

	$(document).ready(function() {
		$('#fun-find-posts-submit').click(function(e) {
			if ( '' == $('#fun-find-posts-response').html() )
				e.preventDefault();
		});
		$( '#fun-find-posts .find-box-search :input' ).keypress( function( event ) {
			if ( 13 == event.which ) {
				funFindPosts.send();
				return false;
			}
		} );
		$('#fun-find-posts-search').click( funFindPosts.send );
		$('#fun-find-posts-close' ).click( funFindPosts.close );
		$('#doaction, #doaction2').click(function(e){
			$('select[name^="action"]').each(function(){
				if ( $(this).val() == 'attach' ) {
					e.preventDefault();
					funFindPosts.open();
				}
			});
		});
	});
})(jQuery);

// File Un-attach functions
jQuery(document).ready(function($){ 
								
	$('.funattach').live('click',function(){
		id = $(this).attr('name').replace('unattach-','');
		$('.fun-mess-'+id).show();
		return false;
	});
	
	$('.fun-no').live('click',function(){
		$(this).parents('.fun-message').hide();
		return false;
	});
	
	$('.fun-yes').live('click',function(){
		id = $(this).attr('id').replace('file-unattch-','');		 
		count = parseInt($('#attachments-count').html());
		if(count > 0) $('#attachments-count').html(count-1)
		$.get(funlocal.adminurl+"ajax.php",{ 
			imageid		:id,
			action		:'unattach',
			postid		:$('#post_id').val(),
			_wpnonce	:funlocal.nonceajax
		})
		$(this).parents('.media-item').fadeOut('slow');
		return false;
	});
	
	$('.fun-unattach-row').live('click',function(){
		id = $(this).attr('id').replace('file-unattch-','');		 
		$.get(funlocal.adminurl+"ajax.php",{ 
			imageid		:id,
			action		:'unattach',
			postid		:$('#post_id').val(),
			_wpnonce	:funlocal.nonceajax
		})
		td = $(this).parents('td');
		td.find('span').remove();
		td.find('strong').replaceWith('('+funlocal.unattach+')');
		$(this).remove();
		return false;
	});
	
	
	$('.fileattach').click(function(){
		id = $(this).attr('name').replace('attach-','');
		
		$(this).hide();
		$('.fun-mess-'+id).show();
		$('#attachments-count').html(parseInt($('#attachments-count').html())+1)
		$.get(funlocal.adminurl+"ajax.php",{ 
			imageid		:id,
			action		:'attach',
			postid		:$('#post_id').val(),
			_wpnonce	:funlocal.nonceajax
		})
		return false;
	});
	
	$('.fun-find-posts').click(function(){
		id = $(this).attr('id').replace('fun-find-posts-','');								
		funFindPosts.open('media[]',id);
		return false;
	});
	
	$('.attached-list').click(function(){
		id = $(this).attr('id').replace('attached-list-','');								
		funFindPosts.open('media[]',id);
		funFindPosts.attchd(id);
		return false;
	});
});