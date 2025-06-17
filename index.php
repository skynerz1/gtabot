<?php
ob_start();

$API_KEY = '8049956611:AAFLu_s8Ea9gLMc46Ew6F_4fZVLDZllvUQI';
define('API_KEY', $API_KEY);

function bot($method, $datas = []) {
    $url = "https://api.telegram.org/bot" . API_KEY . "/" . $method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

$content = file_get_contents('php://input');
$update = json_decode($content, true);

if (!$update) exit;

// استخرج البيانات حسب نوع التحديث (رسالة أو استعلام رد نقر)
$message = $update['message'] ?? null;
$callback_query = $update['callback_query'] ?? null;





if ($message) {
    $chat_id = $message['chat']['id'];
    $text = $message['text'] ?? '';
    $message_id = $message['message_id'];
    $user = $message['from']['username'] ?? null;
    $name = $message['from']['first_name'] ?? '';
    $mention = $user ? "[$name](https://t.me/$user)" : $name;

    

     

    // أمر /start
    if ($text === "/start") {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "مرحبًا $mention 👋\n\nأنا بوت FX2، استخدم الأوامر:\n• وصف\n• القوانين
            البوت مثبت في قروبين <3",
            'parse_mode' => 'Markdown',
            'reply_to_message_id' => $message_id
        ]);
        exit;
    }

    // كلمات محظورة
    $banned = ["اكسزعيم", "كيلوا", "جنتل", "fsgta", "xz3eem", "بايو", "@YYYYF", "bio" ,"احبك"];
    foreach ($banned as $word) {
        if (mb_stripos($text, $word) !== false) {
            // حذف رسالة المستخدم
            bot('deleteMessage', ['chat_id' => $chat_id, 'message_id' => $message_id]);

            // إرسال رسالة التنبيه
            $response = bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "⚠️ عذرًا، *عدم ذكر جمل أو كلمات محظوره* في الرسائل، $mention.",
                'parse_mode' => 'Markdown'
            ]);

            // حذف رسالة التنبيه بعد 5 ثواني
            $sent_message_id = $response['result']['message_id'] ?? null;
            if ($sent_message_id) {
                sleep(5);
                bot('deleteMessage', ['chat_id' => $chat_id, 'message_id' => $sent_message_id]);
            }
            exit;
        }
    }


$dataFile = "replies.json";

// أمر الإضافة
if (mb_strtolower(explode(" ", $text)[0]) === "اضف" && mb_strtolower(explode(" ", $text)[1] ?? '') === "رد") {
    $trigger = trim(explode(" ", $text, 3)[2] ?? '');
    if (!$trigger) {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "❗️اكتب الأمر بهذا الشكل:\nاضف رد مهام",
            'reply_to_message_id' => $message_id
        ]);
        exit;
    }

    file_put_contents("step_{$user_id}.txt", "wait_content|$trigger|$chat_id");
    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "📨 أرسل الآن *نص أو صورة أو صورة مع نص* كـ رد على: [$trigger]",
        'parse_mode' => 'Markdown'
    ]);
    exit;
}

