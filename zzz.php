<?php

require_once './telegram_api.php'; // TelegramBot class 

$botToken = '881857044:AAF-3jC8mrsI9RoKo7xnod2_wUWyj1PHomQ'; // Replace it with your bot_token from botFather
$this_page_url = 'https://aminomer.com/khamsat/khamsat_bot.php'; // replace it with your file url (current file url)

$chats_ids = array();

$users_ = file_get_contents('users.txt');
$users_ = explode("\n", $users_);
$users = array();

foreach($users_ as $user){
    if(!$user)continue;
    $user_ = explode('-', $user);
    $chat_id = trim($user_[0]);
    if(!$chat_id || !is_numeric($chat_id))continue;
    $chats_ids[] = $chat_id;
    $users[$chat_id]['role'] = 'user';
    $users[$chat_id]['name'] = '-';
    if(isset($user_[1]) && trim($user_[1]) == 'admin')$users[$chat_id]['role'] = 'admin';
    if(isset($user_[2]) && trim($user_[2]))$users[$chat_id]['name'] = trim($user_[2]);
}

$bot = new TelegramBot($botToken);

if(isset($_GET['get_requests'])){
    require_once './get_khamsat_requests.php';
    $last_requests = get_last_new_requsets($requests);
    $msgs = get_requsets_msgs($last_requests);
    foreach($msgs as $msg){
        foreach($chats_ids as $chat_id){
            // $row = array('view' => 'عرض التفاصيل');
            // $bot->sendOneRow($msg, $row, $chat_id);
            $bot->sendMessage($msg, $chat_id);
        }
    }
    // print_r($requests);
    echo 'done !!';
    exit;
}

// you need to set webhook url (this_page) to telegram api (Once only - for the first time)
if(isset($_GET['install'])){
    $result = $bot->install($this_page_url);
    echo $result;
    exit;
}



//////////////////////////////////////// Send Messages
if(isset($users[$bot->chatId]['role']) && $users[$bot->chatId]['role'] == 'admin')$isAdmin = true; else $isAdmin = false;

if($isAdmin && strpos($bot->message, 'add') === 0){
    $params = explode('-', $bot->message);
    $chat_id = trim($params[1]);
    $name = trim($params[2]);
    $users_ = file_get_contents('users.txt');
    $users_ = explode("\n", $users_);
    $users_[] = $chat_id . ' - user - ' . $name;
    $users_ = implode("\n", $users_);
    file_put_contents('users.txt', $users_);
    $users_ = file_get_contents('users.txt');
    $bot->sendMessage($users_);
    exit;
}elseif($isAdmin && strpos($bot->message, 'delete') === 0){
    $params = explode('-', $bot->message);
    $chat_id = trim($params[1]);
    $users_ = file_get_contents('users.txt');
    $users_ = explode("\n", $users_);
    foreach($users_ as $k => $user){
        if(strpos($user, "$chat_id") !== false)unset($users_[$k]);
    };
    $users_ = implode("\n", $users_);
    file_put_contents('users.txt', $users_);
    $users_ = file_get_contents('users.txt');
    $bot->sendMessage($users_);
    exit;
}elseif($isAdmin && strpos($bot->message, 'users') === 0){
    $users_ = file_get_contents('users.txt');
    $bot->sendMessage($users_);
    exit;
}

