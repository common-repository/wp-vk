<?php

/**
 * Author: wbolt team
 * Author URI: https://www.wbolt.com
 */
class WP_VK_Utils
{



    public static function limit($pagesize)
    {
        $paged = isset($_GET['paged']) ? abs($_GET['paged']) : 1;
        $_GET['paged'] = $paged = $paged ? $paged : 1;

        $pagesize = $pagesize ? abs($pagesize) : 10;

        return 'LIMIT ' . (($paged - 1) * $pagesize) . ',' . $pagesize;
    }

    public static function a2m($a, $kfield, $vfield = null, $value_array = false)
    {
        $ret = array();
        foreach ($a as $v) {
            $k = $v[$kfield];
            if ($vfield) {
                $v = $v[$vfield];
            }
            if ($value_array) {
                if (!isset($ret[$k])) $ret[$k] = array();
                $ret[$k][] = $v;
            } else {

                $ret[$k] = $v;
            }
        }
        return $ret;
    }

    public static function urls($param = array())
    {

        if (!is_array($param)) $param = array($param);
        if (isset($_GET['page']) && $_GET['page']) $param = array_merge(array('page' => absint($_GET['page'])), $param);

        $current_url = set_url_scheme('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);

        $query = http_build_query($param);
        return esc_url($current_url . '?' . $query);
    }

    public static function pages($param)
    {
        //总页数
        $mxpage = ceil($param['total'] / $param['pagesize']);
        $paged = isset($_GET['paged']) && $_GET['paged'] ? absint($_GET['paged']) : 1; //$param['paged'];
        $url_param = $param;

        //首页
        if (isset($url_param['paged'])) unset($url_param['paged']);
        $home = self::urls($url_param);
        //上一页
        $url_param['paged'] = $paged - 1;
        $preurl = self::urls($url_param);
        //下一页
        $url_param['paged'] = $paged + 1;
        $nxurl = self::urls($url_param);

        //最后一页
        $url_param['paged'] = $mxpage;
        $lastpage = self::urls($url_param);

        //$preurl = self::urls(array('paged'=>$paged-1));

        $html = '<div class="tablenav-pages"><span class="displaying-num">' . $param['total'] . '项目</span><span class="pagination-links">';
        if ($paged == 1) {
            $html .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>';
            $html .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span> ';
        } else if ($mxpage > 1) {
            if ($paged == 2) {
                $html .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>';
            } else {
                $html .= '<a class="first-page button" href="' . $home . '"><span class="screen-reader-text">首页</span><span aria-hidden="true">«</span></a>';
            }
        }
        if ($paged > 1) {
            $html .= '<a class="prev-page button" href="' . $preurl . '"><span class="screen-reader-text">上一页</span><span aria-hidden="true">‹</span></a>';
        }

        $html .= '<span class="screen-reader-text">当前页</span>';
        $html .= '<span id="table-paging" class="paging-input">第' . $paged . '页，共<span class="total-pages">' . $mxpage . '</span>页</span>';

        if ($paged < $mxpage) {
            $html .= '<a class="next-page button" href="' . $nxurl . '"><span class="screen-reader-text">下一页</span><span aria-hidden="true">›</span></a>';
            if ($paged + 1 == $mxpage) {
                $html .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>';
            } else {
                $html .= '<a class="last-page button" href="' . $lastpage . '"><span class="screen-reader-text">尾页</span><span aria-hidden="true">»</span></a>';
            }
        }

        if ($mxpage == $paged) {
            $html .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>';
            $html .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>';
        }


        $html .= '</span></div>';

        return $html;
    }
}
