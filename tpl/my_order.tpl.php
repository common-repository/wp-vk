<?php

/**
 * 会员订单
 */
?>
<?php if (!$list) : ?>
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