// متابعة خطوات الإضافة
if (file_exists("step_{$user_id}.txt")) {
    $step = explode("|", file_get_contents("step_{$user_id}.txt"));
    $stage = $step[0];
    $trigger = $step[1];
    $group_id = $step[2];

    if ($stage === "wait_content") {
        $reply = [];

        if (isset($message["photo"])) {
            $reply["photo"] = end($message["photo"])["file_id"];
            if (!empty($message["caption"])) {
                $reply["text"] = $message["caption"];
            }
        } elseif (!empty($text)) {
            $reply["text"] = $text;
        }

        file_put_contents("step_{$user_id}.txt", "wait_button|$trigger|$group_id|" . json_encode($reply));
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "✏️ أرسل عنوان الزر الآن (أو اكتب /تخطي لتجاهله)"
        ]);
        exit;
    }

    if ($stage === "wait_button") {
        $reply = json_decode($step[3], true);
        if ($text === "/تخطي") {
            $replies = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];
            $replies[$group_id][$trigger] = $reply;
            file_put_contents($dataFile, json_encode($replies, JSON_UNESCAPED_UNICODE));
            unlink("step_{$user_id}.txt");
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "✅ تم حفظ الرد لكلمة [$trigger]"
            ]);
            exit;
        } else {
            file_put_contents("step_{$user_id}.txt", "wait_link|$trigger|$group_id|" . json_encode($reply) . "|$text");
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "🔗 أرسل رابط الزر الآن (أو اكتب /تخطي لتجاهله)"
            ]);
            exit;
        }
    }

    if ($stage === "wait_link") {
        $reply = json_decode($step[3], true);
        $button_text = $step[4];
        if ($text !== "/تخطي") {
            $reply["button_text"] = $button_text;
            $reply["button_url"] = $text;
        }

        $replies = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];
        $replies[$group_id][$trigger] = $reply;
        file_put_contents($dataFile, json_encode($replies, JSON_UNESCAPED_UNICODE));
        unlink("step_{$user_id}.txt");
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "✅ تم حفظ الرد الكامل لكلمة [$trigger]"
        ]);
        exit;
    }
}

// الرد التلقائي حسب القروب والكلمة
$replies = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];
$lower_text = mb_strtolower($text);
if (isset($replies[$chat_id][$lower_text])) {
    $reply = $replies[$chat_id][$lower_text];
    $markup = null;

    if (!empty($reply["button_text"]) && !empty($reply["button_url"])) {
        $markup = json_encode([
            "inline_keyboard" => [
                [["text" => $reply["button_text"], "url" => $reply["button_url"]]]
            ]
        ]);
    }

    if (isset($reply["photo"])) {
        bot('sendPhoto', [
            'chat_id' => $chat_id,
            'photo' => $reply["photo"],
            'caption' => $reply["text"] ?? null,
            'reply_markup' => $markup
        ]);
    } elseif (isset($reply["text"])) {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $reply["text"],
            'reply_markup' => $markup
        ]);
    }
}

// أمر حذف رد
if (mb_strtolower(explode(" ", $text)[0]) === "حذف" && mb_strtolower(explode(" ", $text)[1] ?? '') === "رد") {
    $trigger = trim(explode(" ", $text, 3)[2] ?? '');
    if (!$trigger) {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "❗️اكتب الأمر بهذا الشكل:\nحذف رد مهام",
            'reply_to_message_id' => $message_id
        ]);
        exit;
    }

    $replies = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];
    if (isset($replies[$chat_id][$trigger])) {
        unset($replies[$chat_id][$trigger]);
        file_put_contents($dataFile, json_encode($replies, JSON_UNESCAPED_UNICODE));
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "✅ تم حذف الرد لكلمة [$trigger] من هذا القروب."
        ]);
    } else {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "⚠️ لا يوجد رد محفوظ لكلمة [$trigger] في هذا القروب."
        ]);
    }
    exit;
}

