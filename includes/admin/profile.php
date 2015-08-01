<form method="POST" id="frm_bp_r_t_a_screen" action="<?php echo admin_url('admin-ajax.php');?>">
	<input type="hidden" name="action" value="bp_r_t_a_profile" >
	<?php wp_nonce_field( 'bp_r_t_a_profile', 'nonce_bp_r_t_a_screen' );?>
	<input type="hidden" name="config_data" id="config_data" value="" >
	
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row"><?php _e( 'Default Profile Page', 'bp-reorder-tabs' );?></th>
				<td>
					<select name="def_profile_page" id="def_profile_page">
						<option value=''><?php _e( '&mdash; Select &mdash;', 'bp-reorder-tabs' );?></option>
						<?php 
						$bp = buddypress();
						$default_nav = defined( 'BP_DEFAULT_COMPONENT' ) ? BP_DEFAULT_COMPONENT : '';
						
						/* 
						 * Some tabs are only visible to the logged in user, so don't make sense in this list.
						 * Therefore, lets include only selected tabs
						 */
						$public_navs = array( 'activity', 'profile', 'friends', 'forums', 'groups' );
						
						foreach( $bp->bp_nav as $nav=>$nav_props ){
							if( !in_array( $nav_props['slug'], $public_navs ) )
								continue;
							
							$selected = $nav_props['slug']==$default_nav ? ' selected' : '';
							
							$nav_name = $nav_props['name'];
							$pos = strpos($nav_name, '<span');
							if( $pos ){
								$nav_name = substr($nav_name, 0, $pos);
							}
							
							echo "<option value='" . esc_attr( $nav_props['slug'] ) . "' {$selected}>{$nav_name}</option>";
						}
						?>
					</select>
				</td>
			</tr>
		</tbody>
	</table>
	
	<ul class="nav-list sortable">
		<?php 
		global $bp;
		foreach( $bp->bp_nav as $nav=>$nav_props ){
			$nav_name = $nav_props['name'];
			$pos = strpos($nav_name, '<span');
			if( $pos ){
				$nav_name = substr($nav_name, 0, $pos);
			}
			
			echo "<li class='nav nav-top nav-type-profile nav-" . esc_attr( $nav_props['slug'] ) ."' data-navid='" . esc_attr( $nav_props['slug'] ) . "'>";
				echo "<div class='drag-handle'>{$nav_name}</div>";
				
				if( isset( $bp->bp_options_nav[$nav_props['slug']] ) ){
					echo "<ul class='nav-list subnav-list sortable'>";
					
					foreach( $bp->bp_options_nav[$nav_props['slug']] as $subnav_pos=>$subnav ){
						$subnav_name = $subnav['name'];
						$pos = strpos($subnav_name, '<span');
						if( $pos ){
							$subnav_name = substr($subnav_name, 0, $pos);
						}
						
						echo "<li class='nav nav-subnav nav-type-profile nav-" . esc_attr( $subnav['slug'] ) ."' data-navid='" . esc_attr( $subnav['slug'] ) . "'>";
							echo "<div class='drag-handle'>{$subnav_name}</div>";
						echo "</li>";
					}
					
					echo "</ul>";
				}
				
			echo "</li>";
		}
		?>
	</ul>
	<p class="submit">
		<button type="submit" class="button button-primary" ><?php _e( 'Save Changes', 'bp-reorder-tabs' );?></button>
	</p>
</form>