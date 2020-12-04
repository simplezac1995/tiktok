<?php
// +----------------------------------------------------------------------
// | 系统参数设置
// +----------------------------------------------------------------------
//return include_once '../app/admin/config/sys.php';
return [
    //金銭出納帳タイプ
    'account_log_group'         =>[
        '1'=>'Top up',
        '2'=>'Referral bonuses',
        '3'=>'Task commission',
        '4'=>'Mission rewards',
        '5'=>'Withdrawal',
        '6'=>'Consumption',
		'7'=>'Daily tasks',
    ],
    //出納タイプ
    'account_log_types'         => [
        '11'=>'Background top-up',
        '12'=>'Member upgrade recharge',
        '41'=>'Mission Rewards',
        '42'=>'Task Assistant',
        '21'=>'Level 1 Recommendation award',
        '22'=>'Level 2 Recommendation award',
        '23'=>'Level 3 Recommendation award',
        '31'=>'Commission for level 1 tasks',
        '32'=>'Commission for level 2 tasks',
        '33'=>'Commission for level 3 tasks',
        '51'=>'Withdrawal',
        '52'=>'Withdrawal back',
        '61'=>'Upgrade Membership',
        '62'=>'Task Assistant',
		'71'=>'Immediate subordinate recharge reward',
    ],
    
    //任務の種類
    'task_type'             =>[
        '1'=>'Tiktok',
        '2'=>'Facebook',
        '3'=>'Newbie Task',
    ],
    
    //任務要求
    'task_ask'             =>[
        '1'=>'「Like」',
        '2'=>'Concern',
        '3'=>'「Like」,Concern',
        '4'=>'Retweeting moments&Add the customer service',
    ],
    
    
    //任務の申し込み状態
    'task_apply_status'    =>[
        '0'=>'Unfinished',
        '1'=>'Check pending',
        '2'=>'Finished',
        '-1'=>'Audit failure'
    ],
    
    //チャージの申し込み状態
    'recharge_status'       =>[
        '0'=>'Check pending',
        '1'=>'Finished',
        '-1'=>'Audit failure'
    ],
    
    //チャージの申し込み状態
    'cash_status'           =>[
        '0'=>'Check pending',
        '1'=>'Withdrawal success',
        '-1'=>'Withdrawal of failure'
    ],
    
    //広告タイプ
    'ad_type'           =>[
        '1'=>'Homepage banner',
        '2'=>'Homepage advertisement',
    ],
    
    //秘密問題
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