// أمر عرض الردود
if (mb_strtolower($text) === "عرض الردود") {
    $replies = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];
    $groupReplies = $replies[$chat_id] ?? [];

    if (empty($groupReplies)) {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "🚫 لا توجد أي ردود محفوظة في هذا القروب."
        ]);
    } else {
        $list = "📄 الردود المحفوظة:\n\n";
        foreach ($groupReplies as $key => $_) {
            $list .= "• " . $key . "\n";
        }
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $list
        ]);
    }
    exit;
}

    
// تعريف بيانات المجموعات مباشرة كمصفوفة PHP بدل JSON
$groups = [
    "-1002509155667" => [
        "desc" => [
            "photo" => "https://t.me/fx2ch/10888",
            "caption" => "ياهلا بـ{mention}!\nهذي جميع رسبونات FX2",
            "button_text" => "📡 قناة FX2",
            "button_url" => "https://t.me/fx2gta5"
        ],
        "roles" => [
            "photo" => "https://t.me/fx2ch/10888",
            "caption" => "ياهلا بـ{mention}!\nهذي جميع رتب قروب fx2 role",
            "button_text" => "📡 قناة الرتب",
            "button_url" => "https://t.me/fx2role"
        ],
        "rules" => [
            "text" => "اهلاً وسهلاً بـ{mention}\nهذي قوانين القروب كاملة لابد تشيك عليها قبل إرسال أي شي داخل القروب واي مشكلة توجه للدعم!\n\n> • علماً في حال أرسلت شي بالقروب فأنت توافق على القوانين وجميع الشروط!",
            "button_text" => "⚖️ القوانين",
            "button_url" => "https://t.me/fx2link/3"
        ],
                "mhm" => [
            "text" => "اهلا وسهلا بك [$mention] وضعنا لكم المهمات المشهوره بقروبنا استمتعو بها ",
            "button_text" => "المهام 🔗",
            "button_url" => "https://t.me/fx2link/8"
        ],
                "dam" => [
            "photo" => "https://t.me/fx2ch/10888",
            "caption" => "اهلا فيك [$mention] خصصنا لكم بوت للدعم الفني اكتب مشكلتك او اقتراحك",
            "button_text" => "📡 الدعم FX2",
            "button_url" => "https://t.me/itddbot"
        ],

    ],
        "-1002566159762" => [
        "desc" => [
            "photo" => "https://t.me/fx2ch/10888",
            "caption" => "ياهلا بـ{mention}!\nهذي جميع رسبونات FX2",
            "button_text" => "📡 قناة FX2",
            "button_url" => "https://t.me/fx2gta5"
        ],
        "roles" => [
            "photo" => "https://t.me/fx3ch/10888",
            "caption" => "ياهلا بـ{mention}!\nهذي جميع رتب قروب fx2 role",
            "button_text" => "📡 قناة الرتب",
            "button_url" => "https://t.me/fx2role"
        ],
        "rules" => [
            "text" => "اهلاً وسهلاً بـ{mention}\nهذي قوانين القروب كاملة لابد تشيك عليها قبل إرسال أي شي داخل القروب واي مشكلة توجه للدعم!\n\n> • علماً في حال أرسلت شي بالقروب فأنت توافق على القوانين وجميع الشروط!",
            "button_text" => "⚖️ القوانين",
            "button_url" => "https://t.me/fx2link/3"
        ],
                "mhm" => [
            "text" => "اهلا وسهلا بك [$mention] وضعنا لكم المهمات المشهوره بقروبنا استمتعو بها ",
            "button_text" => "المهام 🔗",
            "button_url" => "https://t.me/fx2link/8"
        ],
    ],
    "-1002876941832" => [
        "desc" => [
            "photo" => "https://t.me/fx2data/37",
            "caption" => "أهلاً بـ{mention}\nهنا رسبونات قروب 𝔚𝔬𝔫𝔡𝔢𝔯 𝔤𝔯𝔬𝔲𝔭!",
            "button_text" => "🔗 قناة 𝔚𝔬𝔫𝔡𝔢𝔯 𝔤𝔯𝔬𝔲𝔭",
            "button_url" => "https://t.me/uwtwtwti"
        ],
        "roles" => [
            "photo" => "https://t.me/fx2data/37",
            "caption" => "حيّاك الله {mention}\nهذي رتب قروب 𝔚𝔬𝔫𝔡𝔢𝔯!",
            "button_text" => "📌 رتب 𝔚𝔬𝔫𝔡𝔢𝔯",
            "button_url" => "https://t.me/igigigigitr"
        ],
        "rules" => [
            "text" => "مرحباً بـ{mention}\nهذه قوانين قروب 𝔚𝔬𝔫𝔡𝔢𝔯 الرسمية، الرجاء الالتزام بها لتجنب الحظر!\n\n> • دخولك القروب يعني موافقتك الكاملة على الشروط!",
            "button_text" => "⚖️ قوانين 𝔚𝔬𝔫𝔡𝔢𝔯",
            "button_url" => "https://t.me/gigititqq"
        ],
    ]
];


