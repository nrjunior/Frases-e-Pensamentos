<?php
// Routes

$app->get('/[{amount_thinker}]', App\Action\PhrasesAndThoughts::class);

$app->get('/{amount_thinker}/[{amount_phrases}]', App\Action\PhrasesAndThoughts::class);


