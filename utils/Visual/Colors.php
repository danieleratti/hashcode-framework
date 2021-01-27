<?php

namespace Utils\Visual;

class Colors
{
    const white = 'ffffff';
    const black = '000000';

    const blue0 = 'e0eaf9';
    const blue1 = 'b3cbf0';
    const blue2 = '80a9e6';
    const blue3 = '4d86db';
    const blue4 = '266cd4';
    const blue5 = '0052cc';
    const blue6 = '004bc7';
    const blue7 = '0041c0';
    const blue8 = '0038b9';
    const blue9 = '0028ad';

    const brown0 = 'f1eeec';
    const brown1 = 'ddd4d0';
    const brown2 = 'c6b7b1';
    const brown3 = 'af9a92';
    const brown4 = '9e847a';
    const brown5 = '8d6e63';
    const brown6 = '85665b';
    const brown7 = '7a5b51';
    const brown8 = '705147';
    const brown9 = '5d3f35';

    const pink0 = 'fde8ef';
    const pink1 = 'f9c6d7';
    const pink2 = 'f6a0bd';
    const pink3 = 'f279a2';
    const pink4 = 'ef5d8e';
    const pink5 = 'ec407a';
    const pink6 = 'ea3a72';
    const pink7 = 'e73267';
    const pink8 = 'e42a5d';
    const pink9 = 'df1c4a';

    const teal0 = 'e0f4f7';
    const teal1 = 'b3e3ec';
    const teal2 = '80d1df';
    const teal3 = '4dbfd2';
    const teal4 = '26b1c9';
    const teal5 = '00a3bf';
    const teal6 = '009bb9';
    const teal7 = '0091b1';
    const teal8 = '0088a9';
    const teal9 = '00779b';

    const purple0 = 'edeef8';
    const purple1 = 'd3d4ee';
    const purple2 = 'b5b8e3';
    const purple3 = '979bd8';
    const purple4 = '8185cf';
    const purple5 = '6b70c7';
    const purple6 = '6368c1';
    const purple7 = '585dba';
    const purple8 = '4e53b3';
    const purple9 = '3c41a6';

    const green0 = 'e0f1eb';
    const green1 = 'b3dbce';
    const green2 = '80c3ad';
    const green3 = '4dab8c';
    const green4 = '269973';
    const green5 = '00875a';
    const green6 = '007f52';
    const green7 = '007448';
    const green8 = '006a3f';
    const green9 = '00572e';

    const orange0 = 'fff3e4';
    const orange1 = 'ffe0bc';
    const orange2 = 'ffcc8f';
    const orange3 = 'ffb862';
    const orange4 = 'ffa841';
    const orange5 = 'ff991f';
    const orange6 = 'ff911b';
    const orange7 = 'ff8617';
    const orange8 = 'ff7c12';
    const orange9 = 'ff6b0a';

    const red0 = 'fbe7e2';
    const red1 = 'f5c2b6';
    const red2 = 'ef9a85';
    const red3 = 'e87254';
    const red4 = 'e35330';
    const red5 = 'de350b';
    const red6 = 'da300a';
    const red7 = 'd52808';
    const red8 = 'd12206';
    const red9 = 'c81603';

    static function randomColor()
    {
        $colors = [
            Colors::blue3,
            Colors::blue9,
            Colors::brown3,
            Colors::brown9,
            Colors::pink3,
            Colors::pink9,
            Colors::teal3,
            Colors::teal9,
            Colors::purple3,
            Colors::purple9,
            Colors::green3,
            Colors::green9,
            Colors::orange3,
            Colors::orange9,
            Colors::red3,
            Colors::red9,
        ];
        return $colors[rand(0, count($colors) - 1)];
    }
}