// افترض عندك متغيرات $chat_id, $text, $message_id, $mention جاهزة للاستخدام

// أمر "وصف"
if (in_array($text, ['وصف', 'رسبون', 'رسبونات'])) {
    if (isset($groups[$chat_id]['desc'])) {
        $data = $groups[$chat_id]['desc'];
        $caption = str_replace("{mention}", $mention, $data['caption']);
        bot('sendPhoto', [
            'chat_id' => $chat_id,
            'photo' => $data['photo'],
            'caption' => $caption,
            'parse_mode' => 'Markdown',
            'reply_to_message_id' => $message_id,
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => $data['button_text'], 'url' => $data['button_url']]]
                ]
            ])
        ]);
    } else {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "⚠️ لا يوجد وصف لهذه المجموعة.",
            'reply_to_message_id' => $message_id,
        ]);
    }
    exit;
}
if (in_array($text, ['الدعم الفني', 'الدعم', 'دعم'])) {
    if (isset($groups[$chat_id]['dam'])) {
        $data = $groups[$chat_id]['dam'];
        $caption = str_replace("{mention}", $mention, $data['caption']);
        bot('sendPhoto', [
            'chat_id' => $chat_id,
            'photo' => $data['photo'],
            'caption' => $caption,
            'parse_mode' => 'Markdown',
            'reply_to_message_id' => $message_id,
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => $data['button_text'], 'url' => $data['button_url']]]
                ]
            ])
        ]);
    } else {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "⚠️ لا يوجد دعم لهذه المجموعة.",
            'reply_to_message_id' => $message_id,
        ]);
    }
    exit;
}

// أمر "رتب"
if (in_array($text, ['رتب', 'الرتب', 'الرتبات'])) {
    if (isset($groups[$chat_id]['roles'])) {
        $data = $groups[$chat_id]['roles'];
        $caption = str_replace("{mention}", $mention, $data['caption']);
        bot('sendPhoto', [
            'chat_id' => $chat_id,
            'photo' => $data['photo'],
            'caption' => $caption,
            'parse_mode' => 'Markdown',
            'reply_to_message_id' => $message_id,
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => $data['button_text'], 'url' => $data['button_url']]]
                ]
            ])
        ]);
    } else {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "⚠️ لا توجد رتب لهذه المجموعة.",
            'reply_to_message_id' => $message_id,
        ]);
    }
    exit;
}

