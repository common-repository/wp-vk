<?php

/**
 * 文章底部提示模块
 */

$vk_type = 0;
$need_login = !$user_id;

if ($opt['login_pay'] && $opt['guest_pay']) {
	$vk_type = 2;
	$need_login = 1;
} else if ($opt['guest_pay']) {
	$vk_type = 1;
	$need_login = 0;
}
?>

<div class="wb-vk-wp" rel="nofollow">
	<div class="wb-tips-txt"><?php echo $tips_tpl; ?></div>

	<div class="wpvk-pay-btns">
		<?php
		// 需登录时 el start
		// 提供购买方式
		if ($need_login) echo '<div id="wpvk-buy-wp" class="wpvk-buy-wp">'; ?>

		<?php
		/**
		 * 包含有登录购买的方式时
		 */
		if ($vk_type != 1) :

			// + 已登录
			if ($user_id) {

				// 登或非登同时支持时（提供有“登录购买”供选择，且有下一步
				if ($vk_type == 2) { ?>
					<a class="wpvk-btn wpvk-buy-next wpvk-primary" data-type="0" href="javascript:void(0);"><span>登录购买</span></a>
				<?php }
			}
			// + 未登录(加登录入口)
			else { ?>
				<a class="wpvk-btn wpvk-primary" href="<?php echo esc_url(wp_login_url($post_url)); ?>"><span>登录购买</span></a>
		<?php }

		endif; ?>

		<?php
		/**
		 * 登或非登同时支持时
		 * (提供免登购买选项)
		 */
		if ($vk_type == 2) : ?>
			<span class="wpvk-or">OR</span>
			<a class="wpvk-btn wpvk-buy-next" data-type="1" href="javascript:void(0);"><span>免登录购买</span></a>
		<?php endif; ?>

		<?php
		// 需登录时 el end
		if ($need_login) echo '</div>';
		?>

		<?php
		/**
		 * 支付方式
		 * (若只有登录购买1种方式 且已登录 || 只有免登录购买1种方式时 直接显示，否则隐藏)
		 */
		?>
		<div class="wpwk-pay-types<?php if ($vk_type == 2 || $need_login) echo ' wpwk-def-hidden'; ?>" id="wpwk-pay-wp">
			<?php
			if (empty($pay)) {
				echo '-- 未配置支付方式 --';
			}
			$type_attr = '';

			// 仅免登录时
			if ($vk_type == 1) {
				$type_attr = 'data-vk="1"';
			}

			if ($pay && in_array('ali', $pay)) {
				$pay_url_ali = WP_VK::pay_url('pay', array('id' => $post_id, 'pay_type' => 'alipay'));
			?>
				<a class="wpvk-btn wpvk-vk-pay" <?php echo $type_attr; ?> data-pay_url="<?php echo esc_attr($pay_url_ali); ?>" rel="nofollow">
					<svg xmlns="http://www.w3.org/2000/svg" style="width:16px; height:16px;" viewBox="0 0 32 32" fill="none">
						<path fill="#009FE9" d="M32 26.7v.2a5.1 5.1 0 01-5.1 5H5A5.1 5.1 0 010 27V5A5.1 5.1 0 015.1 0H27a5.1 5.1 0 015 5.1v21.6z" />
						<path fill="#fff" d="M26.4 20.3l-4.9-1.8c1.2-2.2 2.2-4.6 2.8-7H18V9.6h7.7V8.2l-7.7.1V4.5h-3.1c-.6 0-.7.6-.7.6v3.2H6.4v1.3h7.7v2H7.7v1.2h12.8c-.5 1.6-1.2 3.2-2 4.6-4-1.3-8.4-2.4-11.1-1.7-1.4.3-2.6 1-3.5 2-3 3.6-.8 9.2 5.5 9.2 3.7 0 7.3-2.1 10.1-5.6 4.2 2 12.5 5.5 12.5 5.5V22s-1-.1-5.6-1.6zM8.8 24.8c-5 0-6.4-4-4-6a6 6 0 012.9-1.5c2.9-.3 6 1 9.2 2.7-2.4 3-5.3 4.8-8.1 4.8z" />
					</svg>
					<span>支付宝</span>
				</a>
			<?php }

			if ($pay && in_array('wx', $pay)) {
				$pay_url_wx = WP_VK::pay_url('pay', array('id' => $post_id, 'pay_type' => 'weixin'));
			?>
				<a class="wpvk-btn wpvk-vk-pay" <?php echo $type_attr; ?> data-pay_url="<?php echo esc_attr($pay_url_wx); ?>" rel="nofollow">
					<svg xmlns="http://www.w3.org/2000/svg" style="width:16px; height:16px;" fill="none" viewBox="0 0 32 32">
						<path fill="#41B035" d="M12.2 20.1c-2 1.2-2.2-.6-2.2-.6l-2.4-6c-1-3 .8-1.4.8-1.4s1.5 1.3 2.6 2 2.4.2 2.4.2l15.7-7.8C26.1 2.5 21.4 0 16 0 7.2 0 0 6.8 0 15.2c0 4.8 2.4 9 6 11.9l-.6 4.1s-.3 1.2.8.7c.8-.4 2.7-1.8 3.9-2.6 1.9.7 3.9 1 5.9 1 8.8 0 16-6.8 16-15.1 0-2.5-.6-4.8-1.7-6.8L12.2 20.1z" />
					</svg>
					<span>微信支付</span>
				</a>
			<?php } ?>

		</div>

		<div class="wpvk-login-tips">
			<?php if ($vk_type == 2) { ?>
				如已付费购买，请<?php if (!$user_id) { ?>
				<a class="link" href="<?php echo esc_url(wp_login_url($post_url)); ?>">登录</a> 或
			<?php } ?>
			<a class="link vk-buy-verify" data-post_id="<?php echo esc_attr($post_id); ?>" href="javascript:void(0);">免登录验证</a>。
		<?php } else if ($vk_type == 1) { ?>
			如已付费购买，请<a class="link vk-buy-verify" data-post_id="<?php echo esc_attr($post_id); ?>" href="javascript:void(0);">免登录验证</a>。
			<?php } else {
				if (!$user_id) { ?>
				如已付费购买，请<a class="link" href="<?php echo esc_url(wp_login_url($post_url)); ?>">登录</a>。
		<?php }
			} ?>

		<?php if ($post->post_author == get_current_user_id()) { ?>
			当前登录账号为文章作者，可<a class="link" onclick="document.querySelector('.wb-vk-wp').style.display='none';document.querySelector('.wb-vk-cont').style.display='block'">直接查看</a>付费内容。
		<?php } ?>
		</div>
	</div>
</div>