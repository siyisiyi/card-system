<?php
namespace App\Library\Pay\Qf; use App\Library\CurlRequest as Request; use App\Library\Pay\ApiInterface; use Illuminate\Support\Facades\Log; class Api implements ApiInterface { private $url_notify = ''; private $url_return = ''; public function __construct($spfc3b4d) { $this->url_notify = SYS_URL_API . '/pay/notify/' . $spfc3b4d; $this->url_return = SYS_URL . '/pay/return/' . $spfc3b4d; } function goPay($sp8abf69, $spbd054b, $sp547016, $speb838e, $sp6c12fc) { $sp50782f = strtolower($sp8abf69['payway']); if (!isset($sp8abf69['id'])) { throw new \Exception('请设置 id'); } $spdbbeeb = array(); if ($sp50782f == 'qq') { $spdbbeeb = array('User-Agent' => 'Mozilla/5.0 Mobile MQQBrowser/6.2 QQ/7.2.5.3305'); } elseif ($sp50782f == 'alipay') { $spdbbeeb = array('User-Agent' => 'Mozilla/5.0 AlipayChannelId/5136 AlipayDefined(nt:WIFI,ws:411|0|2.625) AliApp(AP/10.1.10.1226101) AlipayClient/10.1.10.1226101'); } $spe5644b = ''; $spf1bc42 = Request::get('https://o2.qfpay.com/q/info?code=&huid=' . $sp8abf69['id'] . '&opuid=&reqid=' . $spbd054b, $spe5644b, $spdbbeeb); $spd6e674 = static::strBetween($spf1bc42, 'reqid":"', '"'); $spd618a1 = static::strBetween($spf1bc42, 'currency":"', '"'); if ($spd6e674 == '' || $spd618a1 == '') { Log::error('qfpay pay, 获取支付金额失败 - ' . $spf1bc42); throw new \Exception('获取支付请求id失败'); } $spc5dcfd = Request::post('https://o2.qfpay.com/q/payment', 'txamt=' . $sp6c12fc . '&openid=&appid=&huid=' . $sp8abf69['id'] . '&opuid=&reqid=' . $spd6e674 . '&balance=0&currency=' . $spd618a1, $spe5644b, $spdbbeeb); $spbcb528 = json_decode($spc5dcfd, true); $sp588394 = static::strBetween($spc5dcfd, 'syssn":"', '"'); if (!$spbcb528 || $sp588394 == '') { Log::error('qfpay pay, 生成支付单号失败#1 - ' . $spc5dcfd); throw new \Exception('生成支付单号失败#1'); } if ($spbcb528['respcd'] !== '0000') { if (isset($spbcb528['respmsg']) && $spbcb528['respmsg'] !== '') { throw new \Exception($spbcb528['respmsg']); } Log::error('qfpay pay, 生成支付单号失败#2 - ' . $spc5dcfd); throw new \Exception('生成支付单号失败#2'); } \App\Order::whereOrderNo($spbd054b)->update(array('pay_trade_no' => $sp588394)); header('location: /qrcode/pay/' . $spbd054b . '/qf_' . $sp50782f . '?url=' . urlencode(json_encode($spbcb528['data']['pay_params']))); } function verify($sp8abf69, $spc98f69) { $sp7fd294 = \App\Order::whereOrderNo($sp8abf69['out_trade_no'])->firstOrFail(); $sp588394 = $sp7fd294->pay_trade_no; $sp0f0e97 = Request::get('https://marketing.qfpay.com/v1/mkw/activity?syssn=' . $sp588394); $sp789570 = json_decode($sp0f0e97, true); if (!$sp0f0e97) { throw new \Exception('query error'); } if (!isset($sp789570['respcd'])) { Log::error('qfpay query, 获取支付结果失败 - ' . $sp0f0e97); throw new \Exception('获取支付结果失败'); } if ($sp789570['respcd'] !== '0000') { return false; } $sp18a32c = (int) static::strBetween($sp0f0e97, 'trade_amt":', ','); if ($sp18a32c === 0) { $sp18a32c = (int) static::strBetween($sp0f0e97, 'txamt":', ','); if ($sp18a32c === 0) { Log::error('qfpay query, 获取支付金额失败 - ' . $sp0f0e97); throw new \Exception('获取支付金额失败'); } } if ($sp789570['respcd'] === '0000') { $spc98f69($sp8abf69['out_trade_no'], $sp18a32c, $sp588394); return true; } return false; } public static function strBetween($spb67857, $sp47b40f, $spe6a1c0) { $sp110fb8 = stripos($spb67857, $sp47b40f); if ($sp110fb8 === false) { return ''; } $sp18418b = stripos($spb67857, $spe6a1c0, $sp110fb8 + strlen($sp47b40f)); if ($sp18418b === false || $sp110fb8 >= $sp18418b) { return ''; } $spabcd03 = strlen($sp47b40f); $spc8aebe = substr($spb67857, $sp110fb8 + $spabcd03, $sp18418b - $sp110fb8 - $spabcd03); return $spc8aebe; } }