// أمر "قوانين"
    if (in_array(mb_strtolower($text), ['قوانين', 'القوانين'])) {
        if (isset($groups[$chat_id]['rules'])) {
            $data = $groups[$chat_id]['rules'];
            $rules_text = str_replace("{mention}", $mention, $data['text']); // بدون تهريب
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => $rules_text,
                'parse_mode' => 'Markdown', // أو احذفه إذا ما تحتاج تنسيق
                'reply_to_message_id' => $message_id,
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => $data['button_text'], 'url' => $data['button_url']]]
                    ]
                ])
            ]);
        } else {
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "⚠️ لا توجد قوانين مخصصة لهذه المجموعة.",
                'reply_to_message_id' => $message_id,
            ]);
        }
        exit;
    }

    // أمر "مهام"
    if (in_array(mb_strtolower($text), ['مهام', 'المهمات'])) {
        if (isset($groups[$chat_id]['mhm'])) {
            $data = $groups[$chat_id]['mhm'];
            $mhm_text = str_replace("{mention}", $mention, $data['text']); // بدون تهريب
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => $mhm_text,
                'parse_mode' => 'Markdown', // أو احذفه إذا ما تحتاج تنسيق
                'reply_to_message_id' => $message_id,
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => $data['button_text'], 'url' => $data['button_url']]]
                    ]
                ])
            ]);
        } else {
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "⚠️ لا توجد مهام مخصصة لهذه المجموعة.",
                'reply_to_message_id' => $message_id,
            ]);
        }
        exit;
    }

    



    // ردود على برب
    if (mb_strtolower($text) === "برب") {
        $replies = [
            "الله معك $mention 👋 لاتنسى ترجع لنا.",
            "$mention 👋 لا تطول علينا، بننتظرك.",
            "$mention لا تنسى ترجع ترى بنشتاق 🥲",
            "في أمان الله $mention، لا تتأخر 💨",
            "$mention رجعتك أهم من ذهابك 😎",
            "في امان الله لاتطول علينا $mention"
        ];
        $reply = $replies[array_rand($replies)];
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $reply,
            'parse_mode' => 'Markdown',
            'reply_to_message_id' => $message_id
        ]);
        exit;
    }

    // ردود على باك
    if (mb_strtolower($text) === "باك") {
        $replies = [
            "نورت $mention من جديد ✨",
            "$mention رجع وأشرقت الأنوار 🌞",
            "أهلين فيك $mention، اشتقنالك 👋",
            "الحمد لله على سلامتك $mention 🚶‍♂️",
            "رجعتك غير يا $mention 😎",
            "منور القروب من جديد يا$mention",
        ];
        $reply = $replies[array_rand($replies)];
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $reply,
            'parse_mode' => 'Markdown',
            'reply_to_message_id' => $message_id
        ]);
        exit;
    }

// ردود على رسايلي
    $text = mb_strtolower($text);
if ($text === "رسايلي" || $text === "رسائلي") {
    // نفذ الأمر هنا


        $replies = [
            "حيييك يالرسايل $mention",
            "كفو استمر يـ$mention",
            "استمر ي وحش لايوقفك شي",
            "اسطورهه $mention",
            "شقيت الرسايل شق من قوتك ! $mention",
            "حي عينك وعدد الرسايل استمر $mention"

        ];
        $reply = $replies[array_rand($replies)];
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $reply,
            'parse_mode' => 'Markdown',
            'reply_to_message_id' => $message_id
        ]);
        exit;
    }
       $text = mb_strtolower($text);
if ($text === "سلام عليكم" || $text === "السلام عليكم ورحمة الله وبركاته") {
    // نفذ الأمر هنا


        $replies = [
            "عليكم السلام اهلا بك ـ$mention",
            "وعليكم السلام ورحمه الله وبركاته منورنا",
            "منور وعليكم السلام $mention"
           

        ];
        $reply = $replies[array_rand($replies)];
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $reply,
            'parse_mode' => 'Markdown',
            'reply_to_message_id' => $message_id
        ]);
        exit;
    }
       $text = mb_strtolower($text);
