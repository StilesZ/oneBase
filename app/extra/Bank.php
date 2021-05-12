<?php

return [
    //云闪付支付

    'NingBo' => [

        // 商户号
        'appId' => '3000010000',

        //商户测试私钥
        'pfx' => '../extend/bank/cert/3000010000.pfx',

        //私钥密码
        'pfxPwd' => '1',

        //商户测试证书
        'cert' => '../extend/bank/cert/3000010000.cer',

        //支付网关测试公钥
        'paycert' => '../extend/bank/cert/paygate.cer',
    ],
];