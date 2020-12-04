<?php
// +----------------------------------------------------------------------
// | 系统字典设置
// +----------------------------------------------------------------------

return [
    //账户流水分类
    'account_log_group'         =>[
        '1'=>'充值',
        '2'=>'推荐奖励',
        '3'=>'任务返佣',
        '4'=>'任务奖励',
        '5'=>'提现',
        '6'=>'消费',
        '7'=>'每日任务'
    ],
    //账户流水小类
    'account_log_types'         => [
        '11'=>'后台充值',
        '12'=>'会员升级充值',
        '41'=>'任务奖励',
        '42'=>'任务助手',
        '21'=>'一级推荐奖励',
        '22'=>'二级推荐奖励',
        '23'=>'三级推荐奖励',
        '31'=>'一级任务返佣',
        '32'=>'二级任务返佣',
        '33'=>'三级任务返佣',
        '51'=>'提现',
        '52'=>'提现退回',
        '61'=>'升级会员',
        '62'=>'任务助手',
        '71'=>'直属下级充值奖励'
    ],
    
    //任务类型
    'task_type'             =>[
        '1'=>'抖音',
        '2'=>'脸书',
        '3'=>'新手任务',
    ],
    
    //任务要求
    'task_ask'             =>[
        '1'=>'点赞',
        '2'=>'关注',
        '3'=>'点赞,关注',
        '4'=>'转发朋友圈,添加客服',
    ],
    
    
    //任务申请状态
    'task_apply_status'    =>[
        '0'=>'未完成',
        '1'=>'待审核',
        '2'=>'完成',
        '-1'=>'审核失败'
    ],
    
    //充值申请状态
    'recharge_status'       =>[
        '0'=>'待审核',
        '1'=>'审核通过',
        '-1'=>'审核失败'
    ],
    
    //充值申请状态
    'cash_status'           =>[
        '0'=>'待审核',
        '1'=>'提现成功',
        '-1'=>'提现失败'
    ],
    
    //广告类型
    'ad_type'           =>[
        '1'=>'首页Banner',
        '2'=>'首页广告',
    ],
    
    //密保问题
    'questions'          =>[
        "Where is my birthplace?",
        "What was the name of the first school I attended?",
        "What's my student number (job number)?",
        "What is the name of my head teacher in junior high school?",
        "What's the name of my favorite person?",
        "What's my mother's name?",
    ],
    
    //银行
    'banks'     =>[
        'BBVA Compass',
        'CitiBank',
        'HSBC Bank',
        'Chase',
        'Capital One',
        'Bank of America',
        'VISA',
        'Master',
        'American Express',
        'Discover',
    ]
];