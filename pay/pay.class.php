<?php

/**
 * 支付方式
 * User: zhiquan
 * Date: 2016/11/8
 * Time: 10:20
 */


class WP_VK_Pay
{


    public function __construct()
    {
    }


    public static function init()
    {

        if (is_admin()) {
            add_action('wp_ajax_vk_pay_setting', array(__CLASS__, 'wp_ajax_vk_pay_setting'));
        }
    }

    public static function pay_setting()
    {
    }

    public static function default_cnf()
    {
        $def = array(
            'type' => '2',
            'types' => array(
                2 => array(
                    'ver' => '1', //[1=>'虎皮椒',2=>'迅虎']
                    'wx' => array(
                        'open' => 0,
                    ),
                    'ali' => array(
                        'wap' => '1', //wap 支付1.0, wap2.0
                        'open' => 0,
                    )
                ),
                3 => array(
                    'logo' => '',
                    'wx' => array(
                        'open' => 0,
                        'native' => 1,
                        'h5' => 0
                    ),
                    'ali' => array(
                        'open' => 0,
                    )
                ),
                10 => array(
                    'wx' => array(
                        'open' => 0,
                        'native' => 1,
                        'h5' => 0
                    ),
                    'ali' => array(
                        'open' => 0,
                        'native' => 1,
                        'h5' => 0
                    )
                )
            ),
        );

        return $def;
    }

    public static function cnf()
    {
        $def = self::default_cnf();
        $pay = get_option('vk_pay_conf');
        if (!$pay) {
            return $def;
        }
        self::initCnf($pay, $def);
        return $pay;
    }

    public static function initCnf(&$opt, $cnf)
    {
        if (is_array($cnf)) foreach ($cnf as $k => $v) {
            if (!isset($opt[$k])) {
                $opt[$k] = $v;
                continue;
            }
            if (is_array($v)) {
                self::initCnf($opt[$k], $v);
            }
        }
    }

    public static function order_pay_cnf()
    {
        $opt = self::cnf();
        $conf = [
            'logo' => '',
        ];
        $types = $opt['types'];
        $type = $opt['type'];
        $conf['type'] = intval($type);
        if (!isset($types[$type])) {
            $types[$type] = ['ali' => ['open' => 0], 'wx' => ['open' => 0]];
        }
        $pay = $types[$type];

        $alipay = $pay['ali'];
        $wxpay = $pay['wx'];
        if (isset($pay['logo'])) {
            $conf['logo'] = $pay['logo'];
        }
        if (isset($pay['ver'])) {
            $conf['ver'] = $pay['ver'];
        }


        if ($alipay['open']) {
            if ($conf['type'] == 1 && $alipay['qrcode']) {
                $conf['alipay'] = $alipay;
            } else if (in_array($conf['type'], array(2, 3)) && $alipay['appid'] && $alipay['appsecret']) {
                $conf['alipay'] = $alipay;
            } else if ($conf['type'] == 10 && $alipay['appid'] && $alipay['alipay_key'] && $alipay['public_key'] && $alipay['private_key']) {
                $conf['alipay'] = $alipay;
            }
        }

        if ($wxpay['open']) {
            if ($conf['type'] == 1 && $wxpay['qrcode']) {
                $conf['wxpay'] = $wxpay;
            } else if (in_array($conf['type'], array(2, 3)) && isset($wxpay['appid']) && $wxpay['appid'] && $wxpay['appsecret']) {
                $conf['wxpay'] = $wxpay;
            } else if ($conf['type'] == 10 && $wxpay['appid'] && $wxpay['appsecret'] && $wxpay['merchantid'] && $wxpay['key']) {
                $conf['wxpay'] = $wxpay;
            }
        }

        //[alipay=>[],wxpay=>[],logo=>'',ver=>'']
        return $conf;
    }

    public static function wp_ajax_vk_pay_setting()
    {
        $ret = array('code' => 0, 'desc' => 'success');

        if (!is_user_logged_in()) {
            echo wp_json_encode(array('code' => 1, 'desc' => 'fail'));

            exit();
        }

        if (!current_user_can('manage_options')) {
            $ret['code'] = 2;
            $ret['desc'] = __('You do not have sufficient permissions to access this page.');

            echo wp_json_encode($ret);
            exit();
        }

        $op = $_POST['do'] ?? '';

        switch ($op) {
            case 'get_pay_options':
                $ret['data'] = array();

                $def = self::default_cnf();

                //$pay = get_option('vk_pay_conf',$def);

                $pay = self::cnf();

                if (!isset($def['types'][$pay['type']])) {
                    $pay['type'] = 2;
                }


                $ret['data']['pay'] = $pay;


                header('content-type:text/json;charset=utf-8');
                echo wp_json_encode($ret);
                exit();

                break;

            case 'set_pay_options':

                $ret = array('code' => 1, 'desc' => 'fail', 'data' => []);
                do{
                    if (!wp_verify_nonce($_POST['_ajax_nonce'], 'wp_ajax_vk_admin')) {
                        $ret['desc'] = '非法操作';
                        break;
                    }
                    if (isset($_POST['pay']) && $_POST['pay']) {

                        $pay = $_POST['pay'];
                        if (isset($pay['types']) && is_array($pay['types'])) {
                            foreach ($pay['types'] as $k => $v) {
                                if (isset($v['ali']['alipay_key'])) {
                                    $pay['types'][$k]['ali']['alipay_key'] = self::fix_key($v['ali']['alipay_key'], 'public');
                                }
                                if (isset($v['ali']['public_key'])) {
                                    $pay['types'][$k]['ali']['public_key'] = self::fix_key($v['ali']['public_key'], 'public');
                                }
                                if (isset($v['ali']['private_key'])) {
                                    $pay['types'][$k]['ali']['private_key'] = self::fix_key($v['ali']['private_key'], 'private');
                                }
                            }
                        }

                        update_option('vk_pay_conf', $pay, true);
                    }

                    $ret = array('code' => 0, 'desc' => 'success', 'data' => $pay);
                }while(0);




                header('content-type:text/json;charset=utf-8');
                echo wp_json_encode($ret);
                exit();
                break;
        }
        exit();
    }

    public static function fix_key($key, $type = 'public')
    {
        $key = trim($key);
        if (!$key || preg_match('#^--#', $key)) {
            return $key;
        }

        $types = array(
            'public' => 'PUBLIC',
            'private' => 'RSA PRIVATE',
        );
        $key = str_replace(array("\n", "\r"), '', $key);

        $foremat_key = sprintf("-----BEGIN %s KEY-----\n", $types[$type]) .
            wordwrap($key, 64, "\n", true) .
            sprintf("\n-----END %s KEY-----", $types[$type]);
        return $foremat_key;
    }
}
