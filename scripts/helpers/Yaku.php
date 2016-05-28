<?php

class YakuHelper {
    private static function _toArray($list) {
        return array_filter(explode(',', $list), function($el) {
            return $el !== null && $el !== '' && $el !== false;
        });
    }

    public static function toStringList($yakuCommaSeparated) {
        $localStrings = [
            34 => 'Дабл риичи',
            19 => 'Дайсанген',
            21 => 'Дайсууши',
            25 => 'Джунчан',
            9 => 'Иипейко',
            35 => 'Иппацу',
            12 => 'Иццу',
            32 => 'Кокушимусо',
            36 => 'Мендзен цумо',
            8 => 'Пин-фу',
            43 => 'Ренхо',
            33 => 'Риичи',
            38 => 'Риншан кайхо',
            30 => 'Рюисо',
            10 => 'Рянпейко',
            3 => 'Сананко',
            5 => 'Санканцу',
            11 => 'Саншоку',
            4 => 'Саншоку доко',
            7 => 'Сууанко',
            6 => 'Сууканцу',
            23 => 'Тан-яо',
            39 => 'Тенхо',
            1 => 'Тойтой',
            37 => 'Хайтей',
            27 => 'Хоницу',
            2 => 'Хонрото',
            41 => 'Хотей',
            22 => 'Цууисо',
            42 => 'Чанкан',
            24 => 'Чанта',
            31 => 'Чиитойцу',
            28 => 'Чиницу',
            26 => 'Чинрото',
            40 => 'Чихо',
            29 => 'Чууренпото',
            18 => 'Шосанген',
            20 => 'Шосууши',
            13 => 'Якухай 1',
            14 => 'Якухай 2',
            15 => 'Якухай 3',
            16 => 'Якухай 4',
            17 => 'Якухай 5'
        ];

        return array_map(function($id) use ($localStrings) {
            return $localStrings[$id];
        }, explode(',', $yakuCommaSeparated));
    }

    public static function getLocalYaku($yakuList, $yakumanList) {
        $yakuList = self::_toArray($yakuList);
        $yakumanList = self::_toArray($yakumanList);

        $tenhouYakuMap = [
            0  => 36,
            1  => 33,
            2  => 35,
            3  => 42,
            4  => 38,
            5  => 37,
            6  => 41,
            7  => 8,
            8  => 23,
            9  => 9,
    //        10 => 13, // yakuhai place wind ton
    //        11 => 13, // yakuhai place wind nan
    //        12 => 13, // yakuhai place wind sha
    //        13 => 13, // yakuhai place wind pei
    //        14 => 13, // yakuhai round wind ton
    //        15 => 13, // yakuhai round wind nan
    //        16 => 13, // yakuhai round wind sha
    //        17 => 13, // yakuhai round wind pei
    //        18 => 13, // yakuhai haku
    //        19 => 13, // yakuhai hatsu
    //        20 => 13, // yakuhai chun
            21 => 34,
            22 => 31,
            23 => 24,
            24 => 12,
            25 => 11,
            26 => 4,
            27 => 5,
            28 => 1,
            29 => 3,
            30 => 18,
            31 => 2,
            32 => 10,
            33 => 25,
            34 => 27,
            35 => 28,
            36 => 43,
            37 => 39,
            38 => 40,
            39 => 19,
            40 => 7,
            41 => 7, // tanki
            42 => 22,
            43 => 30,
            44 => 26,
            45 => 29,
            46 => 29, // 9-machi
            47 => 32,
            48 => 32, // 13-machi
            49 => 21,
            50 => 20,
            51 => 6
    //        52 => -1, // dora
    //        53 => -1, // uradora
    //        54 => -1 // akadora
        ];

        $yakuhaiCountMap = [1 => '13', '14', '15', '16', '17'];

        $result = [
            'yaku' => [],
            'dora' => 0
        ];
        $yakuhaiCount = 0;

        $yakuList = array_merge($yakuList, $yakumanList);
        for ($i = 0; $i < count($yakuList); $i+=2) {
            $key = $yakuList[$i];
            $value = $yakuList[$i+1];

            if ($key >= 52 && $key <= 54) {
                $result['dora'] += $value;
            } elseif ($key >= 10 && $key <= 20) {
                $yakuhaiCount++;
            } else {
                $result['yaku'] []= $tenhouYakuMap[$key];
            }
        }

        if ($yakuhaiCount > 0) {
            $result['yaku'] []= $yakuhaiCountMap[$yakuhaiCount];
        }

        return $result;
    }

    public static function getHanSum($yakuList) {
        $yakuList = self::_toArray($yakuList);

        $hanSum = 0;
        for ($i = 1; $i < count($yakuList); $i+=2) {
            $hanSum += $yakuList[$i];
        }

        return $hanSum;
    }
}