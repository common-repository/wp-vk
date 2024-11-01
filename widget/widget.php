<?php
/**
*
*/

class WP_VK_Widget
{
    function __construct()
    {

    }

	public static function init(){
	    add_action('widgets_init',array(__CLASS__,'widgets_init'));
    }

    public static function widgets_init(){
	    register_widget( 'WB_WP_VK_Widget' );
    }
}