switch($bot->message){
    case 'hi':
        $bot->sendMessage('Hello ' . $bot->firstName . ' ' . $bot->lastName . '!! how are you?');
    break;
    case '/requests':
        require_once './get_khamsat_requests.php';
        $last_requests = get_last_new_requsets($requests);
        $msgs = get_requsets_msgs($last_requests);
        foreach($msgs as $msg){
            $row = array('view' => 'عرض التفاصيل');
            // $bot->sendOneRow($msg, $row);
            $bot->sendMessage($msg);
        }
    break;
    case 'a':
        $msg = 'amin';
        $row = array('aaa' => 'bbb');
        $bot->sendOneRow($msg, $row);
        $bot->sendMessage('AminOmerM@gmail.com');
    break;
    case 'view':
        $url = '';
        foreach($bot->mData['callback_entities'] as $entity){
            if(isset($entity['url'])){
                $url = $entity['url'];
            }
        }
        if($url){
            $request = get_full_request($url);
            $msg = get_requset_msg($request);
            $bot->editMessage($msg);
            echo "done";
            exit;
        }
    break;
    case 'chat_id':
        $bot->sendMessage($bot->chatId);
    break;
    case 'إشتراك':
    case 'اشتراك':
        if(isset($users[$bot->chatId])){
            $bot->sendMessage('أنت مشترك مسبقا ✅' . "\n\n" . 'لإلغاء الإشتراك أكتب "إلغاء الإشتراك"' . "\n\n⚡️⚡️");
            exit;
        }else{
            $users_ = file_get_contents('users.txt');
            $users_ = explode("\n", $users_);
            $users_[] = $bot->chatId . ' - user - ' . str_replace('-','_', $bot->firstName . ' ' . $bot->lastName);
            $users_ = implode("\n", $users_);
            file_put_contents('users.txt', $users_);
            $msg = '';
            $msg .= "\n" . 'تم الإشتراك بنجاح ✅';
            $msg .= "\n" . '';
            $msg .= "\n" . '📡¦ سيتم تزويدك بالطلبات الجديدة فور نشرها منذ هذه اللحظة';
            $msg .= "\n" . '👫¦ هل تجد البوت مفيد؟ ادع اصدقاءك الان @khamsat_bot';
            $msg .= "\n" . '';
            $msg .= "\n" . 'لإلغاء الإشتراك أكتب "إلغاء الإشتراك"';
            $msg .= "\n" . '';
            $msg .= "\n" . '⚡️⚡️';
            $bot->sendMessage($msg);
            exit;
        }
        
    case 'إلغاء الإشتراك':
    case 'إلغاء الاشتراك':
    case 'الغاء الإشتراك':
    case 'الغاء الاشتراك':
        if(isset($users[$bot->chatId])){
            $users_ = file_get_contents('users.txt');
            $users_ = explode("\n", $users_);
            foreach($users_ as $k => $user_){
                if(strpos($user_, "$bot->chatId") !== false)unset($users_[$k]);
            }
            $users_ = implode("\n", $users_);
            file_put_contents('users.txt', $users_);
            $bot->sendMessage('تم إلغاء الإشتراك بنجاح ✅' . "\n" . 'أكتب "إشتراك" للإشتراك مرة أخرى');
            exit;
        }else{
            $bot->sendMessage('عفوا، انت غير مشترك !!');
            exit;
        }
        
    break;
    default:
    
        $msg = '';
        if(isset($users[$bot->chatId])){
            $msg .= "\n" . '💸¦ أهلا عزيزي، هذا بوت خمسات لعرض أحدث الخدمات المطلوبة';
            $msg .= "\n" . '';
            $msg .= "\n" . '🔹 سأرسل لك الخدمات المعروضة في طلبات الخدمات غير الموجودة ';
            $msg .= "\n" . '🔹 مخصص للخدمات المتعلقة بالبرمجة فقط 👌';
            $msg .= "\n" . '🔹 سأرسل لك طلب الخدمة مع المحتوى والرابط.. ';
            $msg .= "\n" . '🔹 يتم الارسل فور النشر على خمسات ⏱';
            $msg .= "\n" . '';
            $msg .= "\n" . '💎 (أنت مشترك مسبقا ✅)';
            $msg .= "\n" . '👈 أكتب "إلغاء الإشتراك" لإيقاف البوت وإلغاء الإشتراك';
            $msg .= "\n" . '';
            $msg .= "\n" . '📡¦ أهدي هذه الخدمة المجانية لأصدقاءك المبرمجين، سيسعدون بهذه الخدمة 🌸';
            $msg .= "\n" . '@khamsat_bot';
            $msg .= "\n" . '';
            $msg .= "\n" . '✔️';
            $msg .= "\n" . '🌸🌸';
        }else{
            $msg .= "\n" . '💸¦ أهلا عزيزي، هذا بوت خمسات لعرض أحدث الخدمات المطلوبة';
            $msg .= "\n" . '';
            $msg .= "\n" . '🔹 سأرسل لك الخدمات المعروضة في طلبات الخدمات غير الموجودة ';
            $msg .= "\n" . '🔹 مخصص للخدمات المتعلقة بالبرمجة فقط 👌';
            $msg .= "\n" . '🔹 سأرسل لك طلب الخدمة مع المحتوى والرابط.. ';
            $msg .= "\n" . '🔹 يتم الارسل فور النشر على خمسات ⏱';
            $msg .= "\n" . '';
            $msg .= "\n" . '👈 للإشتراك في أي وقت أكتب "إشتراك"';
            $msg .= "\n" . '👈 أكتب "إلغاء الإشتراك" لإيقاف البوت وإلغاء الإشتراك';
            $msg .= "\n" . '';
            $msg .= "\n" . '📡¦ أهدي هذه الخدمة المجانية لأصدقاءك المبرمجين، سيسعدون بهذه الخدمة 🌸';
            $msg .= "\n" . '@khamsat_bot';
            $msg .= "\n" . '';
            $msg .= "\n" . '✔️';
            $msg .= "\n" . '🌸🌸';
        }
        $bot->sendMessage($msg);
        
}


