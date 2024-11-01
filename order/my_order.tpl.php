<?php
$pd_title = '我的订单';
include WP_VK_PATH . '/tpl/wbs_admin.tpl.php';
?>

<div class="wrap">
	<?php include WP_VK_PATH . '/tpl/wbs_header.tpl.php'; ?>


	<form action="" method="post">

		<table class="widefat striped fixed" cellspacing="0">
			<thead>
				<tr>
					<th scope="col" class="manage-column column-name" width="11%">订单号</th>
					<th scope="col" class="manage-column column-name" width="10%">日期</th>
					<th scope="col" class="manage-column column-name" width="20%">文章</th>
					<th scope="col" class="manage-column column-name" width="6%">金额</th>
					<th scope="col" class="manage-column column-name" width="8%">支付方式</th>
					<th scope="col" class="manage-column column-name" width="8%">支付状态</th>
				</tr>
			</thead>

			<tbody>
				<?php

				if (isset($list) && count($list) > 0) {

					if (is_array($list)) foreach ($list as $v) {
				?>
						<tr valign="middle" id="link-2">
							<td class="column-name"><?php echo esc_html($v->order_no); ?></td>
							<td class="column-name"><?php echo esc_html($v->created); ?></td>
							<td class="column-name"><a href="<?php echo esc_url(get_permalink($v->pid)); ?>" target="_blank"><?php echo esc_html($v->name); ?></a></td>
							<td class="column-name"><?php echo esc_html($v->money); ?></td>
							<td class="column-name"><?php echo esc_html($pay_types[$v->pay_type]); ?></td>
							<td class="column-name"><?php echo esc_html($pay_status[$v->pay_status]); ?></td>
						</tr>
					<?php
					}
				} else { ?>
					<tr>
						<td colspan="6">暂无记录</td>
					</tr>
				<?php } ?>
			</tbody>


		</table>

		<div class="tablenav">

			<?php echo $pages; ?>
			<br class="clear">
		</div>
	</form>

</div>