if ($text === "راتب" || $text === "راتبي") {
    // نفذ الأمر هنا


        $replies = [
            "انت مانشوفك الا اذا جيت تاخذ راتبك",
            "بس راتبب؟؟! تفاعل بالقروب بعد هههه",
            "اليوم راتبك اكثر تستاهل $mention"
           

        ];
        $reply = $replies[array_rand($replies)];
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $reply,
            'parse_mode' => 'Markdown',
            'reply_to_message_id' => $message_id
        ]);
        exit;
    }


    

        // نظام النقاط
        $points_file = "points1.json";
        $points_data = file_exists($points_file) ? json_decode(file_get_contents($points_file), true) : [];

        function save_points($data) {
            file_put_contents("points1.json", json_encode($data));
        }

        // صور التطبيقات والأجوبة
        $app_images = [
            ["photo" => "https://t.me/fx2data/12", "answers" => ["قراند 5", "قراند", "لعبه قراند"]],
            ["photo" => "https://t.me/fx2data/13", "answers" => ["شخصيه ترفر", "ترفر", "trevor"]],
            ["photo" => "https://t.me/fx2data/14", "answers" => ["شخصيه فرانكلين", "فرانكلين", "فرانكلن"]],
            ["photo" => "https://t.me/fx2data/15", "answers" => ["شخصيه مايكل", "مايكيل", "مايكل"]],
            ["photo" => "https://t.me/fx2data/19", "answers" => ["سياره بات مان", "سياره باتمان", "بات مان"]],
            ["photo" => "https://t.me/fx2data/21", "answers" => ["بوغاتي", "thrax", "بوقاتي", "ثراكس", "تراكس", "سياره بوغاتي"]],
            ["photo" => "https://t.me/fx2data/23", "answers" => ["شاحنه النسخ", "شاحنة البنكر", "شاحنه البنكر"]],
            ["photo" => "https://t.me/fx2data/24", "answers" => ["ديلكسو", "دلكسو", "سياره تطير"]],
            ["photo" => "https://t.me/fx2data/28", "answers" => ["air", "ايركرافت", "اير كرافت"]],
            ["photo" => "https://t.me/fx2data/30", "answers" => ["دباب طائر", "الدباب الي يطير", "دباب يطير"]],
            ["photo" => "https://t.me/fx2data/32", "answers" => ["رايس", "رولز رايس", "روز رايس"]],
            ["photo" => "https://t.me/fx2data/33", "answers" => ["سيارة شرطه", "فورد شرطه", "سياره شرطه"]],
            ["photo" => "https://t.me/fx2data/34", "answers" => ["طياره ليزر", "lazer", "ليزر"]],
            ["photo" => "https://t.me/fx2data/35", "answers" => ["سيارة مستر بن", "سياره مستر بن", "مستر بن"]],
        ];

        $session_file = "session1.json";
        $session_data = file_exists($session_file) ? json_decode(file_get_contents($session_file), true) : [];

        // عرض صورة عشوائية مع السؤال
        if (in_array(trim(mb_strtolower($text)), ["قراند صور", "صور ق", "صور قراند" , "قراند"])) {
            $app = $app_images[array_rand($app_images)];
            $session_data[$chat_id] = $app['answers'];
            file_put_contents($session_file, json_encode($session_data));

            bot('sendPhoto', [
                'chat_id' => $chat_id,
                'photo' => $app['photo'],
                'caption' => "وش اسم الي فالصوره ؟",
                'reply_to_message_id' => $message_id,
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => "- 𝕊𝕠𝕦𝕣𝕤𝕖 𝔻𝕗𝕜𝕫", 'url' => "https://t.me/JJF_l"]]
                    ]
                ])
            ]);
            exit;
        } elseif (isset($session_data[$chat_id])) {
            $correct_answers = $session_data[$chat_id];
            if (in_array(mb_strtolower(trim($text)), array_map('mb_strtolower', $correct_answers))) {
                unset($session_data[$chat_id]);
                file_put_contents($session_file, json_encode($session_data));

                $username_key = $user ?: $chat_id;
                if (!isset($points_data[$username_key])) $points_data[$username_key] = 0;
                $points_data[$username_key]++;
                save_points($points_data);

                bot('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => "🎉 إجابة صحيحة يا $mention!\nتم إضافة نقطة إلى رصيدك ✅",
                    'parse_mode' => 'Markdown',
                    'reply_to_message_id' => $message_id
                ]);
                exit;
            }
        }

        // عرض نقاط المستخدم
        if (mb_strtolower($text) === "نقاطي") {
            $username_key = $user ?: $chat_id;
            $user_points = $points_data[$username_key] ?? 0;
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "📊 نقاطك يا $mention: *$user_points* نقطة.",
                'parse_mode' => 'Markdown',
                'reply_to_message_id' => $message_id
            ]);
            exit;
        }




