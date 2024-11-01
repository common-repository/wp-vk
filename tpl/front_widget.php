<?php

/**
 *
 */


if (isset($instance['style']) && $instance['style'] != '') {
  echo '<style>' . $instance['style'] . '</style>';
}

?>
<style>
  .widget_wb_wp_vk_widget {
    margin-top: 30px;
  }

  .widget_wb_wp_vk_widget .wpvk-btn {
    box-sizing: border-box;
    display: inline-block;
    padding: 0 10px;
    height: 32px;
    line-height: 30px;
    border: 1px solid var(--wb-vk-bbc, #ddd);
    min-width: 100px;
    border-radius: 3px;
    text-align: center;
    font-size: 12px;
    text-decoration: none !important;
    vertical-align: middle;
    color: #999;
  }

  .widget_wb_wp_vk_widget .widget-content {
    padding: 20px 0
  }

  .widget_wb_wp_vk_widget .wpvk-login-btn {
    min-width: 80px;
    margin-right: 10px
  }

  .widget_wb_wp_vk_widget .wpvk-empty-tips {
    padding: 20px 10px;
    text-align: center;
    color: #999;
    font-size: 12px
  }

  .widget_wb_wp_vk_widget .wpvk-widget-bottom {
    padding: 15px;
    text-align: center
  }
</style>
<?php
echo $args['before_widget'];
if ($title) {
  echo $args['before_title'] . $title . $args['after_title'];
}

?>
<?php if (is_user_logged_in()) : ?>
  <?php if (empty($list)) {
    echo '<div class="wpvk-empty-tips">-空空如也-</div>';
  } else { ?>
    <ul>
      <?php foreach ($list as $item) : ?>
        <li>
          <a href="<?php echo esc_url(get_permalink($item->pid)); ?>"><?php echo esc_html($item->name); ?></a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php } ?>

  <div class="wpvk-widget-bottom">
    <a class="wpvk-more-btn wbp-act-mbc" data-target="vk-order" href="<?php echo esc_url(home_url('?wbp=member&slug=vk')); ?>">我的订单 &gt;</a>
  </div>

<?php else : ?>
  <div class="wpvk-empty-tips">
    <a class="wpvk-login-btn wpvk-btn" href="<?php echo esc_url(wp_login_url($_SERVER['REQUEST_URI'])); ?>">登录</a><span>查看我的付费内容</span>
  </div>
<?php endif; ?>
<?php echo $args['after_widget']; ?>