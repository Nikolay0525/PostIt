<?php

return [

    'read_all' => 'Прочитати все',
    'empty' => 'Сповіщень поки немає',
    'loading' => 'Завантаження…',
    'load_failed' => 'Не вдалося завантажити сповіщення',
    'important' => 'Важливе',
    'post_deleted' => 'Пост видалено',
    'post_unavailable' => 'Пост недоступний',
    'a_group' => 'групі',

    // Слово, яке підставляється в «до ...» нижче.
    'sanctions' => [
        'mute' => 'муту',
        'ban' => 'бану',
    ],
    'someone' => 'Хтось',

    // Built by the frontend from the ids a notification stores; :user and :post are filled in.
    'kinds' => [
        'new_comment_under_post' => ':user прокоментував(-ла) «:post»',
        'new_comment_answer' => ':user відповів(-ла) на ваш коментар у «:post»',
        'received_warning' => 'Попередження в «:group»: страйк :count з :limit до :sanction. Цей діє до :date',
        'group_mute' => 'Вас заглушено в «:group» до :date: можна читати, але не можна писати, коментувати й голосувати',
        'group_mute_forever' => 'Вас заглушено в «:group» назавжди: можна читати, але не можна писати, коментувати й голосувати',
        'warning_removed' => 'Попередження в «:group» закінчилось',
        'group_unmute' => 'Ваш мут у «:group» закінчився',
        'group_ban' => 'Вас забанено в «:group» до :date: її вміст для вас закритий',
        'group_ban_forever' => 'Вас забанено в «:group» назавжди: її вміст для вас закритий',
        'group_unban' => 'Ваш бан у «:group» закінчився',
        'unknown' => 'У вас нове сповіщення',
    ],

];