$src_message = "https://t.me/aerty_yu/".rand(103,207);
if($text == "دعاء" or $text == "ادعيه" or $text == "د" or $text == "."){
    bot('sendMessage',[
        'chat_id' => $chat_id,
        'text' => html_entity_decode(get_meta_tags($src_message)['twitter:description']),
        'reply_to_message_id' => $message_id,
        "parse_mode" => "markdown",
        "reply_markup" => json_encode([
            "inline_keyboard" => [
                [
                    ["text" => "- 𝕊𝕠𝕦𝕣𝕤𝕖 𝔻𝕗𝕜𝕫", "url" => "https://t.me/JJF_l"]
                ]
            ]
        ])
    ]);
}
$src_message = "https://t.me/KYY_E/".rand(4,7);
if($text == "ذ" or $text == "ذكر" or $text == "د" or $text == "اذكار"){
    bot('sendMessage',[
        'chat_id' => $chat_id,
        'text' => html_entity_decode(get_meta_tags($src_message)['twitter:description']),
        'reply_to_message_id' => $message_id,
        "parse_mode" => "markdown",
        "reply_markup" => json_encode([
            "inline_keyboard" => [
                [
                    ["text" => "- 𝕊𝕠𝕦𝕣𝕤𝕖 𝔻𝕗𝕜𝕫", "url" => "https://t.me/JJF_l"]
                ]
            ]
        ])
    ]);
}

    

    // أوامر قراند مع أزرار تفاعلية
    if ($text === "اوامر قراند") {
        $response_text = "اهلا فيك في اوامر قراند\n\nاختر قسم من الأزرار بالأسفل:";
        $reply_markup = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => 'ق1', 'callback_data' => 'q1'],
                    ['text' => 'ق2', 'callback_data' => 'q2']
                ],
                [
                    ['text' => 'ق3', 'callback_data' => 'q3'],
                    ['text' => 'ق4', 'callback_data' => 'q4']
                ]
            ]
        ]);
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $response_text,
            'reply_markup' => $reply_markup
        ]);
        exit;
    }

} elseif ($callback_query) {
    $chat_id = $callback_query['message']['chat']['id'];
    $message_id = $callback_query['message']['message_id'];
    $data = $callback_query['data'];

    // ردود بناءً على الكولباك داتا
    $responses = [
        'q1' => "اهلا بك بالقسم 1 \n هذا القسم يحتوي على لعبه \n فكره العبه ان تكتب قراند , صور ق , صور قراند \n بيجيك صور تخص قراند واول واحد يقول ايش الشي ذا بيربح نقطه \n تبي تشوف نقاطك اكتب , نقاطي || النقاط قريب يمديك نشنري رسبون فيهم",
        'q2' => "اهلا بك بالقسم 2 \n القسم يحتوي على ترتيبات لقروبات قراند \n مثلا تكتب , القوانين , الرتب , وصف \n اذا كتبت راح يجيك رد مع زر تصميم خرافي جذاب ويفيد الكل",
        'q3' => "اهلا بك بالقسم 3 \n يحتوي القسم على ايات قرانيه , دعاء \n اكتب دعاء , د , . وينجلب لك دعاء عشوائي \n الايات القرانيه قريبا \n ",
        'q4' => "قريبا"
    ];

    if (isset($responses[$data])) {
        bot('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => $responses[$data],
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => 'رجوع', 'callback_data' => 'back_to_main']]
                ]
            ])
        ]);
    } elseif ($data === 'back_to_main') {
        $main_keyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => 'ق1', 'callback_data' => 'q1'],
                    ['text' => 'ق2', 'callback_data' => 'q2']
                ],
                [
                    ['text' => 'ق3', 'callback_data' => 'q3'],
                    ['text' => 'ق4', 'callback_data' => 'q4']
                ]
            ]
        ]);
        bot('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => "اختر قسم من الأزرار بالأسفل:",
            'reply_markup' => $main_keyboard
        ]);
        
    }


    

    exit;
}
?>
