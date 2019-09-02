<?php

echo Kirby\Toolkit\Html::img(
    $page->image('flowers.jpg')->resize(844)->url() // trigger optimization
);
