<?php

/**
 * Author: wbolt team
 * Author URI: https://www.wbolt.com
 */

class WP_VK_BLOCK
{

	public static function init()
	{
		add_action('init', array(__CLASS__, 'wb_register_block'), 20);
	}

	public static function wb_enqueue_block_editor_assets()
	{
		/**
		 * 编輯器css
		 */
		wp_enqueue_style(
			'wb_block_editor',
			WP_VK_URL . '/assets/wb_block_editor.css',
			array('wp-edit-blocks'),
			WP_VK_ASSETS_VER
		);

		wp_enqueue_script(
			'wb_block',
			WP_VK_URL . '/assets/block/wb_block.js',
			array('wp-blocks', 'wp-element', 'wp-editor',  'wp-components', 'wp-data', 'wp-block-editor'),

			WP_VK_ASSETS_VER
		);
	}

	public static function wb_register_block()
	{
		$bind_post_types = WP_VK::cnf('bind_post_types', ['post']);
		if (is_admin()) {
			global $pagenow;
			$typenow = '';
			if ('post-new.php' === $pagenow) {
				if (!isset($_REQUEST['post_type'])) {
					$typenow = 'post';
				}
				if (isset($_REQUEST['post_type']) && post_type_exists($_REQUEST['post_type'])) {
					$typenow = $_REQUEST['post_type'];
				};
			} elseif ('post.php' === $pagenow) {
				if (isset($_GET['post']) && isset($_POST['post_ID']) && (int) $_GET['post'] !== (int) $_POST['post_ID']) {
					// Do nothing
				} elseif (isset($_GET['post'])) {
					$post_id = (int) $_GET['post'];
				} elseif (isset($_POST['post_ID'])) {
					$post_id = (int) $_POST['post_ID'];
				}

				if ($post_id) {
					$post = get_post($post_id);
					$typenow = $post->post_type;
				}
			}

			if (!in_array($typenow, $bind_post_types)) {
				return;
			}
		}
		add_action('enqueue_block_editor_assets', array(__CLASS__, 'wb_enqueue_block_editor_assets'));
		add_filter('block_categories_all', array(__CLASS__, 'register_wb_group'), 20, 2);


		/**
		 * 初始附加到特定文章类型
		 */
		$show_price = WP_VK::cnf('default_price', '');
		/**
		 * 设置meta字段
		 * 'default'=> $show_price 因5.5版本+才支持，暂不使用该配置
		 */

		foreach ($bind_post_types as $post_type) {
			register_post_meta($post_type, 'vk_price', array(
				'type' => 'string',
				'description' => '付费价格.',
				'single' => true,
				'show_in_rest' => true
			));
		}

		$pro = get_option('wp_vk_ver', 0);
		$pay_conf = WP_VK_Order::pay_conf();
		$set_pay = 0;
		if (isset($pay_conf['alipay']) && $pay_conf['alipay']) {
			$set_pay = 1;
		} else if (isset($pay_conf['wxpay']) && $pay_conf['wxpay']) {
			$set_pay = 1;
		}
		/**
		 * vkSetPay 支付设置状态
		 * vkSetPayUrl 支付设置页url
		 * customText 编辑器内提示文字
		 * vkMark 对应php处理分隔处理标识文字
		 * vkPrice 记录当前设置的price值 （新建时为0)
		 * defaultPrice 默认价格 (读取设置值）
		 */
		register_block_type('wb/wp-vk', array(
			'editor_script' => 'wb_block',
			'editor_style' => 'wb_block_editor',
			'attributes'      => [
				'defaultPrice' => [
					'default' => $show_price,
					'type'    => 'number',
				],
				'vkIsPro' => [
					'default' => $pro,
					'type'    => 'boolean',
				],
				'vkSettingUrl' => [
					'default' => esc_url(admin_url('admin.php?page=wp_vk#/')),
					'type'    => 'boolean',
				],
				'vkSetPay' => [
					'default' => $set_pay,
					'type'    => 'boolean',
				],
				'vkSetPayUrl' => [
					'default' => esc_url(admin_url('admin.php?page=wp_vk#/pay-setting')),
					'type'    => 'boolean',
				],
				'customText' => [
					'default' => '虚线以下为付费内容',
					'type'    => 'string',
				],
				'vkMark' => [
					'default' => '<!--以下为付费内容-->',
					'type'    => 'string',
				],
				'vkDesc' => [
					'default' => '此区块后的内容为付费内容，访问需付费后可见。请设定价格后确认。',
					'type'    => 'string',
				],
				'vkMetaPrice' => [
					'type' => 'string',
					'source' => 'meta',
					'meta' => 'vk_price'
				]
			],
			'version'         => WP_VK_VERSION
		));
	}

	/**
	 * 注册wbolt专用工具包
	 * @param $block_categories
	 * @param $editor_context
	 *
	 * @return mixed
	 */
	public static function register_wb_group($block_categories, $editor_context)
	{

		$slug_exists = array_search('wb-block', array_column($block_categories, 'slug'));

		if (!empty($editor_context->post) && !$slug_exists) {
			array_push(
				$block_categories,
				array(
					'slug'  => 'wb-block',
					'title' => 'Wbolt工具箱',
					'icon'  => null,
				)
			);
		}
		return $block_categories;
	}
}
