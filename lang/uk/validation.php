<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Default error messages used by the validator. Keys mirror lang/en/validation.php.
    |
    */

    'accepted' => 'Поле :attribute має бути прийняте.',
    'accepted_if' => 'Поле :attribute має бути прийняте, коли :other дорівнює :value.',
    'active_url' => 'Поле :attribute має бути дійсною URL-адресою.',
    'after' => 'Поле :attribute має бути датою після :date.',
    'after_or_equal' => 'Поле :attribute має бути датою не раніше :date.',
    'alpha' => 'Поле :attribute може містити лише літери.',
    'alpha_dash' => 'Поле :attribute може містити лише літери, цифри, дефіси та підкреслення.',
    'alpha_num' => 'Поле :attribute може містити лише літери та цифри.',
    'any_of' => 'Поле :attribute недійсне.',
    'array' => 'Поле :attribute має бути масивом.',
    'array_keys' => 'Поле :attribute може містити лише такі ключі: :values.',
    'ascii' => 'Поле :attribute може містити лише однобайтові латинські літери, цифри та символи.',
    'base64' => 'Поле :attribute має бути дійсним рядком Base64.',
    'before' => 'Поле :attribute має бути датою до :date.',
    'before_or_equal' => 'Поле :attribute має бути датою не пізніше :date.',
    'between' => [
        'array' => 'Поле :attribute має містити від :min до :max елементів.',
        'file' => 'Розмір файлу в полі :attribute має бути від :min до :max кілобайт.',
        'numeric' => 'Поле :attribute має бути між :min і :max.',
        'string' => 'Поле :attribute має містити від :min до :max символів.',
    ],
    'boolean' => 'Поле :attribute має бути «так» або «ні».',
    'can' => 'Поле :attribute містить недозволене значення.',
    'confirmed' => 'Підтвердження поля :attribute не збігається.',
    'contains' => 'У полі :attribute бракує обов’язкового значення.',
    'current_password' => 'Неправильний пароль.',
    'date' => 'Поле :attribute має бути дійсною датою.',
    'date_equals' => 'Поле :attribute має бути датою :date.',
    'date_format' => 'Поле :attribute має відповідати формату :format.',
    'decimal' => 'Поле :attribute має містити :decimal знаків після коми.',
    'declined' => 'Поле :attribute має бути відхилене.',
    'declined_if' => 'Поле :attribute має бути відхилене, коли :other дорівнює :value.',
    'different' => 'Поля :attribute і :other мають відрізнятися.',
    'digits' => 'Поле :attribute має містити :digits цифр.',
    'digits_between' => 'Поле :attribute має містити від :min до :max цифр.',
    'dimensions' => 'Зображення в полі :attribute має недопустимі розміри.',
    'distinct' => 'Поле :attribute містить значення, що повторюється.',
    'doesnt_contain' => 'Поле :attribute не повинно містити жодного з таких значень: :values.',
    'doesnt_end_with' => 'Поле :attribute не повинно закінчуватися жодним із таких значень: :values.',
    'doesnt_start_with' => 'Поле :attribute не повинно починатися жодним із таких значень: :values.',
    'email' => 'Поле :attribute має бути дійсною адресою електронної пошти.',
    'encoding' => 'Поле :attribute має бути в кодуванні :encoding.',
    'ends_with' => 'Поле :attribute має закінчуватися одним із таких значень: :values.',
    'enum' => 'Вибране значення поля :attribute недійсне.',
    'exists' => 'Вибране значення поля :attribute недійсне.',
    'extensions' => 'Поле :attribute має мати одне з таких розширень: :values.',
    'file' => 'Поле :attribute має бути файлом.',
    'filled' => 'Поле :attribute має бути заповнене.',
    'gt' => [
        'array' => 'Поле :attribute має містити більше ніж :value елементів.',
        'file' => 'Розмір файлу в полі :attribute має бути більшим за :value кілобайт.',
        'numeric' => 'Поле :attribute має бути більшим за :value.',
        'string' => 'Поле :attribute має містити більше ніж :value символів.',
    ],
    'gte' => [
        'array' => 'Поле :attribute має містити щонайменше :value елементів.',
        'file' => 'Розмір файлу в полі :attribute має бути не меншим за :value кілобайт.',
        'numeric' => 'Поле :attribute має бути не меншим за :value.',
        'string' => 'Поле :attribute має містити щонайменше :value символів.',
    ],
    'hex_color' => 'Поле :attribute має бути дійсним шістнадцятковим кольором.',
    'image' => 'Поле :attribute має бути зображенням.',
    'in' => 'Вибране значення поля :attribute недійсне.',
    'in_array' => 'Значення поля :attribute має бути в :other.',
    'in_array_keys' => 'Поле :attribute має містити принаймні один із таких ключів: :values.',
    'integer' => 'Поле :attribute має бути цілим числом.',
    'ip' => 'Поле :attribute має бути дійсною IP-адресою.',
    'ipv4' => 'Поле :attribute має бути дійсною IPv4-адресою.',
    'ipv6' => 'Поле :attribute має бути дійсною IPv6-адресою.',
    'json' => 'Поле :attribute має бути дійсним рядком JSON.',
    'list' => 'Поле :attribute має бути списком.',
    'lowercase' => 'Поле :attribute має бути в нижньому регістрі.',
    'lt' => [
        'array' => 'Поле :attribute має містити менше ніж :value елементів.',
        'file' => 'Розмір файлу в полі :attribute має бути меншим за :value кілобайт.',
        'numeric' => 'Поле :attribute має бути меншим за :value.',
        'string' => 'Поле :attribute має містити менше ніж :value символів.',
    ],
    'lte' => [
        'array' => 'Поле :attribute має містити не більше ніж :value елементів.',
        'file' => 'Розмір файлу в полі :attribute має бути не більшим за :value кілобайт.',
        'numeric' => 'Поле :attribute має бути не більшим за :value.',
        'string' => 'Поле :attribute має містити не більше ніж :value символів.',
    ],
    'mac_address' => 'Поле :attribute має бути дійсною MAC-адресою.',
    'max' => [
        'array' => 'Поле :attribute може містити не більше ніж :max елементів.',
        'file' => 'Розмір файлу в полі :attribute не може перевищувати :max кілобайт.',
        'numeric' => 'Поле :attribute не може бути більшим за :max.',
        'string' => 'Поле :attribute не може містити більше ніж :max символів.',
    ],
    'max_digits' => 'Поле :attribute не може містити більше ніж :max цифр.',
    'mimes' => 'Поле :attribute має бути файлом одного з типів: :values.',
    'mimetypes' => 'Поле :attribute має бути файлом одного з типів: :values.',
    'min' => [
        'array' => 'Поле :attribute має містити щонайменше :min елементів.',
        'file' => 'Розмір файлу в полі :attribute має бути щонайменше :min кілобайт.',
        'numeric' => 'Поле :attribute має бути щонайменше :min.',
        'string' => 'Поле :attribute має містити щонайменше :min символів.',
    ],
    'min_digits' => 'Поле :attribute має містити щонайменше :min цифр.',
    'missing' => 'Поле :attribute має бути відсутнім.',
    'missing_if' => 'Поле :attribute має бути відсутнім, коли :other дорівнює :value.',
    'missing_unless' => 'Поле :attribute має бути відсутнім, якщо тільки :other не дорівнює :value.',
    'missing_with' => 'Поле :attribute має бути відсутнім, коли присутнє :values.',
    'missing_with_all' => 'Поле :attribute має бути відсутнім, коли присутні :values.',
    'multiple_of' => 'Поле :attribute має бути кратним :value.',
    'not_in' => 'Вибране значення поля :attribute недійсне.',
    'not_regex' => 'Поле :attribute має неправильний формат.',
    'numeric' => 'Поле :attribute має бути числом.',
    'password' => [
        'letters' => 'Поле :attribute має містити принаймні одну літеру.',
        'mixed' => 'Поле :attribute має містити принаймні одну велику й одну малу літеру.',
        'numbers' => 'Поле :attribute має містити принаймні одну цифру.',
        'symbols' => 'Поле :attribute має містити принаймні один спеціальний символ.',
        'uncompromised' => 'Таке значення поля :attribute знайдено у витоку даних. Будь ласка, оберіть інше.',
    ],
    'present' => 'Поле :attribute має бути присутнім.',
    'present_if' => 'Поле :attribute має бути присутнім, коли :other дорівнює :value.',
    'present_unless' => 'Поле :attribute має бути присутнім, якщо тільки :other не дорівнює :value.',
    'present_with' => 'Поле :attribute має бути присутнім, коли присутнє :values.',
    'present_with_all' => 'Поле :attribute має бути присутнім, коли присутні :values.',
    'prohibited' => 'Поле :attribute заборонене.',
    'prohibited_if' => 'Поле :attribute заборонене, коли :other дорівнює :value.',
    'prohibited_if_accepted' => 'Поле :attribute заборонене, коли :other прийняте.',
    'prohibited_if_declined' => 'Поле :attribute заборонене, коли :other відхилене.',
    'prohibited_unless' => 'Поле :attribute заборонене, якщо тільки :other не входить до :values.',
    'prohibits' => 'Поле :attribute забороняє присутність :other.',
    'regex' => 'Поле :attribute має неправильний формат.',
    'required' => 'Поле :attribute обов’язкове.',
    'required_array_keys' => 'Поле :attribute має містити записи для: :values.',
    'required_if' => 'Поле :attribute обов’язкове, коли :other дорівнює :value.',
    'required_if_accepted' => 'Поле :attribute обов’язкове, коли :other прийняте.',
    'required_if_declined' => 'Поле :attribute обов’язкове, коли :other відхилене.',
    'required_unless' => 'Поле :attribute обов’язкове, якщо тільки :other не входить до :values.',
    'required_with' => 'Поле :attribute обов’язкове, коли присутнє :values.',
    'required_with_all' => 'Поле :attribute обов’язкове, коли присутні :values.',
    'required_without' => 'Поле :attribute обов’язкове, коли відсутнє :values.',
    'required_without_all' => 'Поле :attribute обов’язкове, коли відсутні всі з :values.',
    'same' => 'Поле :attribute має збігатися з :other.',
    'size' => [
        'array' => 'Поле :attribute має містити :size елементів.',
        'file' => 'Розмір файлу в полі :attribute має бути :size кілобайт.',
        'numeric' => 'Поле :attribute має дорівнювати :size.',
        'string' => 'Поле :attribute має містити :size символів.',
    ],
    'starts_with' => 'Поле :attribute має починатися одним із таких значень: :values.',
    'string' => 'Поле :attribute має бути рядком.',
    'timezone' => 'Поле :attribute має бути дійсним часовим поясом.',
    'unique' => 'Таке значення поля :attribute вже зайняте.',
    'uploaded' => 'Не вдалося завантажити :attribute.',
    'uppercase' => 'Поле :attribute має бути у верхньому регістрі.',
    'url' => 'Поле :attribute має бути дійсною URL-адресою.',
    'ulid' => 'Поле :attribute має бути дійсним ULID.',
    'uuid' => 'Поле :attribute має бути дійсним UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Custom messages for an attribute and rule, named "attribute.rule".
    |
    */

    'custom' => [
        // Global per field name: only groups have a slug for now.
        'slug' => [
            'regex' => 'Адреса групи може містити лише малі латинські літери, цифри й одинарні дефіси між словами (наприклад, «retro-gaming»).',
        ],
        // Not a rule name: UpdateSettingsRequest's closure rule fails with this line.
        'show_adult_content' => [
            'adult_only' => 'Вміст 18+ можна ввімкнути, лише якщо вам виповнилося 18 років.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | Reader-friendly names that replace the :attribute placeholder,
    | e.g. "електронна пошта" instead of "email".
    |
    */

    'attributes' => [
        // Account (name: lang/*/attributes.php)
        'email' => 'електронна пошта',
        'password' => 'пароль',
        'date_of_birth' => 'дата народження',
        'token' => 'токен скидання',

        // Settings
        'ui_language_code' => 'мова інтерфейсу',
        'speaking_languages' => 'мови, якими ви розмовляєте',
        'speaking_languages.*' => 'мова',
        'dark_theme' => 'темна тема',
        'show_swear_words' => 'показувати лайку',
        'show_adult_content' => 'показувати вміст 18+',
        'enable_cookies' => 'файли cookie',
        'allow_messages' => 'дозволити повідомлення',

        // Groups (name, slug: lang/*/attributes.php)
        'description' => 'опис',
        'rules' => 'правила',
        'rules.*.text' => 'текст правила',
        'rules.*.example' => 'приклад до правила',
        'language_code' => 'мова',
        'is_private' => 'приватність',

        // Posts, comments, votes
        'group_id' => 'група',
        'title' => 'заголовок',
        'article' => 'текст допису',
        'post_id' => 'допис',
        'parent_id' => 'батьківський коментар',
        'text' => 'текст',
        'target_type' => 'тип об’єкта',
        'target_id' => 'об’єкт',
        'positive' => 'голос',
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Values
    |--------------------------------------------------------------------------
    |
    | Reader-friendly replacements for rule parameters, e.g. the "today" in
    | "before:today" shown in the message.
    |
    */

    'values' => [
        'date_of_birth' => [
            'today' => 'сьогодні',
        ],
    ],

];
