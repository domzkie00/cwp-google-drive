jQuery(function($){
    var selected;

	$(document).on('change', '#integration-select-type', function(){
        if($(this).val() == 'google') {
            var select_album = $('#integration-select-folder');
            select_album.html('<option selected="true" disabled="disabled">Select Folder</option>');
            select_album.attr('disabled', 'disabled');
            
            $.get(
                cwpg_admin_script.ajaxurl,
                { 
                action : 'get_folder_list'
                }, 
                function( result, textStatus, xhr ) {
                    var data = JSON.parse(result);
                    var select = $('#integration-select-folder');
                    select.find('.root-folder').remove();

                    $.each(data['files'], function() {
                        var id = this['id'];
                        var name = this['name'];
                        var option = '<option class="root-folder" value="'+id+'">'+name+'</option>';
                        select.append(option);
                    });
                }).fail(function(error) {
                    console.log(error);
                }).done(function() {
                    $('#integration-select-folder').find('option').each(function(){
                        if($(this).text() === '') {
                            $(this).remove();
                        }
                    });

                    if(selected) {
                        $('#integration-select-folder option[value="'+selected+'"]').attr('selected','selected');
                    }

                    select_album.removeAttr('disabled');
                }
            );
        }
    });

    $(document).ready(function() {
        if($('#integration-select-type').val() == 'google') {
            selected = $('#integration-select-folder').val();
            $('#integration-select-type').trigger('change');
        }
    });
});