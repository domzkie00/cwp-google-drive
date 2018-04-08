jQuery(function($){
	var table = $('#google-table');

	if(table.length) {
		$(document).ready(function() {
	        if(table.find('.one-file').length == 0) {
	        	console.log('hey');
				table.find('.empty-table').show();
			}
		});

		$(document).on('click', '.upload-file', function(){
			$('#select-file-upload').trigger('click');
		});

		$(document).on('change', '#select-file-upload', function(){
			$('.upload-file').text('Uploading...');
			$('.upload-file').css({'pointer-events': 'none', 'box-shadow': 'none'});
			$('.upload-file').blur();
			$('#googledrive_upfile_form').submit();
		});

		$(document).on('click', '.db-trash', function(){
			$(this).hide();
			$(this).closest('td').find('.confirmation-delete').fadeIn();
		});

		$(document).on('click', '.delete-no', function(){
			$(this).closest('.confirmation-delete').hide();
			$(this).closest('td').find('.db-trash').fadeIn();
		});

		$(document).on('click', '.delete-yes', function(){
			var thisTR = $(this).closest('tr');
			var id = $(this).closest('td').find('.db-trash').attr('data-path');

			$.post(
                cwpg_wp_script.ajaxurl,
                { 
            	data: { 
	              'id' : id,
	            },
                action : 'delete_file'
                }, 
                function( result, textStatus, xhr ) {
                    thisTR.fadeOut(function(){
			    		if(table.find('.one-file').length == 1) {
			    			table.find('.empty-table').fadeIn();
			    		}
			    		thisTR.remove();
			    	});
                }).fail(function(error) {
                    console.log(error);
                }
            );
		});
	}
});