$(function() {
    $('#errors').hide();

    function parseRound(_round) {
        var words = _round.replace(/^\s+|\s+$/, '').split(/\s+/);

        // проверяем раунд
        switch (words[0]) {
            case '1':
            case '2':
            case '3':
            case '4':
                break;
            default:
                return false;
        }

        // проверяем исход
        switch (words[1]) {
            case 'draw':
                return true;
            case 'ron':
            case 'tsumo':
                break;
            case 'chombo':
                break;
            default:
                return false;
        }

        // проверяем якуман
        if (words[3] == 'yakuman') {
            if (words[4] != undefined && words[4] == 'dealer') {
                return true;
            }
            if (words[4] == undefined) {
                return true;
            }
            return false;
        }

        if (words[1] == 'chombo' && words[2].length) {
            if (words[3] == undefined || words[3] == 'dealer') {
                return true;
            }
        }

        // проверяем ханы
        if (!/(\d{1,2})han/.test(words[3])) {
            return false;
        }

        // если фу нету, но есть дилер - ок
        if (words[4] == undefined || words[4] == 'dealer') {
            return true;
        }

        // проверяем фу
        if (!/(\d+)fu/.test(words[4])) {
            return false;
        }

        if (words[5] == undefined || words[5] == 'dealer') {
            return true;
        }

        return false;
    }

    $('#addform').submit(function() {
        var errors = [];
        $('#errors').hide().html('');
        var text = $(this).find('textarea').val();
        var rows = text.split("\n");

        var scoresData = rows.splice(0, 1)[0];
        var scoresRegex = /^\s*([-_a-zа-яё0-9]*)\:(-?\d+)\s+([-_a-zа-яё0-9]*)\:(-?\d+)\s+([-_a-zа-яё0-9]*)\:(-?\d+)\s+([-_a-zа-яё0-9]*)\:(-?\d+)\s*$/i;
        if (!scoresRegex.test(scoresData)) {
            errors.push('Не удалось разобрать строку со списком игроков и очками');
        }

        for (var i = 0; i < rows.length; i++) {
            if (!parseRound(rows[i])) {
                errors.push('Не удалось разобрать раунд: ' + rows[i]);
            }
        }

        if (errors.length > 0) {
            $('#errors').html('<li>' + errors.join('</li><li>') + '</li>').show();
            return false;
        } else {
            $('#errors').hide();
            return true;
        }
    });
});