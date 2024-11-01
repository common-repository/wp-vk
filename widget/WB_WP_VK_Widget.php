<?php
/**
*
*/

class WB_WP_VK_Widget extends WP_Widget
{

	private static  $meta_keys = array(
		'title'=>array( 'title'=>'标题名称'),
		'max'=>array( 'title'=>'展示数量'),
		'style'=>array( 'title'=>'css样式')
	);

    function __construct()
    {
	    parent::__construct(
		    'wb_wp_vk_widget', // Base ID
		    __( '#付费内容#', 'wbolt' ), // Name
		    array( 'description' => '', ) // Args
	    );
    }

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {

        global $wpdb;

        // $t = $wpdb->prefix.'vk_orders';
        $pagesize = 5;
        if(isset($instance['max'])){
            $pagesize = absint($instance['max']);
        }

        $uid = get_current_user_id();

        $list = $wpdb->get_results($wpdb->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM {$wpdb->prefix}vk_orders WHERE uid=%d AND u_del=0  AND pay_status=1 ORDER BY id DESC LIMIT %d", $uid, $pagesize));
        $title = ! empty( $instance['title'] ) ? $instance['title'] : '付费内容';
        $title = apply_filters( 'widget_title', $title, $instance, $this->id_base );
		include WP_VK_PATH.'/tpl/front_widget.php';
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$instance = !empty($instance) ? $instance : array('title'=>'付费内容', 'max'=>5, 'style'=>'');
		?>
			<p>
				<label for="<?php echo esc_attr($this->get_field_id( 'title' )); ?>"><b>标题名称</b></label>
				<input class="widefat" id="<?php echo esc_attr($this->get_field_id( 'title' )); ?>" name="<?php echo esc_attr($this->get_field_name( 'title' )); ?>" type="text" value="<?php echo esc_attr($instance['title']); ?>">
			</p>
			<p>
				<label for="<?php echo esc_attr($this->get_field_id( 'max' )); ?>"><b>展示数量</b></label>
				<input class="widefat" placeholder="默认 30" id="<?php echo esc_attr($this->get_field_id( 'max' )); ?>" name="<?php echo esc_attr($this->get_field_name( 'max' )); ?>" type="number" value="<?php echo esc_attr($instance['max']); ?>">
            </p>
			<p>
				<label for="<?php echo esc_attr($this->get_field_id( 'style' )); ?>"><b>css样式</b></label>
                <textarea class="widefat" id="<?php echo esc_attr($this->get_field_id( 'style' )); ?>" name="<?php echo esc_attr($this->get_field_name( 'style' )); ?>"><?php echo esc_textarea(isset($instance['style']) && $instance['style'] !='' ? $instance['style'] : ''); ?></textarea>
            </p>
		<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		foreach(self::$meta_keys as $k => $item){
			$instance[$k] = ( ! empty( $new_instance[$k] ) ) ? wp_strip_all_tags( $new_instance[$k] ) : '';
		}
		return $instance;
	}
}


