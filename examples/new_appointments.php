<?php

$apikey = 'xxxxxxxxxxxxxxxxx';
$username = 'moncompte@clicrdv.com';
$password = 'secret';

require('../clicrdv-rest-client.php');
$c = new ClicRDVclient($apikey, $username, $password);



echo "Récupération des RDV d'hier...\n";
$r = $c->get('/api/v1/vevents?conditions[0][field]=intervention_id&conditions[0][op]=>&conditions[0][value]=0&conditions[1][field]=start&conditions[1][op]=%3E%3D&conditions[1][value]=2011-09-10 00:00:00&conditions[2][field]=end&conditions[2][op]=%3C%3D&conditions[2][value]=2011-09-11 00:00:00');
print_r($r->records[0]);

$fiche_id = $r->records[0]->fiche_id;


echo "Récupération de la fiche...\n";
$r = $c->get('/api/v1/fiches/'.$fiche_id);
echo "Status code : ".$c->getStatusCode()."\n";
print_r($r);

?>