////////////////////////////////////////
function get_last_new_requsets($requests){
    if(!is_array($requests))return false;
    $result = array();
    $times = array(
        'الآن',
        'منذ ثواني',
        'قبل ثواني',
        'أقل من دقيقة',
        'منذ دقيقة',
        'منذ دقيقتين',
    );
    
    for($i = 3; $i <= 10; $i++)$times[] = "منذ $i دقائق";
    for($i = 11; $i <= 40; $i++)$times[] = "منذ $i دقيقة";

    foreach($requests as $request){
        if(in_array($request['time'], $times)){
            $result[] = $request;
        }
    }
    return $result;
}

////////////////////////////////////////
function get_full_request($request_url){
    $requests_html = file_get_contents($request_url);

    /*** new dom object ***/ 
    $dom = new domDocument; 
    libxml_use_internal_errors(true);
    $dom->loadHTML($requests_html); 
    libxml_clear_errors();
    $dom->preserveWhiteSpace = false;
    $articles = $dom->getElementsByTagName('article');

    $request = array();
    $request['title']   = trim($dom->getElementsByTagName('h1')->item(0)->nodeValue);
    $request['article'] = trim($articles->item(0)->nodeValue);
    $request['article'] = str_replace('<br>', "\n", $request['article']);
    $request['url']     = $request_url;

    return $request;
}

////////////////////////////////////////
function get_requset_msg($request){
    $msg = '';
    $msg .= $request['title'];
    $msg .= "\n\n";
    $msg .= $request['article'];
    $msg .= "\n\n";
    $msg .= '<a href="'. $request['url'] .'">ذهاب</a>';
    $msg .= "\n";
    $msg .= '<i>()</i>';
    $msg .= "\n\n";
    $msg .= 'ـ————————————————————';
    return $msg;
}
////////////////////////////////////////
function get_requsets_msgs($requests){
    if(!$requests || !is_array($requests) || empty($requests))return '';

    $last_sent = array();
    if(file_exists('./last_sent.json')){
        $last_sent_json = file_get_contents('./last_sent.json');
        if($last_sent_json){
            $last_sent = json_decode($last_sent_json, true);
        }
    }
    $words = explode("\n", trim(strtolower(file_get_contents('words.txt'))));
    foreach($words as &$word){
        $word = trim($word);
    }

    $msgs = array();
    foreach($requests as $key => $request){
        if(in_array($request['mini'], $last_sent))continue;
        if($words && is_array($words) && !empty($words)){
            $title = explode(" ", strtolower(trim($request['title'])));
            $found = false;
            foreach($words as $word){
                if($title && (strpos($request['title'], $word) !== false || in_array($word,$title) || in_array('ب'.$word,$title) || in_array('و'.$word,$title) || in_array('ال'.$word,$title) || in_array('بال'.$word,$title) || in_array('وبال'.$word,$title) || in_array('او'.$word,$title) || in_array('أو'.$word,$title))){
                    $found = true;
                }
            }
            if(!$found)continue;
        }

        $full_request = get_full_request($request['link']);

        $msg = '';
        $msg .= '<a href="'.$request['link'].'">' . $request['title'] . '</a>';
        $msg .= "\n\n";
        $msg .= '<b>بواسطة</b> 👤 ';
        $msg .= $request['user_name'];
        $msg .= "\n";
        $msg .= '<i>('.$request['time'] . ')</i>';
        $msg .= "\n\n";
        $msg .= 'ـ————————————————————';
        $msg .= "\n\n";
        $msg .= $full_request['article'];
        $msg .= "\n\n";
        $msg .= 'ـ————————————————————';
        $msgs[] = $msg;

        array_unshift($last_sent, $request['mini']);
        $last_sent = array_slice($last_sent, 0, 100, true);
        file_put_contents('./last_sent.json', json_encode($last_sent));
    }
    return $msgs;
}


// exit executing
exit;

?>