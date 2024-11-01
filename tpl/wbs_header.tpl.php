<?php
/**
 * wbolt 通用设置页页头
 */
?>
<div class="wbs-header">
    <svg class="wb-icon sico-wb-logo"><use xlink:href="#sico-wb-logo"></use></svg>
    <span>WBOLT</span>
    <strong><?php echo esc_html(isset($pd_title) ? $pd_title : '设置'); ?></strong>

    <div class="links">
        <a class="wb-btn" href="<?php echo esc_url($pd_index_url); ?>" data-wba-campaign="title-bar" target="_blank">
            <svg class="wb-icon sico-plugins"><use xlink:href="#sico-plugins"></use></svg>
            <span>插件主页</span>
        </a>
        <a class="wb-btn" href="<?php echo esc_url($pd_doc_url); ?>" data-wba-campaign="title-bar" target="_blank">
            <svg class="wb-icon sico-doc"><use xlink:href="#sico-doc"></use></svg>
            <span>说明文档</span>
        </a>
    </div>
</div>