<?php
class Documentation {
	public $settings = array(
		'admin_menu_category' => 'Support',
		'admin_menu_name' => 'Documentation',
		'admin_menu_icon' => '<i class="icon-document"></i>',
		'description' => 'Allows you to add documentation articles to your website.',
		
		'user_menu_name' => 'Documentation',
		'user_menu_icon' => '<i class="icon-document"></i>',
	);
	function admin_area() {
		global $billic, $db;
		
?>
<style>
.arrow:before {
content:'\21B5';
font-weight: normal;
line-height: 100%;
font-size: 125%;
float:left;
margin-left: 15px;
margin-right: 3px;
display:block;
-webkit-transform: matrix(-1, 0, 0, 1, 0, 0);
-moz-transform: matrix(-1, 0, 0, 1, 0, 0);
-o-transform: matrix(-1, 0, 0, 1, 0, 0);
transform: matrix(-1, 0, 0, 1, 0, 0);
}
</style>
<?php
		
		$billic->set_title('Admin/Documentation');
		echo '<h1><i class="icon-document"></i> Documentation</h1>';
		
		if (isset($_GET['Edit'])) {
			if(isset($_POST['update'])) {
				if (empty($_POST['title'])) {
					$errors[] = 'Title can not be empty';
				}
				if (empty($errors)) {
					$db->q('UPDATE `documentation` SET `title` = ?, `parent` = ?, `html` = ?, `weight` = ?, `public` = ?, `lastupdated` = ? WHERE `id` = ?', $_POST['title'], $_POST['parent'], $_POST['html'], $_POST['weight'], $_POST['public'], time(), $_GET['Edit']);
					$billic->redirect('/Admin/Documentation/');
					exit;
				}
			}

			$billic->show_errors();

			$doc = $db->q('SELECT * FROM `documentation` WHERE `id` = ?', $_GET['Edit']);
			$doc = $doc[0];
			if (empty($doc)) {
				err('Doc does not exist');
			}
			?>
			<form method="POST">  
			<table width="100%">
			<tr class="nohover"><td width="50">Title:</td><td><input type="text" name="title" value="<?php echo safe($doc['title']); ?>" class="form-control"></td></tr>
			<tr class="nohover"><td>Parent:</td><td><select name="parent" style="font-family: monospace" class="form-control"><option value="">None</option><?php

			function test($parent) {
				global $db, $branches, $root_parent;
				if (empty($parent)) {
					return;
				}
				$branches[] = $parent;
				$docs = $db->q('SELECT `parent`, `title` FROM `documentation` WHERE `parent` = ? ORDER BY `weight`', $parent);
				foreach ($docs as $d) {
					$prefix = str_repeat('&nbsp;&nbsp;', count($branches)).'|_&nbsp;';
					echo '<option value="'.safe($d['title']).'"'.($d['parent']==$root_parent?' selected':'').'>'.$prefix.$d['title'].'</option>';
					test($d['title']);
				}
				array_pop($branches);
			}

			$branches = array();
			$roots = $db->q('SELECT `parent`, `title` FROM `documentation` WHERE `parent` = ? ORDER BY `weight`', '');
			foreach($roots as $root) {
				if (empty($root['title'])) {
					continue;
				}
				$root_parent = $root['parent'];
				echo '<option value="'.safe($root['title']).'"'.($root['title']==$doc['parent']?' selected':'').'>'.$root['title'].'</option>';
				test($root['title']);
			}
			?></select></td></tr>
			<tr class="nohover"><td>Weight:</td><td><input type="text" name="weight" value="<?php echo safe($doc['weight']); ?>" style="width: 50px" class="form-control"></td></tr>
			<tr class="nohover"><td>Public:</td><td><input type="checkbox" name="public" value="1"<?php if ($doc['public']==1) { echo ' checked'; } ?>> Is this document visible in the user area?</td></tr>
			<tr class="nohover"><td colspan="2"><textarea type="text" name="html" rows="20" style="width: 100%" id="html_body"><?php echo safe($doc['html']); ?></textarea></td></tr>
			</table>
				<div style="text-align:center"><input type="submit" name="update" value="Update &raquo;" class="btn btn-success" /></div>
			</form> 
			<?php
			echo '<script src="//cdn.ckeditor.com/4.5.9/full/ckeditor.js"></script><script>addLoadEvent(function() {
	// Update message while typing (part 1)
	key_count_global = 0; // Global variable
	
	CKEDITOR.replace(\'html_body\', {   
		allowedContent: true,
		enterMode: CKEDITOR.ENTER_BR,
		disableNativeSpellChecker: false,
	});
});</script>';
		} else
		if (isset($_GET['Add'])) {
			$id = $db->insert('documentation', array(
				'lastupdated' => time(),
			));
			$billic->redirect('/Admin/Documentation/Edit/'.$id);
		} else
		if (isset($_GET['Delete'])) {
			if (isset($_POST['delete'])) {
				$db->q('DELETE FROM `documentation` WHERE `id` = ?', $_GET['Delete']);
				$billic->redirect('/Admin/Documentation/');
				exit;
			} else {
				$doc = $db->q('SELECT * FROM `documentation` WHERE `id` = ?', $_GET['Delete']);
				$doc = $page[0];
				echo '<p>You are able to delete the following documentation;</p><p><b>Title:</b> '.safe($doc['title']).'</p><p><b>ID:</b> '.$doc['id'].'</p><p><b>Size:</b> '.round(strlen($doc['html'])/1024, 2).' KB</p><form method="POST"><input type="submit" name="delete" value="Confirm &raquo;" class="delete"></form>';
			}
		} else {
			echo '<form method="GET" action="/Admin/Documentation/Add/"><input type="submit" value="Add New Article &raquo;" class="btn btn-success"></a>';
			
			$docs = $db->q('SELECT * FROM `documentation` ORDER BY `weight`'); 
			echo '<table class="table table-striped"><tr><th width="230">Title</th><th width="4">Public</th><th width="10">Weight</th><th width="120">Last Updated</th><th width="50">Size</th><th width="100">Actions</th></tr>'; 
			if (empty($docs)) { 
				echo '<tr><td colspan="20">No articles available.</td></tr>'; 
			}
			$last_parent = '~';
			foreach($docs as $doc) {
				echo '<tr><td>';
				if ($last_parent==$doc['parent']) {
					echo '<span class="arrow"></span>';
				}
				echo '<a href="/User/Documentation/View/'.$doc['id'].'"><span class="icon-document"></span> '.$doc['title'].'</a></td><td>';
				if ($doc['public']==1) {
					echo '<span class="icon-check-mark"></span>';
				} else {
					echo '<span class="icon-remove"></span>';
				}
				echo '</td><td>'.$doc['weight'].'</td><td>'.$this->timeago($doc['lastupdated']).'</td><td>'.round(strlen($doc['html'])/1024, 2).' KB</td><td><a href="Edit/'.$doc['id'].'">Edit</a>, <a href="Delete/'.$doc['id'].'">Delete</a></td></tr>';
				if (empty($doc['parent']) && !empty($doc['title'])) {
					$last_parent = $doc['title'];
				}
			} 
			echo '</table>'; 
		}
		
	}
	
	function user_area() {
		global $billic, $db;
		
?>
<style>
.arrow:before {
content:'\21B5';
font-weight: normal;
line-height: 100%;
font-size: 125%;
float:left;
margin-left: 15px;
margin-right: 3px;
display:block;
-webkit-transform: matrix(-1, 0, 0, 1, 0, 0);
-moz-transform: matrix(-1, 0, 0, 1, 0, 0);
-o-transform: matrix(-1, 0, 0, 1, 0, 0);
transform: matrix(-1, 0, 0, 1, 0, 0);
}
</style>
<?php
		
		$title = '';
		$doc = $db->q('SELECT * FROM `documentation` WHERE `id` = ?', $_GET['View']);
		$doc = $doc[0];
		if (!empty($doc['title'])) {
			if (!empty($doc['parent'])) {
				$title .= ' > '.$doc['parent'];
				$parentid = $db->q('SELECT `id` FROM `documentation` WHERE `title` = ?', $doc['parent']);
				$parentid = $parentid[0]['id'];
			}
			$title .= ' > '.$doc['title'];
		}
		$billic->set_title('Documentation'.$title);
		echo '<ol class="breadcrumb"><li><a href="/Home">Home</a></li><li><a href="/User/Documentation/">Documentation</a></li>'.(empty($doc['parent'])?'':'<li><a href="/User/Documentation/View/'.$parentid.'/">'.$doc['parent'].'</a></li>').(empty($doc['title'])?'':'<li class="active">'.$doc['title'].'</li>').'</ol>';
		if (!empty($doc['title'])) {
			echo '<h1><i class="icon-document"></i> '.$doc['title'].'</h1>';
		}
		if (empty($doc)) {
			$docs = $db->q('SELECT * FROM `documentation` WHERE `public` = 1 ORDER BY `weight`'); 
			echo '<table class="table table-striped"><tr><th width="230">Title</th><th width="120">Last Updated</th></tr>'; 
			if (empty($docs)) { 
				echo '<tr><td colspan="20">No articles available.</td></tr>'; 
			}
			$last_parent = '~';
			foreach($docs as $doc) { 
				echo '<tr><td>';
				if ($last_parent==$doc['parent']) {
					echo '<span class="arrow"></span>';
				}
				echo '<a href="/User/Documentation/View/'.$doc['id'].'"><span class="icon-search"></span> '.$doc['title'].'</a></td><td>'.$this->timeago($doc['lastupdated']).'</td>';
				if (empty($doc['parent']) && !empty($doc['title'])) {
					$last_parent = $doc['title'];
				}
			} 
			echo '</table>'; 
		} else {
			$children = $db->q('SELECT `id`, `title` FROM `documentation` WHERE `parent` = ? ORDER BY `weight`', $doc['title']);
			if (count($children)>0) {
				echo '<ul>';
				foreach($children as $child) {
					if (!empty($child['title'])) {
						echo '<li><a href="/User/Documentation/View/'.$child['id'].'">'.$child['title'].'</a></li>';
					}
				}
				echo '</ul>';
			}	
			
			echo $doc['html'];
			
			echo '<br><br><hr>';
			$next = $db->q('SELECT `id`, `parent`, `title` FROM `documentation` WHERE `weight` > ? ORDER BY `weight` ASC LIMIT 1', $doc['weight']);
			$next = $next[0];
			if (!empty($next['title'])) {
				echo '<div style="width:50%;float:right;text-align:right">Next: <a href="/User/Documentation/View/'.$next['id'].'">';
				if (!empty($next['parent'])) {
					echo $next['parent'].' &raquo; ';
				}
				echo $next['title'].'</a></div>';
			}
			$previous = $db->q('SELECT `id`, `parent`, `title` FROM `documentation` WHERE `weight` < ? ORDER BY `weight` DESC LIMIT 1', $doc['weight']);
			$previous = $previous[0];
			if (!empty($previous['title'])) {
				echo '<div style="width:50%">Previous: <a href="/User/Documentation/View/'.$previous['id'].'">';
				if (!empty($previous['parent'])) {
					echo $previous['parent'].' &raquo; ';
				}
				echo $previous['title'].'</a></div>';
			}
		}
	}
	
	function timeago($timestamp) {
		// note: the variable $time and $time_a are both used to 
		//       get an accurate value of time, since we're rounding
		if ($timestamp<=0) {
			return 'never';
		}
		$time = (time()-$timestamp);
		if ($time<60) {
			return $time.'&nbsp;sec'.($time>1?'s':'');
		} else {
			$time_a = floor($time/60);
			if ($time_a<60) {
				return $time_a.'&nbsp;min'.($time_a>1?'s':'');
			} else {
				$mins = $time_a % 60;
				$time_a = floor($time_a/60);
				if ($time_a<24) {
					return $time_a.'&nbsp;hour'.($time_a>1?'s':'').($mins>0?', '.$mins.' min'.($mins>1||$mins==0?'s':''):'');
				} else {
					$hours = $time_a % 24;
					$time_a = floor($time_a/24);
					if ($time_a<30) {
						return $time_a.'&nbsp;day'.($time_a>1?'s':'').($hours>0?', '.$hours.' hour'.($hours>1||$hours==0?'s':''):'');
					} else {
						$days = $time_a % 30;
						$time_a = floor($time_a/30);
						if ($time_a<12) {
							return $time_a.'&nbsp;month'.($time_a>1?'s':'').($days>0?', '.$days.' day'.($days>1||$days==0?'s':''):'');
						} else {
							$months = $time_a % 12;
							$time_a = floor($time_a/12);
							return $time_a.'&nbsp;year'.($time_a>1?'s':'').($months>0?', '.$months.' month'.($months>1||$months==0?'s':''):'');
						}
					}
				}
			}
		}
	}
	
}
