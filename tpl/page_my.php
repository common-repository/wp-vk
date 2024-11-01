<?php

/**
 * Template Name: #WP-VK 我的订单
 */

if (!is_user_logged_in()) {
  wp_redirect(wp_login_url($_SERVER['REQUEST_URI']));
  exit;
}

add_filter('document_title_parts', function ($parts) {
  $parts['title'] = '会员订单';
  return $parts;
});

add_action('wp_enqueue_scripts', 'member_header');
function member_header()
{
  $opt = WP_VK::cnf();

  $data = '';
  // 暗黑激活
  if ($dm_cls = $opt['dark_mode_class']) {
    $data .= $dm_cls . '{ --wb-vk-bfc: #c3c3c3; --wb-vk-fcs: #fff; --wb-vk-wk: #999; --wb-vk-wke: #686868; --wb-vk-bgc: #2b2b2b; --wb-vk-bbc: #4d4d4d; --wb-vk-bgcl: #353535; }';
  }

  wp_enqueue_style('wp-vk-member', WP_VK_URL . '/assets/wb_my.css', false, WP_VK_ASSETS_VER);
  wp_add_inline_style('wp-vk-member', $data);
}

//add_action();

//页面
get_header();

?>
<div class="wpvk-content pw">
  <div class="side-mb">
    <div class="side-hd">
      <span class="user-cover" href="<?php echo esc_url(home_url('/')); ?>"><?php global $current_user;
                                                                            echo get_avatar($current_user->user_email, 60); ?></span>
      <span class="user-name">Hi <?php echo esc_html($current_user->display_name); ?></span>
    </div>
    <ul class="nav-side-mb">
      <li class="current">
        <a>
          <svg class="wb-icon wbsico-search ">
            <use xlink:href="#wbsico-order"></use>
          </svg>
          <span>我的订单</span>
        </a>
      </li>
      <li>
        <a href="<?php echo esc_url(wp_logout_url()); ?>" id="J_logout">
          <svg class="wb-icon wbsico-search ">
            <use xlink:href="#wbsico-logout"></use>
          </svg>
          <span>登出</span>
        </a>
      </li>
    </ul>
  </div>

  <div class="wpvk-main main-mb">
    <ul class="wb-tab-navs">
      <li class="tn-item current"><a>我的订单</a></li>
    </ul>

    <div class="mb-content-table" id="J_mbOrderList">
      <?php if (empty($list)) : ?>
        <div class="com-empty-rec">
          <div class="cer-inner">
            <svg xmlns="http://www.w3.org/2000/svg" width="71" height="72">
              <g fill="none" fill-rule="evenodd" stroke="#DFDFDF" stroke-linecap="round" stroke-linejoin="round" stroke-width="4">
                <path d="M62 27.2L59 48H16.8L10.5 6H2M13 17h28" />
                <path d="M56 2a13 13 0 1 1 0 26 13 13 0 0 1 0-26zM56 11v8M52 15h8M21 58a6 6 0 1 1 0 12 6 6 0 0 1 0-12zM57 58a6 6 0 1 1 0 12 6 6 0 0 1 0-12z" />
              </g>
            </svg>
            <p>您还没有订单</p>
          </div>
        </div>
      <?php else : ?>

        <table class="table table-data-list">
          <thead>
            <tr>
              <th>订单号</th>
              <th class="w30">文章</th>
              <th>金额</th>
              <th>日期</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($list as $r) { ?>
              <tr>
                <td><?php echo esc_html($r->order_no); ?></td>
                <td><a href="<?php echo esc_url(get_permalink($r->pid)); ?>"><?php echo esc_html($r->name); ?></a></td>
                <td>&yen;<?php echo esc_html($r->money); ?></td>
                <td><?php echo esc_html($r->created); ?></td>
              </tr>
            <?php } ?>
          </tbody>
        </table>

      <?php endif; ?>

    </div>
  </div>
</div>

<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" overflow="hidden" style="position:absolute;width:0;height:0; display: none;">
  <defs>
    <symbol id="wbsico-order" viewBox="0 0 14 16">
      <g fill-rule="evenodd">
        <path d="M13 0H1C.4 0 0 .4 0 1v14c0 .6.4 1 1 1h12c.6 0 1-.4 1-1V1c0-.6-.4-1-1-1zM2 2h10v12H2V2z" />
        <path d="M3 3h4v4H3zM8 4h3v1H8zM8 6h3v1H8zM3 8h8v1H3zM3 10h8v1H3zM3 12h5v1H3z" />
      </g>
    </symbol>
    <symbol id="wbsico-logout" viewBox="0 0 16 16">
      <g fill-rule="evenodd">
        <path d="M5 12.6l-3-3V3.4l3 3v6.2zM3.4 2H8v2h2V1c0-.6-.4-1-1-1H1C.4 0 0 .4 0 1v9c0 .3.1.5.3.7l5 5c.2.2.4.3.7.3.1 0 .3 0 .4-.1.4-.1.6-.5.6-.9V6c0-.3-.1-.5-.3-.7L3.4 2z" />
        <path d="M15.7 7.3L12 3.6 10.6 5l2 2H8v2h4.6l-2 2 1.4 1.4 3.7-3.7c.4-.4.4-1 0-1.4" />
      </g>
    </symbol>
  </defs>
</svg>
<?php
get_footer();
?>