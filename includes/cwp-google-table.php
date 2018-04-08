<?php
	function formatBytes($bytes, $precision = 2) { 
        $units = array('B', 'KB', 'MB', 'GB', 'TB'); 

        $bytes = max($bytes, 0); 
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
        $pow = min($pow, count($units) - 1); 

        // Uncomment one of the following alternatives
        $bytes /= pow(1024, $pow);
        // $bytes /= (1 << (10 * $pow)); 

        return round($bytes, $precision) . ' ' . $units[$pow]; 
    } 
?>

<table id="google-table" class="table table-striped table-bordered" data-path="<?= $root_folder ?>" style="width:100%">
	<h1>Google Drive</h1>
	<span><b>ROOT:</b> /<?= $folder_name ?></span>
	<a href="javascript:;" style="float: right; text-decoration: none;" class="upload-file">Upload File</a>
	<input name="file_1" type="file" id="select-file-upload" hidden>
    <thead>
        <tr>
            <th style="text-align: center;">Name</th>
            <th style="text-align: center;">Last Modified</th>
            <th style="text-align: center;">Size</th>
            <th style="text-align: center;">Actions</th>
        </tr>
    </thead>
    <tbody>
    	<?php 
			if($result['files']) {
				foreach($result['files'] as $content) {
					if($content['.tag'] != 'folder') {
						$date = new DateTime($content['modifiedTime']);
						$date = $date->format('d-M-Y H:i:s');
		?>
				        <tr class="one-file">
				            <td><a href="https://drive.google.com/open?id=<?= $content['id'] ?>" target="_blank"><?= $content['name'] ?></a></td>
				            <td><?= $date ?></td>
				            <td style="text-align: right;"><?= formatBytes($content['size']) ?></td>
				            <td style="text-align: center;">
				            	<a href="javascript:;" data-path="<?= $content['id'] ?>" class='db-trash'>Delete</a>
				            	<div class="confirmation-delete" style="display: none;">
				            		<p style="margin: 0px;">Continue delete?</p>
				            		<div>
				            			<a href="javascript:;" class="delete-yes">Yes</a>&nbsp;&nbsp;
				            			<a href="javascript:;" class="delete-no">No</a>
			            			</div>
			            		</div>
				            </td>
				        </tr>			  
        <?php 
        			}
    			} 
		?> 		<tr style="display: none; background-color: #d1d1d1;" class="empty-table">
		        	<td colspan="4" style="text-align: center;">Folder is empty.</td>
	        	</tr>
    	<?php
			}
        ?>
    </tbody>
</table>