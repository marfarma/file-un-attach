var funFindPosts;(function(a){funFindPosts={open:function(b,c){var d=document.documentElement.scrollTop||a(document).scrollTop();if(b&&c){a("#fun-affected").attr("name",b).val(c)}a("#fun-find-posts").show().draggable({handle:"#fun-find-posts-head"}).css({top:d+50+"px",left:"50%",marginLeft:"-250px"});a("#fun-find-posts-input").val("");a("#fun-find-posts-input").focus().keyup(function(a){if(a.which==27){funFindPosts.close()}});return false},close:function(){a("#fun-find-posts-response").html("");a("#fun-find-posts").draggable("destroy").hide()},send:function(){var b={action:"find_posts",ps:a("#fun-find-posts-input").val(),_wpnonce:funlocal.nonceajax};var c;a("input[type='radio']:checked").each(function(){c=a(this).val()});b["post_type"]=c;b["exclude"]=a("input[name='fun-current-attached']").val();a.ajax({type:"GET",data:b,url:funlocal.adminurl+"ajax.php",success:function(a){funFindPosts.show(a)},error:function(a){funFindPosts.error(a)}})},attchd:function(b){var c={img:b,action:"find_attached",ps:a("#fun-find-posts-input").val(),_wpnonce:funlocal.nonceajax};a.ajax({data:c,type:"GET",url:funlocal.adminurl+"ajax.php",success:function(a){funFindPosts.show(a)},error:function(a){funFindPosts.error(a)}})},show:function(b){a(".fun-search-results").remove();if(typeof b=="string"){this.error({responseText:b});return}var c=wpAjax.parseAjaxResponse(b);if(c.errors){this.error({responseText:wpAjax.broken})}c=c.responses[0];a("#fun-find-posts-response").append(c.data)},error:function(b){a(".fun-search-results").remove();var c=b.statusText;if(b.responseText){c=b.responseText}if(c){a("#fun-find-posts-response").append(c)}}};a(document).ready(function(){a("#fun-find-posts-submit").click(function(b){if(""==a("#fun-find-posts-response").html())b.preventDefault()});a("#fun-find-posts .find-box-search :input").keypress(function(a){if(13==a.which){funFindPosts.send();return false}});a("#fun-find-posts-search").click(funFindPosts.send);a("#fun-find-posts-close").click(funFindPosts.close);a("#doaction, #doaction2").click(function(b){a('select[name^="action"]').each(function(){if(a(this).val()=="attach"){b.preventDefault();funFindPosts.open()}})})})})(jQuery);jQuery(document).ready(function(a){a(".funattach").live("click",function(){id=a(this).attr("name").replace("unattach-","");a(".fun-mess-"+id).show();return false});a(".fun-no").live("click",function(){a(this).parents(".fun-message").hide();return false});a(".fun-yes").live("click",function(){id=a(this).attr("id").replace("file-unattch-","");count=parseInt(a("#attachments-count").html());if(count>0)a("#attachments-count").html(count-1);a.get(funlocal.adminurl+"ajax.php",{imageid:id,action:"unattach",postid:a("#post_id").val(),_wpnonce:funlocal.nonceajax});a(this).parents(".media-item").fadeOut("slow");return false});a(".fun-unattach-row").live("click",function(){id=a(this).attr("id").replace("file-unattch-","");a.get(funlocal.adminurl+"ajax.php",{imageid:id,action:"unattach",postid:a("#post_id").val(),_wpnonce:funlocal.nonceajax});td=a(this).parents("td");td.find("span").remove();td.find("strong").replaceWith("("+funlocal.unattach+")");a(this).remove();return false});a(".fileattach").click(function(){id=a(this).attr("name").replace("attach-","");a(this).hide();a(".fun-mess-"+id).show();a("#attachments-count").html(parseInt(a("#attachments-count").html())+1);a.get(funlocal.adminurl+"ajax.php",{imageid:id,action:"attach",postid:a("#post_id").val(),_wpnonce:funlocal.nonceajax});return false});a(".fun-find-posts").click(function(){id=a(this).attr("id").replace("fun-find-posts-","");funFindPosts.open("media[]",id);return false});a(".attached-list").click(function(){id=a(this).attr("id").replace("attached-list-","");funFindPosts.open("media[]",id);funFindPosts.attchd(id);return false})})